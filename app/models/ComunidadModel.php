<?php
require_once __DIR__ . '/../core/Model.php';

// ============================================================
// ComunidadModel: grupos de WhatsApp/Telegram por vecindario
// (tabla grupos_comunidad, ver sql/sos_barrio_extension.sql)
// ============================================================

class ComunidadModel extends Model
{
    public function getGruposPorVecindario()
    {
        $sql = "SELECT g.id_grupo, g.plataforma, g.nombre_grupo, g.url_invitacion, g.descripcion,
                       v.id_vecindario, v.nombre AS vecindario, v.distrito
                FROM grupos_comunidad g
                INNER JOIN vecindarios v ON v.id_vecindario = g.id_vecindario
                WHERE g.activo = 1
                ORDER BY v.nombre, g.plataforma";
        return $this->db->query($sql)->fetchAll();
    }
}
