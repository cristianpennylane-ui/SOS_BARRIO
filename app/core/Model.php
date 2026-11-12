<?php
require_once __DIR__ . '/../../config/database.php';

// ============================================================
// Model base: todos los modelos heredan la conexión PDO
// ============================================================

class Model
{
    protected $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }
}
