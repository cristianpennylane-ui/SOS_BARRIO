<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../models/ReporteModel.php';

// ============================================================
// DashboardController: panel de estadísticas para serenazgo/
// juntas vecinales (usa las funciones SQL fn_incidentes_por_zona_mes
// y fn_tiempo_respuesta_promedio ya definidas en la BD)
// ============================================================

class DashboardController extends Controller
{
    private $model;

    public function __construct()
    {
        $this->model = new ReporteModel();
    }

    // GET index.php?controller=Dashboard&action=index
    public function index()
    {
        Auth::requerirRol(Auth::ADMIN);
        $resumen = $this->model->getResumenGeneral();
        $this->view('dashboard/index', ['resumen' => $resumen]);
    }

    // GET index.php?controller=Dashboard&action=data  (usado por Chart.js vía fetch)
    public function data()
    {
        Auth::requerirRol(Auth::ADMIN);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'resumen' => $this->model->getResumenGeneral(),
            'porTipo' => $this->model->getReportesPorTipo(),
            'porVecindario' => $this->model->getReportesPorVecindario(),
            'indicadores' => $this->model->getIndicadoresPorVecindario(),
            'porDia' => $this->model->getReportesPorDia(14),
            'zonasRiesgo' => $this->model->getZonasRiesgo(),
            'calificacion' => $this->model->getPromedioCalificacion(),
        ]);
        exit;
    }
}
