<?php
require_once __DIR__ . '/../core/Model.php';

class PanelModel extends Model
{
    public function vecino(int $id): array
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(estado IN ('Pendiente','Asignado')) AS abiertos, SUM(estado='Cerrado') AS cerrados FROM reportes_incidentes WHERE id_usuario = ?");
        $stmt->execute([$id]);
        $resumen = $stmt->fetch();
        $stmt = $this->db->prepare("SELECT r.id_reporte, r.descripcion, r.estado, r.prioridad, r.fecha_hora, t.nombre AS tipo FROM reportes_incidentes r INNER JOIN tipos_incidente t ON t.id_tipo_incidente=r.id_tipo_incidente WHERE r.id_usuario=? ORDER BY r.fecha_hora DESC LIMIT 8");
        $stmt->execute([$id]);
        return ['resumen' => $resumen, 'reportes' => $stmt->fetchAll()];
    }

    public function serenazgo(int $zona, int $usuarioId): array
    {
        require_once __DIR__.'/SerenazgoModel.php';
        $agente = (new SerenazgoModel())->agentePorUsuario($usuarioId);
        $stmt = $this->db->prepare("SELECT COUNT(*) AS total, SUM(estado IN ('Pendiente','Asignado')) AS abiertos, SUM(prioridad='Alta' AND estado NOT IN ('Cerrado','Atendido')) AS urgentes FROM reportes_incidentes WHERE id_vecindario=?");
        $stmt->execute([$zona]);
        $resumen = $stmt->fetch();
        $stmt = $this->db->prepare("SELECT r.id_reporte, r.descripcion, r.estado, r.prioridad, r.fecha_hora, t.nombre AS tipo,
           (SELECT rr.id_serenazgo FROM respuestas_reporte rr WHERE rr.id_reporte=r.id_reporte AND rr.fecha_cierre IS NULL ORDER BY rr.id_respuesta DESC LIMIT 1) AS agente_asignado
           FROM reportes_incidentes r INNER JOIN tipos_incidente t ON t.id_tipo_incidente=r.id_tipo_incidente WHERE r.id_vecindario=? ORDER BY FIELD(r.estado,'Pendiente','Asignado','Atendido','Cerrado'), FIELD(r.prioridad,'Alta','Media','Baja'), r.fecha_hora DESC LIMIT 30");
        $stmt->execute([$zona]);
        return ['resumen' => $resumen, 'reportes' => $stmt->fetchAll(), 'agente' => $agente];
    }

    public function administrador(): array
    {
        $usuarios = $this->db->query("SELECT COUNT(*) AS total, SUM(estado='Activo') AS activos, SUM(id_rol=3) AS agentes FROM usuarios")->fetch();
        $resumen = $this->db->query("SELECT COUNT(*) AS total, SUM(estado IN ('Pendiente','Asignado')) AS abiertos, SUM(prioridad='Alta' AND estado NOT IN ('Cerrado','Atendido')) AS urgentes FROM reportes_incidentes")->fetch();
        $reportes = $this->db->query("SELECT r.id_reporte, r.descripcion, r.estado, r.prioridad, r.fecha_hora, t.nombre AS tipo FROM reportes_incidentes r INNER JOIN tipos_incidente t ON t.id_tipo_incidente=r.id_tipo_incidente ORDER BY r.fecha_hora DESC LIMIT 10")->fetchAll();
        return ['usuarios' => $usuarios, 'resumen' => $resumen, 'reportes' => $reportes];
    }
}
