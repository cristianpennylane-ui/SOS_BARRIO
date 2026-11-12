-- ============================================================
-- SOS BARRIO - EXTENSIÓN v3
-- Ejecuta esto DESPUÉS de sos_barrio.sql y sos_barrio_extension.sql.
-- Agrega:
--   1) Tokens de API (para login DNI+contraseña desde app móvil)
--   2) Tipo de incidente "Emergencia SOS / Pánico"
--   3) Columna "origen" en reportes (Web / Panico / App)
-- ============================================================

USE sos_barrio;

-- ------------------------------------------------------------
-- 1. Tokens de la API REST (ver app/core/Api.php)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS api_tokens (
    id_token INT AUTO_INCREMENT PRIMARY KEY,
    id_usuario INT NOT NULL,
    token CHAR(64) NOT NULL UNIQUE,
    creado_en DATETIME DEFAULT CURRENT_TIMESTAMP,
    ultimo_uso DATETIME NULL,
    FOREIGN KEY (id_usuario) REFERENCES usuarios(id_usuario)
);

-- ------------------------------------------------------------
-- 2. Tipo de incidente especial para el botón de pánico
-- (INSERT idempotente: si ya existe no lo duplica al re-correr el script)
-- ------------------------------------------------------------
INSERT INTO tipos_incidente (nombre, descripcion, nivel_prioridad)
SELECT 'Emergencia SOS / Pánico', 'Generado por el botón de pánico de un toque', 'Alta'
WHERE NOT EXISTS (
    SELECT 1 FROM tipos_incidente WHERE nombre = 'Emergencia SOS / Pánico'
);

-- ------------------------------------------------------------
-- 3. Origen del reporte (para diferenciar alertas de pánico
-- en el listado y el dashboard)
-- ------------------------------------------------------------
ALTER TABLE reportes_incidentes
    ADD COLUMN origen ENUM('Web','Panico','App') NOT NULL DEFAULT 'Web' AFTER prioridad;

-- ============================================================
-- FIN DE LA EXTENSIÓN v3
-- ============================================================
