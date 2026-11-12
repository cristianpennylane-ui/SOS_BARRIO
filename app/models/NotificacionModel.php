<?php
require_once __DIR__ . '/../core/Model.php';

// ============================================================
// NotificacionModel: notificaciones para vecinos (tabla
// notificaciones). Antes solo la llenaba el trigger
// trg_notificar_reporte_alta para el serenazgo; ahora también
// se usa para avisar a un vecino cuando comentan su reporte o
// cuando su reporte se cierra.
// ============================================================

class NotificacionModel extends Model
{
    public function crear($idUsuario, $mensaje, $tipo = 'General')
    {
        $stmt = $this->db->prepare(
            "INSERT INTO notificaciones (id_usuario, mensaje, tipo) VALUES (:u, :m, :t)"
        );
        $stmt->execute([':u' => $idUsuario, ':m' => $mensaje, ':t' => $tipo]);
        return $this->db->lastInsertId();
    }

    public function getRecientes($idUsuario, $limite = 8)
    {
        $stmt = $this->db->prepare(
            "SELECT n.* FROM notificaciones n WHERE n.id_usuario = :u OR n.id_serenazgo =
            (SELECT s.id_serenazgo FROM serenazgo s INNER JOIN usuarios u ON u.dni=s.dni WHERE u.id_usuario=:uid AND u.id_rol=3 AND s.id_vecindario=u.id_vecindario LIMIT 1)
            ORDER BY n.fecha_hora DESC LIMIT " . (int) $limite
        );
        $stmt->execute([':u' => $idUsuario, ':uid' => $idUsuario]);
        return $stmt->fetchAll();
    }

    public function contarNoLeidas($idUsuario)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS n FROM notificaciones n WHERE n.leido=0 AND (n.id_usuario = :u OR n.id_serenazgo =
            (SELECT s.id_serenazgo FROM serenazgo s INNER JOIN usuarios u ON u.dni=s.dni WHERE u.id_usuario=:uid AND u.id_rol=3 AND s.id_vecindario=u.id_vecindario LIMIT 1))"
        );
        $stmt->execute([':u' => $idUsuario, ':uid' => $idUsuario]);
        return (int) $stmt->fetch()['n'];
    }

    public function marcarLeidas($idUsuario)
    {
        $stmt = $this->db->prepare("UPDATE notificaciones n SET n.leido=1 WHERE n.leido=0 AND (n.id_usuario=:u OR n.id_serenazgo =
            (SELECT s.id_serenazgo FROM serenazgo s INNER JOIN usuarios u ON u.dni=s.dni WHERE u.id_usuario=:uid AND u.id_rol=3 AND s.id_vecindario=u.id_vecindario LIMIT 1))");
        $stmt->execute([':u' => $idUsuario, ':uid' => $idUsuario]);
    }
}
