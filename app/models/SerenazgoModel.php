<?php
require_once __DIR__ . '/../core/Model.php';
class SerenazgoModel extends Model
{
    public function agentePorUsuario(int $idUsuario): ?array
    {
        $st = $this->db->prepare("SELECT s.* FROM serenazgo s INNER JOIN usuarios u ON s.dni = u.dni WHERE u.id_usuario=? AND u.id_rol=3 AND u.estado='Activo' AND s.id_vecindario=u.id_vecindario LIMIT 1");
        $st->execute([$idUsuario]);
        $r = $st->fetch();
        return $r ?: null;
    }

    // La transacción bloquea el reporte: dos agentes no pueden tomarlo al mismo tiempo.
    public function tomar(int $idUsuario, int $idReporte): bool
    {
        $agente = $this->agentePorUsuario($idUsuario);
        if (!$agente || !(int) $agente['disponible']) return false;
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT id_usuario, id_vecindario, estado FROM reportes_incidentes WHERE id_reporte=? FOR UPDATE');
            $st->execute([$idReporte]);
            $r = $st->fetch();
            if (!$r || (int)$r['id_vecindario'] !== (int)$agente['id_vecindario'] || $r['estado'] !== 'Pendiente') {
                $this->db->rollBack(); return false;
            }
            $st = $this->db->prepare('SELECT id_respuesta FROM respuestas_reporte WHERE id_reporte=? AND fecha_cierre IS NULL LIMIT 1');
            $st->execute([$idReporte]);
            if ($st->fetch()) { $this->db->rollBack(); return false; }
            $st = $this->db->prepare('INSERT INTO respuestas_reporte (id_reporte,id_serenazgo) VALUES (?,?)');
            $st->execute([$idReporte, $agente['id_serenazgo']]);
            $st = $this->db->prepare("UPDATE reportes_incidentes SET estado='Asignado' WHERE id_reporte=?");
            $st->execute([$idReporte]);
            $this->notificar($r['id_usuario'], 'Tu reporte #'.$idReporte.' fue asignado a un oficial.', 'Asignacion');
            $this->db->commit(); return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function atender(int $idUsuario, int $idReporte, string $observacion): bool
    {
        $agente = $this->agentePorUsuario($idUsuario);
        if (!$agente) return false;
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare('SELECT id_usuario,id_vecindario,estado FROM reportes_incidentes WHERE id_reporte=? FOR UPDATE');
            $st->execute([$idReporte]); $r = $st->fetch();
            if (!$r || (int)$r['id_vecindario'] !== (int)$agente['id_vecindario'] || $r['estado'] !== 'Asignado') {
                $this->db->rollBack(); return false;
            }
            $st = $this->db->prepare("UPDATE respuestas_reporte SET fecha_llegada = COALESCE(fecha_llegada, NOW()), tiempo_respuesta_min=TIMESTAMPDIFF(MINUTE, fecha_asignacion, COALESCE(fecha_llegada, NOW())), observaciones=? WHERE id_reporte=? AND id_serenazgo=? AND fecha_cierre IS NULL");
            $st->execute([$observacion, $idReporte, $agente['id_serenazgo']]);
            if ($st->rowCount() === 0) { $this->db->rollBack(); return false; }
            $st = $this->db->prepare("UPDATE reportes_incidentes SET estado='Atendido' WHERE id_reporte=? AND estado='Asignado'");
            $st->execute([$idReporte]);
            $this->notificar($r['id_usuario'], 'Tu reporte #'.$idReporte.' fue marcado como atendido. El administrador validará el cierre.', 'Atencion');
            $this->db->commit(); return true;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    private function notificar(int $idUsuario, string $mensaje, string $tipo): void
    {
        $st = $this->db->prepare('INSERT INTO notificaciones (id_usuario,mensaje,tipo) VALUES (?,?,?)');
        $st->execute([$idUsuario, $mensaje, $tipo]);
    }
}
