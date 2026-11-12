<?php
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/ApiTokenModel.php';
require_once __DIR__ . '/../models/ReporteModel.php';
require_once __DIR__ . '/../models/ComunidadModel.php';

// ============================================================
// Api: API REST propia de SOS Barrio, pensada para conectar una
// futura app móvil (Android/iOS) sin depender del router MVC de
// las páginas web. Se accede vía public/api.php?resource=...
//
// Recursos:
//   POST /api.php?resource=auth        { dni, password }        -> token
//   POST /api.php?resource=registro    { nombres, ... }         -> token
//   GET  /api.php?resource=reportes                             -> lista pública
//   GET  /api.php?resource=reportes&id=5                        -> detalle + comentarios
//   POST /api.php?resource=reportes    (Bearer token)           -> crear reporte
//   POST /api.php?resource=comentarios (Bearer token)           -> comentar
//   POST /api.php?resource=panico      (Bearer token)           -> botón SOS
//   GET  /api.php?resource=comunidad                            -> grupos de WhatsApp/Telegram
//   GET  /api.php?resource=dashboard                            -> estadísticas
//   GET  /api.php?resource=catalogos                            -> tipos de incidente + vecindarios
// ============================================================

class Api
{
    private static $usuarioModel;
    private static $tokenModel;
    private static $reporteModel;
    private static $comunidadModel;

    public static function dispatch()
    {
        self::$usuarioModel = new UsuarioModel();
        self::$tokenModel = new ApiTokenModel();
        self::$reporteModel = new ReporteModel();
        self::$comunidadModel = new ComunidadModel();

        $recurso = $_GET['resource'] ?? '';
        $metodo = $_SERVER['REQUEST_METHOD'];

        try {
            switch ($recurso) {
                case 'auth':
                    if ($metodo === 'POST') return self::auth();
                    break;
                case 'registro':
                    if ($metodo === 'POST') return self::registro();
                    break;
                case 'reportes':
                    if ($metodo === 'GET') return self::reportes();
                    if ($metodo === 'POST') return self::crearReporte();
                    break;
                case 'comentarios':
                    if ($metodo === 'POST') return self::comentar();
                    break;
                case 'panico':
                    if ($metodo === 'POST') return self::panico();
                    break;
                case 'comunidad':
                    if ($metodo === 'GET') return self::comunidad();
                    break;
                case 'dashboard':
                    if ($metodo === 'GET') return self::dashboard();
                    break;
                case 'catalogos':
                    if ($metodo === 'GET') return self::catalogos();
                    break;
            }
            self::error('Recurso o método no soportado.', 404);
        } catch (Throwable $e) {
            error_log('[SOS Barrio API] '.$e->getMessage());
            self::error('No se pudo completar la operación. Verifica la configuración de la base de datos.',500);
        }
    }

    // ---------------- Helpers ----------------

    private static function body()
    {
        $datos = json_decode(file_get_contents('php://input'), true);
        return is_array($datos) ? $datos : [];
    }

    private static function token()
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
            return $m[1];
        }
        // Solo se aceptan tokens enviados en el encabezado Authorization.
        return null; // No aceptar tokens en URLs: pueden quedar en historial y registros.
    }

    private static function usuarioAutenticado()
    {
        $token = self::token();
        if (!$token) self::error('Falta el token de autenticación (header Authorization: Bearer <token>).', 401);
        $usuario = self::$tokenModel->validar($token);
        if (!$usuario) self::error('Token inválido o expirado.', 401);
        return $usuario;
    }

    private static function respond($data, $code = 200)
    {
        http_response_code($code);
        echo json_encode($data);
        exit;
    }

    private static function error($mensaje, $code = 400)
    {
        http_response_code($code);
        echo json_encode(['ok' => false, 'error' => $mensaje]);
        exit;
    }

    private static function usuarioPublico($u)
    {
        return [
            'id_usuario' => $u['id_usuario'],
            'nombres' => $u['nombres'],
            'apellidos' => $u['apellidos'],
            'dni' => $u['dni'],
            'id_vecindario' => $u['id_vecindario'],
        ];
    }

    // ---------------- Endpoints ----------------

    private static function auth()
    {
        $body = self::body();
        $dni = trim($body['dni'] ?? '');
        $password = $body['password'] ?? '';

        $usuario = self::$usuarioModel->findByDni($dni);
        if (!$usuario || $usuario['estado'] !== 'Activo' || !password_verify($password, $usuario['password'])) {
            self::error('DNI o contraseña incorrectos.', 401);
        }

        $token = self::$tokenModel->crear($usuario['id_usuario']);
        self::respond(['ok' => true, 'token' => $token, 'usuario' => self::usuarioPublico($usuario)]);
    }

    private static function registro()
    {
        $body = self::body();
        $dni = trim($body['dni'] ?? '');

        if (strlen($dni) !== 8 || !ctype_digit($dni)) self::error('El DNI debe tener 8 dígitos.', 422);
        if (!is_string($body['password'] ?? null) || strlen($body['password']) < 10) self::error('La contraseña debe tener al menos 10 caracteres.', 422);
        if (!filter_var($body['email'] ?? null, FILTER_VALIDATE_EMAIL)) self::error('Correo no válido.', 422);
        if (empty(trim($body['nombres'] ?? '')) || empty(trim($body['apellidos'] ?? ''))) self::error('Nombres y apellidos requeridos.', 422);
        if (self::$usuarioModel->dniExiste($dni) || self::$usuarioModel->emailExiste($body['email'])) self::error('DNI o correo ya registrado.', 409);

        $idUsuario = self::$usuarioModel->crear([
            'nombres' => trim($body['nombres'] ?? ''),
            'apellidos' => trim($body['apellidos'] ?? ''),
            'dni' => $dni,
            'email' => trim($body['email'] ?? ''),
            'password' => $body['password'],
            'telefono' => trim($body['telefono'] ?? ''),
            'direccion' => trim($body['direccion'] ?? ''),
            'id_vecindario' => $body['id_vecindario'] ?? null,
        ]);

        $usuario = self::$usuarioModel->findByDni($dni);
        $token = self::$tokenModel->crear($idUsuario);
        self::respond(['ok' => true, 'token' => $token, 'usuario' => self::usuarioPublico($usuario)], 201);
    }

    private static function reportes()
    {
        if (isset($_GET['id'])) {
            $reporte = self::$reporteModel->getById($_GET['id']);
            if (!$reporte) self::error('Reporte no encontrado.', 404);
            $reporte['comentarios'] = self::$reporteModel->getComentarios($_GET['id']);
            self::respond(['ok' => true, 'reporte' => $reporte]);
        }
        self::respond(['ok' => true, 'reportes' => self::$reporteModel->getAll()]);
    }

    private static function crearReporte()
    {
        $usuario = self::usuarioAutenticado();
        if ((int)$usuario['id_rol'] !== 2) self::error('Este perfil no puede registrar reportes.',403);
        $body = self::body();

        foreach (['id_tipo_incidente', 'id_vecindario', 'descripcion', 'prioridad'] as $campo) {
            if (empty($body[$campo])) self::error("Falta el campo '$campo'.", 422);
        }

        if (!in_array($body['prioridad'], ['Baja','Media','Alta'],true)
           || !is_string($body['descripcion']) || mb_strlen(trim($body['descripcion'])) > 300
           || mb_strlen(trim($body['descripcion'])) < 10) self::error('Prioridad o descripción inválida.',422);
        $idReporte = self::$reporteModel->create([
            'id_usuario' => $usuario['id_usuario'],
            'id_tipo_incidente' => $body['id_tipo_incidente'],
            'id_vecindario' => $body['id_vecindario'],
            'descripcion' => trim($body['descripcion']),
            'latitud' => $body['latitud'] ?? null,
            'longitud' => $body['longitud'] ?? null,
            'direccion_referencia' => $body['direccion_referencia'] ?? null,
            'prioridad' => $body['prioridad'],
            'origen' => 'App',
        ]);

        if ($body['prioridad'] === 'Alta') {
            try { self::$reporteModel->asignarSerenazgo($idReporte); }
            catch (Throwable $e) { error_log('[SOS Barrio API] Error en asignación: '.$e->getMessage()); }
        }

        self::respond(['ok' => true, 'id_reporte' => $idReporte], 201);
    }

    private static function comentar()
    {
        $usuario = self::usuarioAutenticado();
        $body = self::body();

        if (empty($body['id_reporte']) || empty(trim($body['comentario'] ?? ''))) {
            self::error('Falta el reporte o el comentario.', 422);
        }

        self::$reporteModel->addComentario($body['id_reporte'], $usuario['id_usuario'], trim($body['comentario']));
        self::respond(['ok' => true], 201);
    }

    private static function panico()
    {
        $usuario = self::usuarioAutenticado();
        if ((int)$usuario['id_rol'] !== 2) self::error('Perfil no autorizado para SOS.',403);
        $body = self::body();

        if (!isset($body['latitud'], $body['longitud'])) {
            self::error('Faltan las coordenadas o el vecindario.', 422);
        }

        $lat = filter_var($body['latitud'], FILTER_VALIDATE_FLOAT);
        $lng = filter_var($body['longitud'], FILTER_VALIDATE_FLOAT);
        if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) self::error('Coordenadas inválidas.',422);
        $idReporte = self::$reporteModel->crearReportePanico(
            $usuario['id_usuario'], $usuario['id_vecindario'], $lat, $lng, 'App'
        );
        self::respond(['ok' => true, 'id_reporte' => $idReporte], 201);
    }

    private static function comunidad()
    {
        self::respond(['ok' => true, 'grupos' => self::$comunidadModel->getGruposPorVecindario()]);
    }

    private static function dashboard()
    {
        $usuario = self::usuarioAutenticado();
        if ((int)$usuario['id_rol'] !== 1) self::error('Solo administración tiene acceso a estas estadísticas.',403);
        self::respond([
            'ok' => true,
            'resumen' => self::$reporteModel->getResumenGeneral(),
            'porTipo' => self::$reporteModel->getReportesPorTipo(),
            'porVecindario' => self::$reporteModel->getReportesPorVecindario(),
            'indicadores' => self::$reporteModel->getIndicadoresPorVecindario(),
        ]);
    }

    private static function catalogos()
    {
        self::respond([
            'ok' => true,
            'tipos_incidente' => self::$reporteModel->getTiposIncidente(),
            'vecindarios' => self::$reporteModel->getVecindarios(),
        ]);
    }
}
