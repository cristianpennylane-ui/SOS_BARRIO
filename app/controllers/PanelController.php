<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/PanelModel.php';
require_once __DIR__ . '/../models/SolicitudAccesoModel.php';

class PanelController extends Controller
{
    private $model;
    public function __construct() { $this->model = new PanelModel(); }

    public function index()
    {
        Auth::requerir();
        switch (Auth::rol()) {
            case Auth::ADMIN: $this->redirect('Panel', 'administrador');
            case Auth::SERENAZGO: $this->redirect('Panel', 'serenazgo');
            case Auth::VECINO: $this->redirect('Panel', 'vecino');
            default: http_response_code(403); exit('Rol no válido.');
        }
    }

    public function vecino()
    {
        Auth::requerirRol(Auth::VECINO);
        $datos = $this->model->vecino(Auth::id());
        $this->view('panel/vecino', $datos);
    }

    public function serenazgo()
    {
        Auth::requerirRol(Auth::SERENAZGO);
        $datos = $this->model->serenazgo(Auth::idVecindario(), Auth::id());
        $datos['mensaje'] = $_SESSION['flash_ok'] ?? null;
        $datos['error'] = $_SESSION['flash_error'] ?? null;
        unset($_SESSION['flash_ok'], $_SESSION['flash_error']);
        $this->view('panel/serenazgo', $datos);
    }

    public function administrador()
    {
        Auth::requerirRol(Auth::ADMIN);
        $datos = $this->model->administrador();
        try { $datos['solicitudes_pendientes'] = (new SolicitudAccesoModel())->contarPendientes(); }
        catch (Throwable $e) { $datos['solicitudes_pendientes'] = null; }
        $this->view('panel/administrador', $datos);
    }
}
