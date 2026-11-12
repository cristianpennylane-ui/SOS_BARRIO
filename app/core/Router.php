<?php
// Enrutador MVC con lista permitida: evita invocar métodos internos desde URLs.
class Router
{
    public static function dispatch(): void
    {
        $rutas = [
            'Auth' => ['login', 'procesarLogin', 'registro', 'procesarRegistro', 'logout'],
            'Panel' => ['index', 'vecino', 'serenazgo', 'administrador'],
            'Admin' => ['usuarios', 'guardarUsuario', 'crearOficial', 'resolverSolicitud'],
            'Serenazgo' => ['tomar', 'atender'],
            'Dashboard' => ['index', 'data'],
            'Reporte' => ['index', 'create', 'store', 'detalle', 'mapa', 'geojson', 'comentar', 'cerrar', 'calificar', 'zonasriesgo', 'panico'],
            'Comunidad' => ['index'],
            'Perfil' => ['credencial', 'verificar', 'seguridad', 'cambiarPassword'],
            'Notificacion' => ['listar', 'marcarLeidas'],
        ];
        $controllerName = $_GET['controller'] ?? 'Reporte';
        $action = $_GET['action'] ?? 'index';
        if (!is_string($controllerName) || !is_string($action) || !isset($rutas[$controllerName]) || !in_array($action, $rutas[$controllerName], true)) {
            http_response_code(404);
            exit('Página no encontrada.');
        }
        $controllerFile = __DIR__ . '/../controllers/' . $controllerName . 'Controller.php';
        require_once $controllerFile;
        $controllerClass = $controllerName . 'Controller';
        $controller = new $controllerClass();
        $controller->$action();
    }
}
