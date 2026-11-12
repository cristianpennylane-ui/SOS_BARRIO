<?php
// ============================================================
// Configuración de conexión a la Base de Datos (PDO)
// ============================================================

class Database
{
    private static $host = "localhost";
    private static $db_name = "sos_barrio";
    private static $username = "root";
    private static $password = ""; // cambia esto según tu XAMPP/config local
    private static $conn = null;

    public static function getConnection()
    {
        if (self::$conn === null) {
            try {
                self::$conn = new PDO(
                    "mysql:host=" . self::$host . ";dbname=" . self::$db_name . ";charset=utf8mb4",
                    self::$username,
                    self::$password
                );
                self::$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                self::$conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                error_log('[SOS Barrio] Falló la conexión a MySQL: ' . $e->getMessage());
                http_response_code(503);
                exit('No fue posible conectar con la base de datos. Comprueba MySQL y la configuración de SOS Barrio.');
            }
        }
        return self::$conn;
    }
}
