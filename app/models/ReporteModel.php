<?php
require_once __DIR__ . '/../core/Model.php';

// ============================================================
// ReporteModel: gestiona los reportes de incidentes
// ============================================================

class ReporteModel extends Model
{
    // Lista todos los reportes con datos relacionados (join)
    public function getAll()
    {
        $sql = "SELECT r.id_reporte, r.descripcion, r.fecha_hora, r.estado, r.prioridad, r.origen,
                       u.nombres AS usuario_nombres, u.apellidos AS usuario_apellidos,
                       t.nombre AS tipo_incidente,
                       v.nombre AS vecindario
                FROM reportes_incidentes r
                INNER JOIN usuarios u ON u.id_usuario = r.id_usuario
                INNER JOIN tipos_incidente t ON t.id_tipo_incidente = r.id_tipo_incidente
                INNER JOIN vecindarios v ON v.id_vecindario = r.id_vecindario
                ORDER BY r.fecha_hora DESC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Obtiene un reporte por id, junto con sus comentarios
    public function getById($id)
    {
        $stmt = $this->db->prepare("SELECT * FROM reportes_incidentes WHERE id_reporte = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    public function getComentarios($idReporte)
    {
        $stmt = $this->db->prepare(
            "SELECT c.comentario, c.fecha_hora, u.nombres, u.apellidos
             FROM comentarios_reporte c
             INNER JOIN usuarios u ON u.id_usuario = c.id_usuario
             WHERE c.id_reporte = :id ORDER BY c.fecha_hora ASC"
        );
        $stmt->execute([':id' => $idReporte]);
        return $stmt->fetchAll();
    }

    // Crea un nuevo reporte. El trigger trg_notificar_reporte_alta y
    // trg_actualizar_zona_riesgo se disparan automáticamente en la BD.
    public function create($data)
    {
        $sql = "INSERT INTO reportes_incidentes
                (id_usuario, id_tipo_incidente, id_vecindario, descripcion, latitud, longitud, direccion_referencia, prioridad, origen)
                VALUES (:id_usuario, :id_tipo_incidente, :id_vecindario, :descripcion, :latitud, :longitud, :direccion_referencia, :prioridad, :origen)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id_usuario' => $data['id_usuario'],
            ':id_tipo_incidente' => $data['id_tipo_incidente'],
            ':id_vecindario' => $data['id_vecindario'],
            ':descripcion' => $data['descripcion'],
            ':latitud' => $data['latitud'] ?: null,
            ':longitud' => $data['longitud'] ?: null,
            ':direccion_referencia' => $data['direccion_referencia'] ?: null,
            ':prioridad' => $data['prioridad'],
            ':origen' => $data['origen'] ?? 'Web',
        ]);
        return $this->db->lastInsertId();
    }

    // Id del tipo de incidente "Emergencia SOS / Pánico" (sembrado en
    // sos_barrio_extension_v3.sql). Se busca por nombre para no depender
    // de un id fijo.
    public function getTipoPanicoId()
    {
        $stmt = $this->db->query("SELECT id_tipo_incidente FROM tipos_incidente WHERE nombre = 'Emergencia SOS / Pánico' LIMIT 1");
        $fila = $stmt->fetch();
        return $fila ? $fila['id_tipo_incidente'] : null;
    }

    // Crea un reporte de pánico: prioridad Alta fija, descripción
    // automática y asignación inmediata de serenazgo.
    public function crearReportePanico($idUsuario, $idVecindario, $lat, $lng, $origen = 'Web')
    {
        $idTipo = $this->getTipoPanicoId();
        if (!$idTipo) {
            throw new Exception('Falta importar sql/sos_barrio_extension_v3.sql (tipo de incidente de pánico no encontrado).');
        }
        $idReporte = $this->create([
            'id_usuario' => $idUsuario,
            'id_tipo_incidente' => $idTipo,
            'id_vecindario' => $idVecindario,
            'descripcion' => 'Alerta enviada con el botón de pánico. Requiere atención inmediata.',
            'latitud' => $lat,
            'longitud' => $lng,
            'direccion_referencia' => null,
            'prioridad' => 'Alta',
            'origen' => $origen,
        ]);
        $this->asignarSerenazgo($idReporte);
        return $idReporte;
    }

    // Llama al procedimiento sp_asignar_serenazgo para asignar automáticamente
    public function asignarSerenazgo($idReporte)
    {
        $stmt = $this->db->prepare("CALL sp_asignar_serenazgo(:id)");
        $stmt->execute([':id' => $idReporte]);
    }

    // Llama al procedimiento sp_cerrar_reporte
    public function cerrarReporte($idReporte, $idRespuesta, $observaciones)
    {
        $stmt = $this->db->prepare("CALL sp_cerrar_reporte(:idReporte, :idRespuesta, :obs)");
        $stmt->execute([
            ':idReporte' => $idReporte,
            ':idRespuesta' => $idRespuesta,
            ':obs' => $observaciones,
        ]);
    }

    // Usa la función fn_incidentes_por_zona_mes para el dashboard
    public function incidentesPorZonaMes($idVecindario, $mes, $anio)
    {
        $stmt = $this->db->prepare("SELECT fn_incidentes_por_zona_mes(:v, :m, :a) AS total");
        $stmt->execute([':v' => $idVecindario, ':m' => $mes, ':a' => $anio]);
        return $stmt->fetch()['total'];
    }

    // Usa la función fn_tiempo_respuesta_promedio para el dashboard
    public function tiempoRespuestaPromedio($idVecindario)
    {
        $stmt = $this->db->prepare("SELECT fn_tiempo_respuesta_promedio(:v) AS promedio");
        $stmt->execute([':v' => $idVecindario]);
        return $stmt->fetch()['promedio'];
    }

    public function getTiposIncidente()
    {
        return $this->db->query("SELECT * FROM tipos_incidente")->fetchAll();
    }

    public function getVecindarios()
    {
        return $this->db->query("SELECT * FROM vecindarios")->fetchAll();
    }

    // Reportes con coordenadas válidas, para pintar el mapa general
    public function getReportesConUbicacion()
    {
        $sql = "SELECT r.id_reporte, r.descripcion, r.latitud, r.longitud, r.direccion_referencia,
                       r.prioridad, r.estado, r.fecha_hora,
                       t.nombre AS tipo_incidente, v.nombre AS vecindario
                FROM reportes_incidentes r
                INNER JOIN tipos_incidente t ON t.id_tipo_incidente = r.id_tipo_incidente
                INNER JOIN vecindarios v ON v.id_vecindario = r.id_vecindario
                WHERE r.latitud IS NOT NULL AND r.longitud IS NOT NULL
                ORDER BY r.fecha_hora DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // Agrega un comentario (usado por el endpoint AJAX)
    public function addComentario($idReporte, $idUsuario, $texto)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO comentarios_reporte (id_reporte, id_usuario, comentario) VALUES (:r, :u, :c)"
        );
        $stmt->execute([':r' => $idReporte, ':u' => $idUsuario, ':c' => $texto]);
        return $this->db->lastInsertId();
    }

    // Totales por prioridad / estado / tipo, para las tarjetas y gráficos del dashboard
    public function getResumenGeneral()
    {
        $total = $this->db->query("SELECT COUNT(*) AS n FROM reportes_incidentes")->fetch()['n'];
        $pendientes = $this->db->query("SELECT COUNT(*) AS n FROM reportes_incidentes WHERE estado = 'Pendiente'")->fetch()['n'];
        $altos = $this->db->query("SELECT COUNT(*) AS n FROM reportes_incidentes WHERE prioridad = 'Alta'")->fetch()['n'];
        $cerrados = $this->db->query("SELECT COUNT(*) AS n FROM reportes_incidentes WHERE estado = 'Cerrado'")->fetch()['n'];
        $panico = $this->db->query("SELECT COUNT(*) AS n FROM reportes_incidentes WHERE origen = 'Panico'")->fetch()['n'];
        return compact('total', 'pendientes', 'altos', 'cerrados', 'panico');
    }

    public function getReportesPorTipo()
    {
        $sql = "SELECT t.nombre AS tipo, COUNT(*) AS total
                FROM reportes_incidentes r
                INNER JOIN tipos_incidente t ON t.id_tipo_incidente = r.id_tipo_incidente
                GROUP BY t.nombre ORDER BY total DESC";
        return $this->db->query($sql)->fetchAll();
    }

    public function getReportesPorVecindario()
    {
        $sql = "SELECT v.nombre AS vecindario, COUNT(*) AS total
                FROM reportes_incidentes r
                INNER JOIN vecindarios v ON v.id_vecindario = r.id_vecindario
                GROUP BY v.nombre ORDER BY total DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // Usa las funciones fn_incidentes_por_zona_mes / fn_tiempo_respuesta_promedio
    // ya definidas en la BD, una fila por vecindario
    public function getIndicadoresPorVecindario()
    {
        $vecindarios = $this->getVecindarios();
        $mes = (int) date('n');
        $anio = (int) date('Y');
        $out = [];
        foreach ($vecindarios as $v) {
            $out[] = [
                'vecindario' => $v['nombre'],
                'incidentes_mes' => $this->incidentesPorZonaMes($v['id_vecindario'], $mes, $anio),
                'tiempo_respuesta_prom' => $this->tiempoRespuestaPromedio($v['id_vecindario']),
            ];
        }
        return $out;
    }

    // ------------------------------------------------------------
    // Zonas de riesgo (tabla zonas_riesgo, actualizada automáticamente
    // por el trigger trg_actualizar_zona_riesgo cada vez que se crea
    // un reporte). Antes no se mostraba en ninguna vista.
    // ------------------------------------------------------------
    public function getZonasRiesgo()
    {
        $sql = "SELECT z.id_zona, z.nombre_zona, z.nivel_riesgo, z.cantidad_incidentes_mes, z.ultima_actualizacion,
                       v.id_vecindario, v.nombre AS vecindario, v.latitud, v.longitud
                FROM zonas_riesgo z
                INNER JOIN vecindarios v ON v.id_vecindario = z.id_vecindario
                ORDER BY FIELD(z.nivel_riesgo, 'Alto','Medio','Bajo'), z.cantidad_incidentes_mes DESC";
        return $this->db->query($sql)->fetchAll();
    }

    // Serie de reportes de los últimos N días, para el gráfico de
    // tendencia del dashboard (dinamismo adicional con Chart.js)
    public function getReportesPorDia($dias = 14)
    {
        $sql = "SELECT DATE(fecha_hora) AS fecha, COUNT(*) AS total
                FROM reportes_incidentes
                WHERE fecha_hora >= DATE_SUB(CURDATE(), INTERVAL :dias DAY)
                GROUP BY DATE(fecha_hora)
                ORDER BY fecha ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':dias', (int) $dias, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    // ------------------------------------------------------------
    // Calificación del servicio (tabla calificacion_reporte). Solo
    // aplica a reportes Cerrados y solo puede calificar el vecino
    // que lo creó.
    // ------------------------------------------------------------
    public function getCalificacion($idReporte)
    {
        $stmt = $this->db->prepare("SELECT * FROM calificacion_reporte WHERE id_reporte = :id");
        $stmt->execute([':id' => $idReporte]);
        return $stmt->fetch();
    }

    public function calificar($idReporte, $idUsuario, $estrellas, $comentario)
    {
        $stmt = $this->db->prepare(
            "INSERT INTO calificacion_reporte (id_reporte, id_usuario, calificacion, comentario)
             VALUES (:r, :u, :c, :com)"
        );
        return $stmt->execute([
            ':r' => $idReporte,
            ':u' => $idUsuario,
            ':c' => $estrellas,
            ':com' => $comentario !== '' ? $comentario : null,
        ]);
    }

    // Promedio de calificación de todo el sistema (para el dashboard)
    public function getPromedioCalificacion()
    {
        $fila = $this->db->query("SELECT AVG(calificacion) AS promedio, COUNT(*) AS n FROM calificacion_reporte")->fetch();
        return ['promedio' => $fila['promedio'] ? round($fila['promedio'], 1) : null, 'n' => (int) $fila['n']];
    }

    // ------------------------------------------------------------
    // Cierre de reportes (usa sp_cerrar_reporte). Antes existía el
    // procedimiento en la BD pero ningún controlador lo invocaba.
    // ------------------------------------------------------------
    // Cierra un reporte que nunca llegó a tener una respuesta asignada
    // (típico de prioridad Baja/Media). No usa sp_cerrar_reporte porque
    // ese procedimiento opera sobre una fila de respuestas_reporte.
    public function cerrarSinAsignacion($idReporte)
    {
        $stmt = $this->db->prepare("UPDATE reportes_incidentes SET estado = 'Cerrado' WHERE id_reporte = :id");
        $stmt->execute([':id' => $idReporte]);
    }

    public function getUltimaRespuestaAbierta($idReporte)
    {
        $stmt = $this->db->prepare(
            "SELECT id_respuesta FROM respuestas_reporte
             WHERE id_reporte = :id AND fecha_cierre IS NULL
             ORDER BY fecha_asignacion DESC LIMIT 1"
        );
        $stmt->execute([':id' => $idReporte]);
        $fila = $stmt->fetch();
        return $fila ? $fila['id_respuesta'] : null;
    }
}
