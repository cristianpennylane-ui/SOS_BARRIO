<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';
require_once __DIR__ . '/../models/SolicitudAccesoModel.php';

class AuthController extends Controller
{
    private $model;
    public function __construct() { $this->model = new UsuarioModel(); }

    public function login()
    {
        if (Auth::estaConectado()) $this->redirect('Panel', 'index');
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_error']);
        $rolSeleccionado = filter_var($_GET['rol'] ?? ($_SESSION['login_rol_seleccionado'] ?? Auth::VECINO), FILTER_VALIDATE_INT);
        if (!in_array($rolSeleccionado, [Auth::VECINO,Auth::SERENAZGO,Auth::ADMIN],true)) $rolSeleccionado=Auth::VECINO;
        unset($_SESSION['login_rol_seleccionado']);
        $this->view('auth/login', ['error' => $error, 'redir' => isset($_GET['redir']), 'rolSeleccionado' => (int) $rolSeleccionado]);
    }

    public function procesarLogin()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $ahora = time();
        if (($_SESSION['login_espera_hasta'] ?? 0) > $ahora) {
            $_SESSION['flash_error'] = 'Demasiados intentos. Intenta nuevamente dentro de 15 minutos.';
            $this->redirect('Auth', 'login');
        }
        $identificador = is_string($_POST['identificador'] ?? null) ? trim($_POST['identificador']) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $rolSeleccionado = filter_var($_POST['rol_acceso'] ?? null, FILTER_VALIDATE_INT);
        $_SESSION['login_rol_seleccionado'] = $rolSeleccionado;
        $usuario = strlen($identificador) <= 100 && $identificador !== ''
            ? $this->model->findByDniOEmail($identificador) : null;
        // El selector solo elige el destino. El rol real se verifica desde la BD.
        if (!in_array($rolSeleccionado, [Auth::ADMIN, Auth::VECINO, Auth::SERENAZGO], true)
            || !$usuario || !password_verify($password, $usuario['password'])
            || $usuario['estado'] !== 'Activo'
            || (int) $usuario['id_rol'] !== $rolSeleccionado) {
            $fallos = (int) ($_SESSION['login_fallos'] ?? 0) + 1;
            $_SESSION['login_fallos'] = $fallos;
            if ($fallos >= 5) $_SESSION['login_espera_hasta'] = $ahora + 900;
            $_SESSION['flash_error'] = 'No se pudo acceder. Revisa tus credenciales y el tipo de panel elegido.';
            $this->redirect('Auth', 'login');
        }
        Auth::iniciar($usuario);
        $this->redirect('Panel', 'index');
    }

    public function registro()
    {
        if (Auth::estaConectado()) $this->redirect('Panel', 'index');
        $rolSeleccionado = filter_var($_GET['rol'] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($rolSeleccionado, [Auth::VECINO, Auth::SERENAZGO, Auth::ADMIN], true)) {
            $rolSeleccionado = Auth::VECINO;
        }
        $vecindarios = $this->model->getVecindarios();
        $error = $_SESSION['flash_error'] ?? null;
        $mensaje = $_SESSION['flash_ok'] ?? null;
        $anteriores = $_SESSION['registro_valores'] ?? [];
        unset($_SESSION['flash_error'], $_SESSION['flash_ok'], $_SESSION['registro_valores']);
        $this->view('auth/registro', compact('vecindarios','error','mensaje','anteriores','rolSeleccionado'));
    }

    public function procesarRegistro()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $rol = filter_var($_POST['rol_registro'] ?? null, FILTER_VALIDATE_INT);
        if (!in_array($rol, [Auth::ADMIN,Auth::VECINO,Auth::SERENAZGO], true)) {
            $_SESSION['flash_error'] = 'Selecciona un tipo de cuenta válido.';
            $this->redirect('Auth','registro');
        }
        $dni = is_string($_POST['dni'] ?? null) ? trim($_POST['dni']) : '';
        $email = is_string($_POST['email'] ?? null) ? strtolower(trim($_POST['email'])) : '';
        $password = $_POST['password'] ?? null;
        $confirmacion = $_POST['confirmar_password'] ?? null;
        $vecindario = filter_var($_POST['id_vecindario'] ?? null, FILTER_VALIDATE_INT);
        $nombres = is_string($_POST['nombres'] ?? null) ? trim($_POST['nombres']) : '';
        $apellidos = is_string($_POST['apellidos'] ?? null) ? trim($_POST['apellidos']) : '';
        $telefono = is_string($_POST['telefono'] ?? null) ? trim($_POST['telefono']) : '';
        $direccion = is_string($_POST['direccion'] ?? null) ? trim($_POST['direccion']) : '';
        $institucion = is_string($_POST['institucion'] ?? null) ? trim($_POST['institucion']) : '';
        $justificacion = is_string($_POST['justificacion'] ?? null) ? trim($_POST['justificacion']) : '';
        $turno = is_string($_POST['turno'] ?? null) ? trim($_POST['turno']) : '';

        $longitud = static fn(string $valor): int => function_exists('mb_strlen') ? mb_strlen($valor, 'UTF-8') : strlen($valor);

        // Conserva solo los campos no sensibles para facilitar la corrección de errores.
        $_SESSION['registro_valores'] = compact('nombres','apellidos','dni','email','telefono','direccion','institucion','justificacion','turno','vecindario');
        $volver = function (string $mensaje) use ($rol): void {
            $_SESSION['flash_error'] = $mensaje;
            header('Location: index.php?controller=Auth&action=registro&rol='.$rol);
            exit;
        };
        if (!preg_match('/^[0-9]{8}$/D', $dni) || !filter_var($email, FILTER_VALIDATE_EMAIL)
            || $longitud($email)>100 || $longitud($nombres)<2 || $longitud($nombres)>60
            || $longitud($apellidos)<2 || $longitud($apellidos)>60) {
            $volver('Comprueba tu nombre, apellidos, DNI de 8 dígitos y correo válido.');
        }
        if (!is_string($password) || strlen($password)<10 || strlen($password)>255
            || !is_string($confirmacion) || !hash_equals($password,$confirmacion)) {
            $volver('La contraseña debe tener entre 10 y 255 caracteres y coincidir con su confirmación.');
        }
        if ($longitud($telefono)>15 || ($telefono !== '' && !preg_match('/^[+0-9() .-]{7,15}$/D',$telefono))
            || $longitud($direccion)>150 || $longitud($institucion)>120 || $longitud($justificacion)>500) {
            $volver('Revisa la longitud de teléfono, dirección, institución y motivo de solicitud.');
        }
        $idsValidos = array_map('intval',array_column($this->model->getVecindarios(),'id_vecindario'));
        if (!$vecindario || !in_array($vecindario,$idsValidos,true)) {
            $volver('Selecciona un vecindario válido.');
        }
        if ($rol === Auth::VECINO && $direccion === '') {
            $volver('Indica una dirección para tu cuenta vecinal.');
        }
        if ($rol !== Auth::VECINO && ($longitud($institucion)<3 || $longitud($justificacion)<15
            || !isset($_POST['declaracion']) || $_POST['declaracion'] !== '1')) {
            $volver('Para solicitar acceso institucional indica tu dependencia, motivo (mínimo 15 caracteres) y acepta la declaración.');
        }
        if ($rol === Auth::SERENAZGO && !in_array($turno,['Mañana','Tarde','Noche'],true)) {
            $volver('Selecciona un turno válido para serenazgo.');
        }
        if ($this->model->dniExiste($dni) || $this->model->emailExiste($email)) {
            $volver('Ya existe una cuenta con ese DNI o correo. Puedes iniciar sesión o contactar a administración.');
        }

        try {
            if ($rol === Auth::VECINO) {
                $this->model->crear([
                    'nombres'=>$nombres,'apellidos'=>$apellidos,'dni'=>$dni,'email'=>$email,
                    'password'=>$password,'telefono'=>$telefono,'direccion'=>$direccion,
                    'id_vecindario'=>$vecindario,
                ]);
                unset($_SESSION['registro_valores']);
                $usuario=$this->model->findByDni($dni);
                Auth::iniciar($usuario);
                $this->redirect('Panel','index');
            }
            // El registro público de privilegios NO crea usuarios ni da acceso.
            if (($_SESSION['registro_privilegiado_ultimo'] ?? 0) + 60 > time()) {
                $volver('Espera un minuto antes de enviar otra solicitud de acceso.');
            }
            $solicitudes = new SolicitudAccesoModel();
            if ($solicitudes->tienePendiente($dni,$email)) {
                $volver('Ya existe una solicitud pendiente con esos datos. Espera la revisión de administración.');
            }
            $solicitudes->crearSolicitud([
                'rol_solicitado'=>$rol,'nombres'=>$nombres,'apellidos'=>$apellidos,
                'dni'=>$dni,'email'=>$email,'password'=>$password,'telefono'=>$telefono,
                'direccion'=>$direccion,'id_vecindario'=>$vecindario,
                'turno'=>$rol===Auth::SERENAZGO?$turno:null,
                'institucion'=>$institucion,'justificacion'=>$justificacion,
            ]);
            $_SESSION['registro_privilegiado_ultimo']=time();
            unset($_SESSION['registro_valores']);
            $_SESSION['flash_ok']='Solicitud recibida. Un administrador deberá verificar tu identidad y aprobarla. Aún no puedes iniciar sesión con este rol.';
        } catch (Throwable $e) {
            error_log('[SOS Barrio] Registro / solicitud: '.$e->getMessage());
            $volver('No se pudo guardar la solicitud. Si instalaste una actualización, verifica que importaste el SQL de v5.');
        }
        header('Location: index.php?controller=Auth&action=registro&rol='.$rol);
        exit;
    }

    public function logout()
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        Auth::cerrar();
        session_start();
        $this->redirect('Auth', 'login');
    }
}
