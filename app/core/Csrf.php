<?php
class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

    public static function campo(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verificarSolicitud(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') return;
        $recibido = $_POST['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        if (!is_string($recibido) || !hash_equals(self::token(), $recibido)) {
            http_response_code(419);
            header('Content-Type: text/plain; charset=utf-8');
            exit('La solicitud expiró o no es válida. Recarga la página e inténtalo nuevamente.');
        }
    }
}
