<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/UsuarioModel.php';

// ============================================================
// PerfilController: credencial vecinal digital.
// Genera una tarjeta de identificación con un código QR que
// enlaza a una página pública de verificación (útil para que el
// serenazgo o un vecino confirmen, en la calle, que alguien
// pertenece realmente a la junta vecinal).
// ============================================================

class PerfilController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new UsuarioModel();
    }

    // GET index.php?controller=Perfil&action=credencial
    public function credencial()
    {
        Auth::requerirRol(Auth::VECINO);
        $usuario = $this->model->getById(Auth::id());
        $this->view('perfil/credencial', ['usuario' => $usuario]);
    }

    public function seguridad()
    {
        Auth::requerir();
        $mensaje = $_SESSION['flash_ok'] ?? null;
        $error = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        $this->view('perfil/seguridad', compact('mensaje','error'));
    }

    public function cambiarPassword()
    {
        Auth::requerir();
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $actual = $_POST['password_actual'] ?? '';
        $nuevo = $_POST['password_nuevo'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';
        if (!is_string($actual) || !is_string($nuevo) || strlen($nuevo) < 10 || strlen($nuevo) > 128 || $nuevo !== $confirmar || $nuevo === $actual) {
            $_SESSION['flash_error'] = 'Contraseña nueva inválida, distinta o no confirmada. Debe tener entre 10 y 128 caracteres.';
            $this->redirect('Perfil', 'seguridad');
        }
        try { $ok = $this->model->cambiarPassword(Auth::id(), $actual, $nuevo); }
        catch (Throwable $e) { error_log('[SOS Barrio] Falló cambio de clave: '.$e->getMessage()); $ok = false; }
        $_SESSION[$ok ? 'flash_ok' : 'flash_error'] = $ok ? 'Contraseña actualizada correctamente.' : 'No se pudo cambiar la contraseña. Revisa la contraseña actual.';
        $this->redirect('Perfil','seguridad');
    }

    // GET index.php?controller=Perfil&action=verificar&dni=12345678
    // Vista pública (sin sesión): a esta URL apunta el QR de la credencial.
    public function verificar()
    {
        $dni = trim($_GET['dni'] ?? '');
        $resultado = null;
        if (strlen($dni) === 8 && ctype_digit($dni)) {
            $resultado = $this->model->getVerificacionPublicaPorDni($dni);
        }
        $this->view('perfil/verificar', ['resultado' => $resultado, 'dni' => $dni]);
    }
}
