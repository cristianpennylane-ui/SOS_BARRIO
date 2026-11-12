<?php
require_once __DIR__ . '/../core/Model.php';

// ============================================================
// UsuarioModel: login y registro de vecinos (DNI + contraseña)
// ============================================================

class UsuarioModel extends Model
{
    public function findByDni($dni)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE dni = :dni");
        $stmt->execute([':dni' => $dni]);
        return $stmt->fetch();
    }

    public function findByDniOEmail($identificador)
    {
        $stmt = $this->db->prepare("SELECT * FROM usuarios WHERE dni = :dni OR email = :email LIMIT 1");
        $stmt->execute([':dni' => $identificador, ':email' => $identificador]);
        return $stmt->fetch();
    }

    public function dniExiste($dni)
    {
        return (bool) $this->findByDni($dni);
    }

    public function emailExiste($email)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM usuarios WHERE email = :email");
        $stmt->execute([':email' => $email]);
        return (bool) $stmt->fetch();
    }

    public function crear($data)
    {
        $sql = "INSERT INTO usuarios (nombres, apellidos, dni, email, password, telefono, direccion, id_vecindario, id_rol)
                VALUES (:nombres, :apellidos, :dni, :email, :password, :telefono, :direccion, :id_vecindario, 2)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':nombres' => $data['nombres'],
            ':apellidos' => $data['apellidos'],
            ':dni' => $data['dni'],
            ':email' => $data['email'],
            ':password' => password_hash($data['password'], PASSWORD_DEFAULT),
            ':telefono' => $data['telefono'],
            ':direccion' => $data['direccion'],
            ':id_vecindario' => $data['id_vecindario'],
        ]);
        return $this->db->lastInsertId();
    }

    // Permite cambiar solo la contraseña de la cuenta autenticada.
    public function cambiarPassword(int $idUsuario, string $actual, string $nueva): bool
    {
        $stmt = $this->db->prepare('SELECT password FROM usuarios WHERE id_usuario=? LIMIT 1');
        $stmt->execute([$idUsuario]);
        $u = $stmt->fetch();
        if (!$u || !password_verify($actual, $u['password'])) return false;
        $stmt = $this->db->prepare('UPDATE usuarios SET password=? WHERE id_usuario=?');
        return $stmt->execute([password_hash($nueva, PASSWORD_DEFAULT), $idUsuario]);
    }

    public function getVecindarios()
    {
        return $this->db->query("SELECT * FROM vecindarios")->fetchAll();
    }

    // Usado por PerfilController::credencial() para armar la credencial
    // digital del vecino conectado
    public function getById($id)
    {
        $stmt = $this->db->prepare(
            "SELECT u.*, v.nombre AS vecindario, v.distrito
             FROM usuarios u
             INNER JOIN vecindarios v ON v.id_vecindario = u.id_vecindario
             WHERE u.id_usuario = :id"
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    // Datos mínimos y no sensibles para la página pública de
    // verificación (a la que apunta el QR de la credencial). No
    // expone email, teléfono, dirección ni contraseña.
    public function getVerificacionPublicaPorDni($dni)
    {
        $stmt = $this->db->prepare(
            "SELECT u.nombres, u.apellidos, u.estado, u.fecha_registro, u.id_rol,
                    v.nombre AS vecindario, v.distrito, r.nombre AS rol
             FROM usuarios u
             INNER JOIN vecindarios v ON v.id_vecindario = u.id_vecindario
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.dni = :dni"
        );
        $stmt->execute([':dni' => $dni]);
        return $stmt->fetch();
    }
}
