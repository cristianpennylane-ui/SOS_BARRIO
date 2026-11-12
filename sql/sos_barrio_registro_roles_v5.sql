-- SOS BARRIO v5: solicitudes de registro por rol (no destructivo).
-- Primero importar los SQL originales y sos_barrio_mejoras_v4.sql.
-- Exporta una copia de seguridad antes de cualquier migración.
USE sos_barrio;

CREATE TABLE IF NOT EXISTS solicitudes_acceso (
    id_solicitud INT AUTO_INCREMENT PRIMARY KEY,
    rol_solicitado INT NOT NULL,
    nombres VARCHAR(60) NOT NULL,
    apellidos VARCHAR(60) NOT NULL,
    dni CHAR(8) NOT NULL,
    email VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NULL,
    telefono VARCHAR(15) DEFAULT NULL,
    direccion VARCHAR(150) DEFAULT NULL,
    id_vecindario INT NOT NULL,
    turno ENUM('Mañana','Tarde','Noche') DEFAULT NULL,
    institucion VARCHAR(120) DEFAULT NULL,
    justificacion VARCHAR(500) DEFAULT NULL,
    estado ENUM('Pendiente','Aprobada','Rechazada') NOT NULL DEFAULT 'Pendiente',
    fecha_solicitud DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_revision DATETIME DEFAULT NULL,
    id_revisor INT DEFAULT NULL,
    CONSTRAINT fk_solicitud_rol FOREIGN KEY (rol_solicitado) REFERENCES roles(id_rol),
    CONSTRAINT fk_solicitud_zona FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario),
    CONSTRAINT fk_solicitud_revisor FOREIGN KEY (id_revisor) REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    INDEX idx_solicitudes_estado (estado, fecha_solicitud),
    INDEX idx_solicitudes_dni (dni, estado),
    INDEX idx_solicitudes_correo (email, estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Verificación: SHOW TABLES LIKE 'solicitudes_acceso';
