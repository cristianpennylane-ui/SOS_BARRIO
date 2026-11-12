<?php
// Seguridad de sesión y autorización por rol. Los roles se definen en sql/sos_barrio.sql.
class Auth
{
    public const ADMIN = 1;
    public const VECINO = 2;
    public const SERENAZGO = 3;

    public static function estaConectado(): bool { return isset($_SESSION['id_usuario']); }
    public static function id(): ?int { return isset($_SESSION['id_usuario']) ? (int) $_SESSION['id_usuario'] : null; }
    public static function nombre(): ?string { return $_SESSION['usuario_nombre'] ?? null; }
    public static function rol(): ?int { return isset($_SESSION['usuario_rol']) ? (int) $_SESSION['usuario_rol'] : null; }
    public static function idVecindario(): ?int { return isset($_SESSION['usuario_vecindario']) ? (int) $_SESSION['usuario_vecindario'] : null; }
    public static function esAdmin(): bool { return self::rol() === self::ADMIN; }
    public static function tieneRol(int ...$roles): bool { return in_array(self::rol(), $roles, true); }

    public static function iniciar(array $usuario): void
    {
        session_regenerate_id(true); // Previene fijación de sesión
        $_SESSION['id_usuario'] = (int) $usuario['id_usuario'];
        $_SESSION['usuario_nombre'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
        $_SESSION['usuario_rol'] = (int) $usuario['id_rol'];
        $_SESSION['usuario_vecindario'] = (int) $usuario['id_vecindario'];
        unset($_SESSION['login_fallos'], $_SESSION['login_espera_hasta']);
    }

    // Revoca acceso si una cuenta fue desactivada o cambió de rol.
    public static function verificarVigencia(): void
    {
        if (!self::estaConectado()) return;
        require_once __DIR__ . '/../../config/database.php';
        $db = Database::getConnection();
        $stmt = $db->prepare('SELECT id_usuario, nombres, apellidos, id_rol, id_vecindario, estado FROM usuarios WHERE id_usuario = ? LIMIT 1');
        $stmt->execute([self::id()]);
        $usuario = $stmt->fetch();
        if (!$usuario || $usuario['estado'] !== 'Activo') {
            self::cerrar();
            session_start();
            $_SESSION['flash_error'] = 'La cuenta ya no está activa. Contacta al administrador.';
            header('Location: index.php?controller=Auth&action=login');
            exit;
        }
        $_SESSION['usuario_nombre'] = $usuario['nombres'] . ' ' . $usuario['apellidos'];
        $_SESSION['usuario_rol'] = (int) $usuario['id_rol'];
        $_SESSION['usuario_vecindario'] = (int) $usuario['id_vecindario'];
    }

    public static function cerrar(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    public static function requerir(): void
    {
        if (!self::estaConectado()) {
            header('Location: index.php?controller=Auth&action=login&redir=1');
            exit;
        }
    }

    public static function requerirRol(int ...$roles): void
    {
        self::requerir();
        if (!self::tieneRol(...$roles)) {
            http_response_code(403);
            require __DIR__ . '/../views/errors/403.php';
            exit;
        }
    }
}
