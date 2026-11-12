-- SOS BARRIO v4 — reparación no destructiva de triggers y asignación automática
-- Compatible con MySQL 8 / MariaDB (XAMPP). Importar tras el SQL principal y extensiones v2/v3.
-- NO borra tablas ni registros. Realiza copia de seguridad de la BD antes de importar.
USE sos_barrio;

-- 1) Alerta de prioridad alta: limitar a VARCHAR(250) para que una descripción
-- larga no rompa la inserción del reporte con "Data too long".
DROP TRIGGER IF EXISTS trg_notificar_reporte_alta;
DELIMITER $$
CREATE TRIGGER trg_notificar_reporte_alta
AFTER INSERT ON reportes_incidentes
FOR EACH ROW
BEGIN
  IF NEW.prioridad = 'Alta' THEN
    INSERT INTO notificaciones (id_serenazgo, mensaje, tipo)
    SELECT s.id_serenazgo,
      LEFT(CONCAT('Nuevo reporte urgente #', NEW.id_reporte, ' en tu zona: ', NEW.descripcion), 250),
      'Alerta'
    FROM serenazgo s
    WHERE s.id_vecindario = NEW.id_vecindario AND s.disponible=1;
  END IF;
END$$
DELIMITER ;

-- 2) Bitácora: registra exclusivamente cambios reales de estado.
DROP TRIGGER IF EXISTS trg_bitacora_cambio_estado;
DELIMITER $$
CREATE TRIGGER trg_bitacora_cambio_estado
AFTER UPDATE ON reportes_incidentes
FOR EACH ROW
BEGIN
  IF OLD.estado <> NEW.estado THEN
    INSERT INTO bitacora_estados (id_reporte, estado_anterior, estado_nuevo)
    VALUES (NEW.id_reporte, OLD.estado, NEW.estado);
  END IF;
END$$
DELIMITER ;

-- 3) Zona de riesgo: mismo mes/año -> sumar; nuevo mes -> reiniciar a 1.
-- El nivel se calcula ANTES de modificar el contador: evita desfase +1.
DROP TRIGGER IF EXISTS trg_actualizar_zona_riesgo;
DELIMITER $$
CREATE TRIGGER trg_actualizar_zona_riesgo
AFTER INSERT ON reportes_incidentes
FOR EACH ROW
BEGIN
  UPDATE zonas_riesgo z
  SET z.nivel_riesgo = CASE
      WHEN (IF(YEAR(z.ultima_actualizacion)=YEAR(NEW.fecha_hora)
               AND MONTH(z.ultima_actualizacion)=MONTH(NEW.fecha_hora),
               z.cantidad_incidentes_mes, 0) + 1) >= 15 THEN 'Alto'
      WHEN (IF(YEAR(z.ultima_actualizacion)=YEAR(NEW.fecha_hora)
               AND MONTH(z.ultima_actualizacion)=MONTH(NEW.fecha_hora),
               z.cantidad_incidentes_mes, 0) + 1) >= 6 THEN 'Medio'
      ELSE 'Bajo'
    END,
    z.cantidad_incidentes_mes = IF(YEAR(z.ultima_actualizacion)=YEAR(NEW.fecha_hora)
                                  AND MONTH(z.ultima_actualizacion)=MONTH(NEW.fecha_hora),
                                  z.cantidad_incidentes_mes, 0) + 1,
    z.ultima_actualizacion = NEW.fecha_hora
  WHERE z.id_vecindario = NEW.id_vecindario;
END$$
DELIMITER ;

-- 4) Impedir asignar a un agente inexistente o no disponible.
DROP TRIGGER IF EXISTS trg_validar_disponibilidad_serenazgo;
DELIMITER $$
CREATE TRIGGER trg_validar_disponibilidad_serenazgo
BEFORE INSERT ON respuestas_reporte
FOR EACH ROW
BEGIN
  DECLARE v_disponible TINYINT DEFAULT NULL;
  SELECT disponible INTO v_disponible FROM serenazgo
  WHERE id_serenazgo = NEW.id_serenazgo LIMIT 1;
  IF v_disponible IS NULL OR v_disponible = 0 THEN
    SIGNAL SQLSTATE '45000'
      SET MESSAGE_TEXT = 'El oficial no existe o no está disponible.';
  END IF;
END$$
DELIMITER ;

-- 5) Procedimiento de asignación: evita duplicar asignaciones abiertas.
-- Las asignaciones manuales de los oficiales también aplican controles en PHP.
DROP PROCEDURE IF EXISTS sp_asignar_serenazgo;
DELIMITER $$
CREATE PROCEDURE sp_asignar_serenazgo(IN p_id_reporte INT)
BEGIN
  DECLARE v_zona INT DEFAULT NULL;
  DECLARE v_estado VARCHAR(20) DEFAULT NULL;
  DECLARE v_agente INT DEFAULT NULL;
  SELECT id_vecindario, estado INTO v_zona, v_estado
    FROM reportes_incidentes WHERE id_reporte = p_id_reporte LIMIT 1;
  IF v_zona IS NOT NULL AND v_estado = 'Pendiente'
     AND NOT EXISTS (SELECT 1 FROM respuestas_reporte WHERE id_reporte = p_id_reporte AND fecha_cierre IS NULL) THEN
    SELECT s.id_serenazgo INTO v_agente
    FROM serenazgo s
    LEFT JOIN respuestas_reporte rr ON rr.id_serenazgo=s.id_serenazgo AND rr.fecha_cierre IS NULL
    WHERE s.id_vecindario=v_zona AND s.disponible=1
    GROUP BY s.id_serenazgo
    ORDER BY COUNT(rr.id_respuesta) ASC, s.id_serenazgo ASC
    LIMIT 1;
    IF v_agente IS NOT NULL THEN
      INSERT INTO respuestas_reporte (id_reporte, id_serenazgo) VALUES (p_id_reporte,v_agente);
      UPDATE reportes_incidentes SET estado='Asignado' WHERE id_reporte=p_id_reporte AND estado='Pendiente';
    END IF;
  END IF;
END$$
DELIMITER ;

-- Comprobar instalación:
-- SHOW TRIGGERS FROM sos_barrio;
-- SHOW PROCEDURE STATUS WHERE Db='sos_barrio';

-- 6) Cierre: conservar tiempo de llegada calculado por el oficial, si existe.
DROP PROCEDURE IF EXISTS sp_cerrar_reporte;
DELIMITER $$
CREATE PROCEDURE sp_cerrar_reporte(IN p_id_reporte INT, IN p_id_respuesta INT, IN p_observaciones VARCHAR(300))
BEGIN
  UPDATE respuestas_reporte
  SET fecha_cierre=NOW(), observaciones=COALESCE(NULLIF(p_observaciones,''),observaciones),
      tiempo_respuesta_min=COALESCE(tiempo_respuesta_min, TIMESTAMPDIFF(MINUTE,fecha_asignacion,NOW()))
  WHERE id_respuesta=p_id_respuesta AND id_reporte=p_id_reporte AND fecha_cierre IS NULL;
  UPDATE reportes_incidentes SET estado='Cerrado' WHERE id_reporte=p_id_reporte AND estado<>'Cerrado';
END$$
DELIMITER ;
