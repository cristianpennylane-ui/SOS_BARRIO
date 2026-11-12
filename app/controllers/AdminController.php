<?php
require_once __DIR__.'/../core/Controller.php';
require_once __DIR__.'/../core/Auth.php';
require_once __DIR__.'/../models/AdminModel.php';
require_once __DIR__.'/../models/SolicitudAccesoModel.php';
class AdminController extends Controller
{
    private $model;
    public function __construct() { $this->model = new AdminModel(); }
    public function usuarios(): void
    {
        Auth::requerirRol(Auth::ADMIN);
        $usuarios=$this->model->usuarios();$vecindarios=$this->model->vecindarios();
        $solicitudes=[];$avisoMigracion=null;
        try { $solicitudes=(new SolicitudAccesoModel())->pendientes(); }
        catch (Throwable $e) { error_log('[SOS Barrio] Solicitudes: '.$e->getMessage()); $avisoMigracion='No se pudo leer solicitudes. Importa sql/sos_barrio_registro_roles_v5.sql en phpMyAdmin.'; }
        $mensaje=$_SESSION['flash_ok'] ?? null;$error=$_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_ok'],$_SESSION['flash_error']);
        $this->view('admin/usuarios',compact('usuarios','vecindarios','mensaje','error','solicitudes','avisoMigracion'));
    }
    public function guardarUsuario(): void
    {
        Auth::requerirRol(Auth::ADMIN);
        if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit('Método no permitido'); }
        $id=filter_var($_POST['id_usuario'] ?? null,FILTER_VALIDATE_INT);
        $rol=filter_var($_POST['id_rol'] ?? null,FILTER_VALIDATE_INT);
        $zona=filter_var($_POST['id_vecindario'] ?? null,FILTER_VALIDATE_INT);
        $estado=$_POST['estado'] ?? '';$turno=$_POST['turno'] ?? 'Mañana';
        if (!$id || !$zona || $id===Auth::id() || !in_array($rol,[1,2,3],true) || !in_array($estado,['Activo','Inactivo'],true) || !in_array($turno,['Mañana','Tarde','Noche'],true)) {
            $_SESSION['flash_error']='Datos no válidos o intento de modificar tu propia cuenta.';
            $this->redirect('Admin','usuarios');
        }
        try { $ok=$this->model->cambiarRolYEstado($id,$rol,$estado,$zona,$turno); }
        catch (Throwable $e) { error_log('[SOS Barrio] No se pudo actualizar el usuario: '.$e->getMessage());$ok=false; }
        $_SESSION[$ok?'flash_ok':'flash_error']=$ok?'Cuenta y permisos actualizados. La ficha del oficial se sincronizó si corresponde.':'No se pudo actualizar. Verifica la cuenta y los vínculos existentes.';
        $this->redirect('Admin','usuarios');
    }
    public function crearOficial(): void
    {
        Auth::requerirRol(Auth::ADMIN);
        if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit('Método no permitido'); }
        $d=['nombres'=>trim($_POST['nombres'] ?? ''),'apellidos'=>trim($_POST['apellidos'] ?? ''),
        'dni'=>trim($_POST['dni'] ?? ''),'email'=>trim($_POST['email'] ?? ''),'telefono'=>trim($_POST['telefono'] ?? ''),
        'password'=>$_POST['password'] ?? '', 'turno'=>$_POST['turno'] ?? '',
        'id_vecindario'=>filter_var($_POST['id_vecindario'] ?? null,FILTER_VALIDATE_INT)];
        if (strlen($d['nombres'])<2 || strlen($d['apellidos'])<2 || !preg_match('/^[0-9]{8}$/D',$d['dni'])
            || !filter_var($d['email'],FILTER_VALIDATE_EMAIL) || strlen($d['telefono'])>15
            || !is_string($d['password']) || strlen($d['password'])<10 || $d['password']!==($_POST['confirmar_password'] ?? null)
            || !in_array($d['turno'],['Mañana','Tarde','Noche'],true) || !$d['id_vecindario']) {
            $_SESSION['flash_error']='Verifica los datos del oficial: DNI de 8 dígitos, correo válido y contraseña de al menos 10 caracteres.';
            $this->redirect('Admin','usuarios');
        }
        try { $ok=$this->model->crearOficial($d); }
        catch (Throwable $e) { error_log('[SOS Barrio] Falló alta de oficial: '.$e->getMessage());$ok=false; }
        $_SESSION[$ok?'flash_ok':'flash_error']=$ok?'Cuenta de oficial creada con su ficha de serenazgo.':'No se pudo crear la cuenta. El DNI o correo podrían estar ya registrados.';
        $this->redirect('Admin','usuarios');
    }
    public function resolverSolicitud(): void
    {
        Auth::requerirRol(Auth::ADMIN);
        if (($_SERVER['REQUEST_METHOD'] ?? '')!=='POST') { http_response_code(405); exit('Método no permitido'); }
        $id=filter_var($_POST['id_solicitud'] ?? null,FILTER_VALIDATE_INT);
        $decision=$_POST['decision'] ?? '';
        if (!$id || !is_string($decision) || !in_array($decision,['aprobar','rechazar'],true)) {
            $_SESSION['flash_error']='Operación no válida.';
            $this->redirect('Admin','usuarios');
        }
        try {
            $resultado=(new SolicitudAccesoModel())->resolver(
                (int)$id, (int)Auth::id(), $decision, ($_POST['identidad_verificada'] ?? null)==='1'
            );
            $mensajes=[
                'approved'=>'Solicitud aprobada. La cuenta ya puede ingresar con el rol autorizado.',
                'rejected'=>'Solicitud rechazada. No se creó ninguna cuenta.',
                'verify'=>'Antes de aprobar, confirma que has verificado la identidad y autorización del solicitante.',
                'duplicate'=>'Ya existe una cuenta con ese DNI o correo. Revisa el usuario antes de aprobar.',
                'processed'=>'La solicitud ya fue resuelta por otra operación.',
                'invalid'=>'La solicitud no tiene datos válidos.'
            ];
            $_SESSION[in_array($resultado,['approved','rejected'],true)?'flash_ok':'flash_error']
                =$mensajes[$resultado] ?? 'No se completó la operación.';
        } catch (Throwable $e) {
            error_log('[SOS Barrio] Revisión de solicitud: '.$e->getMessage());
            $_SESSION['flash_error']='Error al procesar. Revisa que la migración v5 esté importada y los datos sean válidos.';
        }
        $this->redirect('Admin','usuarios');
    }

}
