<?php
// ============================================================
// public/api.php — Punto de entrada de la API REST de SOS Barrio.
// Pensada para que una futura app móvil (o cualquier cliente
// externo) consuma el sistema sin pasar por las páginas web.
// Ver la lista de endpoints al inicio de app/core/Api.php
// ============================================================

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../app/core/Api.php';
Api::dispatch();
