-- ============================================================
-- SOS BARRIO - Sistema de Alerta y Seguridad Vecinal
-- Base de datos MySQL - Proyecto Universitario
-- ============================================================

CREATE DATABASE IF NOT EXISTS sos_barrio;
USE sos_barrio;

-- ============================================================
-- 0. LIMPIEZA PREVIA (por si vuelves a correr el script)
-- Borra solo las tablas/objetos de este proyecto, en orden
-- inverso de dependencia (hijas antes que padres) para no
-- romper las llaves foráneas.
-- ============================================================

DROP TABLE IF EXISTS bitacora_estados;
DROP TABLE IF EXISTS notificaciones;
DROP TABLE IF EXISTS calificacion_reporte;
DROP TABLE IF EXISTS comentarios_reporte;
DROP TABLE IF EXISTS patrullajes;
DROP TABLE IF EXISTS contactos_emergencia;
DROP TABLE IF EXISTS zonas_riesgo;
DROP TABLE IF EXISTS respuestas_reporte;
DROP TABLE IF EXISTS reportes_incidentes;
DROP TABLE IF EXISTS serenazgo;
DROP TABLE IF EXISTS tipos_incidente;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS vecindarios;
DROP TABLE IF EXISTS roles;

DROP TRIGGER IF EXISTS trg_notificar_reporte_alta;
DROP TRIGGER IF EXISTS trg_bitacora_cambio_estado;
DROP TRIGGER IF EXISTS trg_actualizar_zona_riesgo;
DROP TRIGGER IF EXISTS trg_validar_disponibilidad_serenazgo;

DROP FUNCTION IF EXISTS fn_incidentes_por_zona_mes;
DROP FUNCTION IF EXISTS fn_tiempo_respuesta_promedio;

DROP PROCEDURE IF EXISTS sp_asignar_serenazgo;
DROP PROCEDURE IF EXISTS sp_cerrar_reporte;

-- ============================================================
-- 1. TABLAS
-- ============================================================

-- 1. Roles
CREATE TABLE roles (
    id_rol INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(30) NOT NULL UNIQUE
);

-- 2. Vecindarios (juntas vecinales / zonas administrativas)
CREATE TABLE vecindarios (
    id_vecindario INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(80) NOT NULL,
    distrito VARCHAR(60) NOT NULL,
    provincia VARCHAR(60) NOT NULL DEFAULT 'Lima',
    departamento VARCHAR(60) NOT NULL DEFAULT 'Lima'
);

-- 3. Usuarios (vecinos)
CREATE TABLE usuarios (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(60) NOT NULL,
    apellidos VARCHAR(60) NOT NULL,
    dni CHAR(8) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    telefono VARCHAR(15),
    direccion VARCHAR(150),
    id_vecindario INT NOT NULL,
    id_rol INT NOT NULL DEFAULT 2,
    fecha_registro DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Activo','Inactivo') DEFAULT 'Activo',
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario),
    FOREIGN KEY (id_rol) REFERENCES roles(id_rol)
);

-- 4. Tipos de incidente
CREATE TABLE tipos_incidente (
    id_tipo_incidente INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(60) NOT NULL,
    descripcion VARCHAR(200),
    nivel_prioridad ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media'
);

-- 5. Serenazgo / personal de seguridad
CREATE TABLE serenazgo (
    id_serenazgo INT AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(60) NOT NULL,
    apellidos VARCHAR(60) NOT NULL,
    dni CHAR(8) NOT NULL UNIQUE,
    telefono VARCHAR(15),
    id_vecindario INT NOT NULL,
    turno ENUM('Mañana','Tarde','Noche') NOT NULL,
    disponible TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario)
);

-- 6. Reportes de incidentes (tabla central)
CREATE TABLE reportes_incidentes (
    id_reporte INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    id_tipo_incidente INT NOT NULL,
    id_vecindario INT NOT NULL,
    descripcion VARCHAR(300) NOT NULL,
    latitud DECIMAL(10,7),
    longitud DECIMAL(10,7),
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    estado ENUM('Pendiente','Asignado','Atendido','Cerrado') DEFAULT 'Pendiente',
    prioridad ENUM('Baja','Media','Alta') NOT NULL DEFAULT 'Media',
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_tipo_incidente) REFERENCES tipos_incidente(id_tipo_incidente),
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario)
);

-- 7. Respuestas a reportes (asignación de serenazgo)
CREATE TABLE respuestas_reporte (
    id_respuesta INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    id_serenazgo INT NOT NULL,
    fecha_asignacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_llegada DATETIME NULL,
    fecha_cierre DATETIME NULL,
    tiempo_respuesta_min INT NULL,
    observaciones VARCHAR(300),
    FOREIGN KEY (id_reporte) REFERENCES reportes_incidentes(id_reporte),
    FOREIGN KEY (id_serenazgo) REFERENCES serenazgo(id_serenazgo)
);

-- 8. Zonas de riesgo (estadística por vecindario)
CREATE TABLE zonas_riesgo (
    id_zona INT AUTO_INCREMENT PRIMARY KEY,
    id_vecindario INT NOT NULL,
    nombre_zona VARCHAR(80) NOT NULL,
    nivel_riesgo ENUM('Bajo','Medio','Alto') DEFAULT 'Bajo',
    cantidad_incidentes_mes INT DEFAULT 0,
    ultima_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario)
);

-- 9. Contactos de emergencia del usuario
CREATE TABLE contactos_emergencia (
    id_contacto INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    nombre_contacto VARCHAR(100) NOT NULL,
    telefono VARCHAR(15) NOT NULL,
    relacion VARCHAR(40),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- 10. Patrullajes programados
CREATE TABLE patrullajes (
    id_patrullaje INT AUTO_INCREMENT PRIMARY KEY,
    id_serenazgo INT NOT NULL,
    id_vecindario INT NOT NULL,
    fecha DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fin TIME NOT NULL,
    ruta_descripcion VARCHAR(200),
    FOREIGN KEY (id_serenazgo) REFERENCES serenazgo(id_serenazgo),
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario)
);

-- 11. Comentarios sobre un reporte (otros vecinos aportan info)
CREATE TABLE comentarios_reporte (
    id_comentario INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    id_usuario INT NOT NULL,
    comentario VARCHAR(300) NOT NULL,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reportes_incidentes(id_reporte),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- 12. Calificación del servicio de un reporte cerrado
CREATE TABLE calificacion_reporte (
    id_calificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL UNIQUE,
    id_usuario INT NOT NULL,
    calificacion TINYINT NOT NULL CHECK (calificacion BETWEEN 1 AND 5),
    comentario VARCHAR(200),
    FOREIGN KEY (id_reporte) REFERENCES reportes_incidentes(id_reporte),
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- 13. Notificaciones
CREATE TABLE notificaciones (
    id_notificacion INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NULL,
    id_serenazgo INT NULL,
    mensaje VARCHAR(250) NOT NULL,
    tipo VARCHAR(40) DEFAULT 'General',
    leido TINYINT(1) DEFAULT 0,
    fecha_hora DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario),
    FOREIGN KEY (id_serenazgo) REFERENCES serenazgo(id_serenazgo)
);

-- 14. Bitácora de cambios de estado (auditoría, la llenan los triggers)
CREATE TABLE bitacora_estados (
    id_bitacora INT AUTO_INCREMENT PRIMARY KEY,
    id_reporte INT NOT NULL,
    estado_anterior VARCHAR(20),
    estado_nuevo VARCHAR(20),
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_reporte) REFERENCES reportes_incidentes(id_reporte)
);

-- ============================================================
-- 2. DATOS BASE (catálogos mínimos)
-- ============================================================

INSERT INTO roles (nombre) VALUES ('Administrador'), ('Vecino'), ('Serenazgo');

INSERT INTO vecindarios (nombre, distrito) VALUES
('Junta Vecinal Los Olivos Central', 'Los Olivos'),
('Junta Vecinal San Martín', 'San Martín de Porres');

INSERT INTO tipos_incidente (nombre, descripcion, nivel_prioridad) VALUES
('Robo/Asalto', 'Robo o asalto en curso o reciente', 'Alta'),
('Disturbios', 'Peleas, ruidos molestos, aglomeraciones', 'Media'),
('Sospechoso', 'Persona o vehículo sospechoso', 'Media'),
('Accidente de tránsito', 'Choque o atropello', 'Alta'),
('Infraestructura', 'Poste caído, fuga de agua, alumbrado', 'Baja');

-- ------------------------------------------------------------
-- Usuarios de prueba (vecinos + 1 admin)
-- OJO: ReporteController::store() usa id_usuario = 1 como usuario
-- demo cuando no hay sesión iniciada, así que el usuario #1 debe
-- existir siempre. La contraseña de todos es "123456" (hash bcrypt,
-- compatible con password_verify()).
-- ------------------------------------------------------------
INSERT INTO usuarios (nombres, apellidos, dni, email, password, telefono, direccion, id_vecindario, id_rol) VALUES
('Juan',    'Pérez Ramos',    '10203040', 'juan.perez@example.com',    '$2b$12$ZmPwahr89k66QHaPFoYVGO0bKCHHh/z2q0zwlAJAayNHYXrZVUBIi', '999111222', 'Jr. Las Flores 123', 1, 2),
('María',   'Gómez Torres',   '10203041', 'maria.gomez@example.com',   '$2b$12$ZmPwahr89k66QHaPFoYVGO0bKCHHh/z2q0zwlAJAayNHYXrZVUBIi', '999111223', 'Av. Los Álamos 456', 2, 2),
('Admin',   'Sistema',        '10203042', 'admin@sosbarrio.com',       '$2b$12$ZmPwahr89k66QHaPFoYVGO0bKCHHh/z2q0zwlAJAayNHYXrZVUBIi', '999111224', 'Oficina central',    1, 1);

-- ------------------------------------------------------------
-- Personal de serenazgo de prueba (necesario para que
-- sp_asignar_serenazgo y el trigger de notificación de reportes
-- "Alta" tengan a quién asignar/notificar)
-- ------------------------------------------------------------
INSERT INTO serenazgo (nombres, apellidos, dni, telefono, id_vecindario, turno, disponible) VALUES
('Carlos', 'Ramírez Soto',  '20304050', '988111222', 1, 'Mañana', 1),
('Luis',   'Fernández Paz', '20304051', '988111223', 1, 'Noche',  1),
('Rosa',   'Quispe Mamani', '20304052', '988111224', 2, 'Tarde',  1);

-- ------------------------------------------------------------
-- Zonas de riesgo iniciales (una por vecindario), para que el
-- trigger trg_actualizar_zona_riesgo tenga una fila que actualizar
-- ------------------------------------------------------------
INSERT INTO zonas_riesgo (id_vecindario, nombre_zona, nivel_riesgo, cantidad_incidentes_mes) VALUES
(1, 'Los Olivos Central', 'Bajo', 0),
(2, 'San Martín',         'Bajo', 0);

-- ============================================================
-- 3. TRIGGERS
-- ============================================================

DELIMITER //

-- Trigger 1: al crear un reporte de prioridad Alta, notificar automáticamente
-- a todo el serenazgo disponible de ese vecindario.
CREATE TRIGGER trg_notificar_reporte_alta
AFTER INSERT ON reportes_incidentes
FOR EACH ROW
BEGIN
    IF NEW.prioridad = 'Alta' THEN
        INSERT INTO notificaciones (id_serenazgo, mensaje, tipo)
        SELECT s.id_serenazgo,
               CONCAT('Nuevo reporte URGENTE #', NEW.id_reporte, ' en su zona: ', NEW.descripcion),
               'Alerta'
        FROM serenazgo s
        WHERE s.id_vecindario = NEW.id_vecindario AND s.disponible = 1;
    END IF;
END//

-- Trigger 2: registrar en bitácora cada cambio de estado de un reporte
CREATE TRIGGER trg_bitacora_cambio_estado
AFTER UPDATE ON reportes_incidentes
FOR EACH ROW
BEGIN
    IF OLD.estado <> NEW.estado THEN
        INSERT INTO bitacora_estados (id_reporte, estado_anterior, estado_nuevo)
        VALUES (NEW.id_reporte, OLD.estado, NEW.estado);
    END IF;
END//

-- Trigger 3: al insertar un reporte, incrementar el contador de incidentes
-- del mes en la zona de riesgo correspondiente (si existe una zona registrada)
CREATE TRIGGER trg_actualizar_zona_riesgo
AFTER INSERT ON reportes_incidentes
FOR EACH ROW
BEGIN
    UPDATE zonas_riesgo
    SET cantidad_incidentes_mes = cantidad_incidentes_mes + 1,
        ultima_actualizacion = NOW(),
        nivel_riesgo = CASE
            WHEN cantidad_incidentes_mes + 1 >= 15 THEN 'Alto'
            WHEN cantidad_incidentes_mes + 1 >= 6 THEN 'Medio'
            ELSE 'Bajo'
        END
    WHERE id_vecindario = NEW.id_vecindario;
END//

-- Trigger 4: impedir que un serenazgo sea asignado si ya no está disponible
CREATE TRIGGER trg_validar_disponibilidad_serenazgo
BEFORE INSERT ON respuestas_reporte
FOR EACH ROW
BEGIN
    DECLARE v_disponible TINYINT;
    SELECT disponible INTO v_disponible FROM serenazgo WHERE id_serenazgo = NEW.id_serenazgo;
    IF v_disponible = 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'El personal de serenazgo seleccionado no está disponible.';
    END IF;
END//

DELIMITER ;

-- ============================================================
-- 4. FUNCIONES
-- ============================================================

DELIMITER //

-- Función 1: cantidad de incidentes de un vecindario en un mes/año dado
CREATE FUNCTION fn_incidentes_por_zona_mes(
    p_id_vecindario INT, p_mes INT, p_anio INT
) RETURNS INT
DETERMINISTIC
BEGIN
    DECLARE v_total INT;
    SELECT COUNT(*) INTO v_total
    FROM reportes_incidentes
    WHERE id_vecindario = p_id_vecindario
      AND MONTH(fecha_hora) = p_mes
      AND YEAR(fecha_hora) = p_anio;
    RETURN v_total;
END//

-- Función 2: tiempo de respuesta promedio (en minutos) del serenazgo de un vecindario
CREATE FUNCTION fn_tiempo_respuesta_promedio(p_id_vecindario INT) RETURNS DECIMAL(10,2)
DETERMINISTIC
BEGIN
    DECLARE v_promedio DECIMAL(10,2);
    SELECT AVG(rr.tiempo_respuesta_min) INTO v_promedio
    FROM respuestas_reporte rr
    INNER JOIN reportes_incidentes ri ON ri.id_reporte = rr.id_reporte
    WHERE ri.id_vecindario = p_id_vecindario
      AND rr.tiempo_respuesta_min IS NOT NULL;
    RETURN IFNULL(v_promedio, 0);
END//

DELIMITER ;

-- ============================================================
-- 5. PROCEDIMIENTOS ALMACENADOS
-- ============================================================

DELIMITER //

-- Procedimiento 1: asignar automáticamente al serenazgo disponible más "libre"
-- (con menos reportes activos) del vecindario del reporte
CREATE PROCEDURE sp_asignar_serenazgo(IN p_id_reporte INT)
BEGIN
    DECLARE v_id_vecindario INT;
    DECLARE v_id_serenazgo INT;

    SELECT id_vecindario INTO v_id_vecindario
    FROM reportes_incidentes WHERE id_reporte = p_id_reporte;

    SELECT s.id_serenazgo INTO v_id_serenazgo
    FROM serenazgo s
    LEFT JOIN respuestas_reporte rr ON rr.id_serenazgo = s.id_serenazgo AND rr.fecha_cierre IS NULL
    WHERE s.id_vecindario = v_id_vecindario AND s.disponible = 1
    GROUP BY s.id_serenazgo
    ORDER BY COUNT(rr.id_respuesta) ASC
    LIMIT 1;

    IF v_id_serenazgo IS NOT NULL THEN
        INSERT INTO respuestas_reporte (id_reporte, id_serenazgo)
        VALUES (p_id_reporte, v_id_serenazgo);

        UPDATE reportes_incidentes SET estado = 'Asignado' WHERE id_reporte = p_id_reporte;
    END IF;
END//

-- Procedimiento 2: cerrar un reporte, calculando el tiempo de respuesta total
CREATE PROCEDURE sp_cerrar_reporte(
    IN p_id_reporte INT,
    IN p_id_respuesta INT,
    IN p_observaciones VARCHAR(300)
)
BEGIN
    UPDATE respuestas_reporte
    SET fecha_cierre = NOW(),
        observaciones = p_observaciones,
        tiempo_respuesta_min = TIMESTAMPDIFF(MINUTE, fecha_asignacion, NOW())
    WHERE id_respuesta = p_id_respuesta;

    UPDATE reportes_incidentes
    SET estado = 'Cerrado'
    WHERE id_reporte = p_id_reporte;
END//

DELIMITER ;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================
