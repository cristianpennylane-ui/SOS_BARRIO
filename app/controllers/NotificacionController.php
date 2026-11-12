<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/NotificacionModel.php';

// ============================================================
// NotificacionController: endpoints AJAX para la campanita de
// notificaciones del navbar (consultados por app.js cada cierto
// tiempo, sin recargar la página)
// ============================================================

class NotificacionController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new NotificacionModel();
    }

    // GET index.php?controller=Notificacion&action=listar (AJAX)
    public function listar()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::estaConectado()) {
            echo json_encode(['ok' => false, 'noLeidas' => 0, 'items' => []]);
            exit;
        }
        $items = $this->model->getRecientes(Auth::id());
        $noLeidas = $this->model->contarNoLeidas(Auth::id());
        echo json_encode(['ok' => true, 'noLeidas' => $noLeidas, 'items' => $items]);
        exit;
    }

    // POST index.php?controller=Notificacion&action=marcarLeidas (AJAX)
    public function marcarLeidas()
    {
        header('Content-Type: application/json; charset=utf-8');
        if (!Auth::estaConectado()) {
            http_response_code(401);
            echo json_encode(['ok' => false]);
            exit;
        }
        $this->model->marcarLeidas(Auth::id());
        echo json_encode(['ok' => true]);
        exit;
    }
}
