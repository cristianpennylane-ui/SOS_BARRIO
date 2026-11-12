<?php
require_once __DIR__.'/../core/Controller.php';
require_once __DIR__.'/../core/Auth.php';
require_once __DIR__.'/../models/SerenazgoModel.php';
class SerenazgoController extends Controller
{
    private $model;
    public function __construct() { $this->model = new SerenazgoModel(); }

    public function tomar(): void
    {
        Auth::requerirRol(Auth::SERENAZGO);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $id = filter_var($_POST['id_reporte'] ?? null, FILTER_VALIDATE_INT);
        try { $ok = $id && $this->model->tomar(Auth::id(), $id); }
        catch (Throwable $e) { error_log('[SOS Barrio] Error al asignar reporte: '.$e->getMessage()); $ok=false; }
        $_SESSION[$ok ? 'flash_ok' : 'flash_error'] = $ok ? 'Incidente asignado a tu cuenta.' : 'No se pudo asignar. Verifica que siga pendiente y tu ficha de serenazgo esté habilitada.';
        $this->redirect('Panel','serenazgo');
    }

    public function atender(): void
    {
        Auth::requerirRol(Auth::SERENAZGO);
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') { http_response_code(405); exit('Método no permitido'); }
        $id = filter_var($_POST['id_reporte'] ?? null, FILTER_VALIDATE_INT);
        $obs = trim(is_string($_POST['observaciones'] ?? null) ? $_POST['observaciones'] : '');
        if ($obs === '' || mb_strlen($obs) > 300) {
            $_SESSION['flash_error'] = 'Describe brevemente la atención (máximo 300 caracteres).';
            $this->redirect('Panel','serenazgo');
        }
        try { $ok = $id && $this->model->atender(Auth::id(), $id, $obs); }
        catch (Throwable $e) { error_log('[SOS Barrio] Error al atender reporte: '.$e->getMessage()); $ok=false; }
        $_SESSION[$ok ? 'flash_ok' : 'flash_error'] = $ok ? 'Atención registrada. El administrador puede cerrar el caso.' : 'No puedes atender este incidente: comprueba la asignación y su estado.';
        $this->redirect('Panel','serenazgo');
    }
}
