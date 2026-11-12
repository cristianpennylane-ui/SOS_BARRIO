<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

// ============================================================
// ReporteController: maneja el listado, creación y cierre
// de reportes de incidentes (feature principal de SOS Barrio)
// ============================================================

class ReporteController extends Controller
{
    private $model;
    private $notificaciones;

    public function __construct()
    {
        $this->model = new ReporteModel();
        $this->notificaciones = new NotificacionModel();
    }

    // GET index.php?controller=Reporte&action=index
    public function index()
    {
        $reportes = $this->model->getAll();
        $resumen = $this->model->getResumenGeneral();
        $this->view('reportes/index', ['reportes' => $reportes, 'resumen' => $resumen]);
    }

    // GET index.php?controller=Reporte&action=create
    public function create()
    {
        Auth::requerirRol(Auth::VECINO);
        $tipos = $this->model->getTiposIncidente();
        $vecindarios = $this->model->getVecindarios();
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        $this->view('reportes/create', ['tipos' => $tipos, 'vecindarios' => $vecindarios, 'error' => $error]);
    }

    // POST index.php?controller=Reporte&action=store
    public function store()
    {
        Auth::requerirRol(Auth::VECINO);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $tipo = filter_var($_POST['id_tipo_incidente'] ?? null, FILTER_VALIDATE_INT);
        $zona = filter_var($_POST['id_vecindario'] ?? null, FILTER_VALIDATE_INT);
        $desc = trim(is_string($_POST['descripcion'] ?? null) ? $_POST['descripcion'] : '');
        $prioridad = $_POST['prioridad'] ?? '';
        $lat = ($_POST['latitud'] ?? '') === '' ? null : filter_var($_POST['latitud'], FILTER_VALIDATE_FLOAT);
        $lng = ($_POST['longitud'] ?? '') === '' ? null : filter_var($_POST['longitud'], FILTER_VALIDATE_FLOAT);
        $direccion = trim(is_string($_POST['direccion_referencia'] ?? null) ? $_POST['direccion_referencia'] : '');
        $tiposValidos = array_map('intval', array_column($this->model->getTiposIncidente(), 'id_tipo_incidente'));
        $zonasValidas = array_map('intval', array_column($this->model->getVecindarios(), 'id_vecindario'));
        if (!$tipo || !in_array($tipo, $tiposValidos, true) || !$zona || !in_array($zona, $zonasValidas, true)
            || mb_strlen($desc) < 10 || mb_strlen($desc) > 300 || mb_strlen($direccion) > 200
            || !in_array($prioridad, ['Baja','Media','Alta'], true)
            || $lat === false || $lng === false
            || ($lat !== null && ($lat < -90 || $lat > 90)) || ($lng !== null && ($lng < -180 || $lng > 180))) {
            $_SESSION['flash_error'] = 'Revisa el tipo, zona, descripción, prioridad y coordenadas del reporte.';
            $this->redirect('Reporte', 'create');
        }
        try {
            $idReporte = $this->model->create([
                'id_usuario' => Auth::id(), 'id_tipo_incidente' => $tipo,
                'id_vecindario' => $zona, 'descripcion' => $desc,
                'latitud' => $lat, 'longitud' => $lng,
                'direccion_referencia' => $direccion,
                'prioridad' => $prioridad, 'origen' => 'Web',
            ]);
        } catch (Throwable $e) {
            error_log('[SOS Barrio] Error al crear reporte: '.$e->getMessage());
            $_SESSION['flash_error'] = 'No se pudo guardar el reporte. Verifica que las extensiones y triggers SQL estén instalados.';
            $this->redirect('Reporte', 'create');
        }
        // Si el procedimiento no está disponible, el reporte sigue guardado.
        if ($prioridad === 'Alta') {
            try { $this->model->asignarSerenazgo($idReporte); }
            catch (Throwable $e) { error_log('[SOS Barrio] Falló asignación automática: '.$e->getMessage()); }
        }
        $this->redirect('Panel', 'vecino');
    }

    // GET index.php?controller=Reporte&action=detalle&id=5
    public function detalle()
    {
        $id = $_GET['id'] ?? null;
        $reporte = $this->model->getById($id);
        $comentarios = $this->model->getComentarios($id);
        $calificacion = ($reporte && $reporte['estado'] === 'Cerrado') ? $this->model->getCalificacion($id) : null;
        $this->view('reportes/detalle', [
            'reporte' => $reporte,
            'comentarios' => $comentarios,
            'calificacion' => $calificacion,
        ]);
    }

    // GET index.php?controller=Reporte&action=mapa
    public function mapa()
    {
        $vecindarios = $this->model->getVecindarios();
        $this->view('reportes/mapa', ['vecindarios' => $vecindarios]);
    }

    // GET index.php?controller=Reporte&action=geojson  (usado por el mapa vía fetch)
    public function geojson()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->model->getReportesConUbicacion());
        exit;
    }

    // POST index.php?controller=Reporte&action=comentar  (AJAX, sin recargar la página)
    public function comentar()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::estaConectado()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para comentar.']);
            exit;
        }

        $idReporte = $_POST['id_reporte'] ?? null;
        $texto = trim($_POST['comentario'] ?? '');

        if (!$idReporte || $texto === '') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Falta el reporte o el comentario está vacío.']);
            exit;
        }

        $this->model->addComentario($idReporte, Auth::id(), $texto);

        // Notifica al vecino que creó el reporte (si no se está
        // comentando a sí mismo)
        $reporte = $this->model->getById($idReporte);
        if ($reporte && (int) $reporte['id_usuario'] !== (int) Auth::id()) {
            $this->notificaciones->crear(
                $reporte['id_usuario'],
                Auth::nombre() . ' comentó en tu reporte #' . $idReporte . ': "' . mb_substr($texto, 0, 60) . (mb_strlen($texto) > 60 ? '…' : '') . '"',
                'Comentario'
            );
        }

        echo json_encode(['ok' => true]);
        exit;
    }

    // POST index.php?controller=Reporte&action=cerrar  (solo Administrador)
    // Usa sp_cerrar_reporte, que antes existía en la BD pero no se
    // invocaba desde ninguna parte de la aplicación.
    public function cerrar()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        Auth::requerir();
        if (!Auth::esAdmin()) {
            http_response_code(403);
            die('Solo un administrador puede cerrar un reporte.');
        }

        $idReporte = $_POST['id_reporte'] ?? null;
        $observaciones = trim($_POST['observaciones'] ?? 'Caso atendido y cerrado.');

        if ($idReporte) {
            $idRespuesta = $this->model->getUltimaRespuestaAbierta($idReporte);
            if ($idRespuesta) {
                $this->model->cerrarReporte($idReporte, $idRespuesta, $observaciones);
            } else {
                // No hubo asignación previa (ej. prioridad Baja/Media): se
                // cierra igual de forma directa por el administrador.
                $this->model->cerrarSinAsignacion($idReporte);
            }
            $reporte = $this->model->getById($idReporte);
            if ($reporte) {
                $this->notificaciones->crear(
                    $reporte['id_usuario'],
                    'Tu reporte #' . $idReporte . ' fue cerrado. ¡Cuéntanos cómo fue la atención calificándolo!',
                    'Cierre'
                );
            }
        }
        $this->redirect('Reporte', 'detalle&id=' . (int) $idReporte);
    }

    // POST index.php?controller=Reporte&action=calificar  (AJAX)
    public function calificar()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::estaConectado()) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión para calificar.']);
            exit;
        }

        $idReporte = $_POST['id_reporte'] ?? null;
        $estrellas = (int) ($_POST['calificacion'] ?? 0);
        $comentario = trim($_POST['comentario'] ?? '');
        $reporte = $idReporte ? $this->model->getById($idReporte) : null;

        if (!$reporte || $estrellas < 1 || $estrellas > 5) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Datos de calificación inválidos.']);
            exit;
        }
        if ((int) $reporte['id_usuario'] !== (int) Auth::id()) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Solo quien reportó el incidente puede calificar la atención.']);
            exit;
        }
        if ($reporte['estado'] !== 'Cerrado') {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Solo se puede calificar un reporte ya cerrado.']);
            exit;
        }
        if ($this->model->getCalificacion($idReporte)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Este reporte ya fue calificado. ¡Gracias!']);
            exit;
        }

        $this->model->calificar($idReporte, Auth::id(), $estrellas, $comentario);
        echo json_encode(['ok' => true]);
        exit;
    }

    // GET index.php?controller=Reporte&action=zonasriesgo  (AJAX, panel del mapa)
    public function zonasriesgo()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->model->getZonasRiesgo());
        exit;
    }

    // POST index.php?controller=Reporte&action=panico  (botón SOS, AJAX)
    public function panico()
    {
        header('Content-Type: application/json; charset=utf-8');

        if (!Auth::tieneRol(Auth::VECINO)) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'Solo las cuentas vecinales pueden generar alertas.']);
            exit;
        }

        $lat = $_POST['latitud'] ?? null;
        $lng = $_POST['longitud'] ?? null;
        $idVecindario = Auth::idVecindario(); // La zona se deriva de la sesión, no del formulario.
        $lat = filter_var($lat, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($lng, FILTER_VALIDATE_FLOAT);

        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || !$idVecindario) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Falta la ubicación o el vecindario para enviar la alerta.']);
            exit;
        }

        try {
            $idReporte = $this->model->crearReportePanico(Auth::id(), $idVecindario, $lat, $lng, 'Panico');
            echo json_encode(['ok' => true, 'id_reporte' => $idReporte]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
        }
        exit;
    }
}
