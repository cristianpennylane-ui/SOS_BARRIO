<?php
// ============================================================
// Controller base: métodos comunes para todos los controladores
// ============================================================

class Controller
{
    // Carga una vista pasándole datos (array asociativo -> variables)
    protected function view($viewPath, $data = [])
    {
        extract($data);
        require_once __DIR__ . "/../views/layout/header.php";
        require_once __DIR__ . "/../views/{$viewPath}.php";
        require_once __DIR__ . "/../views/layout/footer.php";
    }

    // Redirige a otra ruta (index.php?controller=x&action=y)
    protected function redirect($controller, $action = 'index')
    {
        header("Location: index.php?controller={$controller}&action={$action}");
        exit;
    }
}
