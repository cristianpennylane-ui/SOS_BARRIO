<?php
require_once __DIR__ . '/../core/Model.php';

// ============================================================
// ApiTokenModel: emisión y verificación de tokens Bearer
// para la API REST (pensada para una futura app móvil)
// ============================================================

class ApiTokenModel extends Model
{
    public function crear($idUsuario)
    {
        $token = bin2hex(random_bytes(32));
        $stmt = $this->db->prepare("INSERT INTO api_tokens (id_usuario, token) VALUES (:u, :t)");
        $stmt->execute([':u' => $idUsuario, ':t' => $token]);
        return $token;
    }

    // Devuelve el usuario dueño del token, o null si no es válido
    public function validar($token)
    {
        $stmt = $this->db->prepare(
            "SELECT u.* FROM api_tokens a
             INNER JOIN usuarios u ON u.id_usuario = a.id_usuario
             WHERE a.token = :t AND u.estado = 'Activo'"
        );
        $stmt->execute([':t' => $token]);
        $usuario = $stmt->fetch();

        if ($usuario) {
            $upd = $this->db->prepare("UPDATE api_tokens SET ultimo_uso = NOW() WHERE token = :t");
            $upd->execute([':t' => $token]);
        }
        return $usuario ?: null;
    }
}
