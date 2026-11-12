<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ComunidadModel.php';

// ============================================================
// ComunidadController: grupos de WhatsApp/Telegram por vecindario,
// con QR de invitación generado en el navegador (qrcode.min.js)
// ============================================================

class ComunidadController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new ComunidadModel();
    }

    // GET index.php?controller=Comunidad&action=index
    public function index()
    {
        $grupos = $this->model->getGruposPorVecindario();
        $this->view('comunidad/index', ['grupos' => $grupos]);
    }
}
