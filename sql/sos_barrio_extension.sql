-- ============================================================
-- SOS BARRIO - EXTENSIÓN (v2)
-- Ejecuta esto DESPUÉS de sos_barrio.sql. No reemplaza nada,
-- solo agrega columnas y tablas nuevas para:
--   1) Ubicaciones más específicas (coords de cada vecindario
--      + referencia textual de cada reporte)
--   2) Comunidad: grupos de WhatsApp/Telegram por vecindario
--      (usados para generar el QR de invitación)
-- ============================================================

USE sos_barrio;

-- ------------------------------------------------------------
-- 1. Ubicación específica
-- ------------------------------------------------------------

-- Coordenadas del centro de cada vecindario (para centrar el
-- mapa automáticamente al elegir el vecindario en el formulario)
ALTER TABLE vecindarios
    ADD COLUMN latitud  DECIMAL(10,7) NULL,
    ADD COLUMN longitud DECIMAL(10,7) NULL;

UPDATE vecindarios SET latitud = -11.9702, longitud = -77.0710 WHERE id_vecindario = 1; -- Los Olivos Central
UPDATE vecindarios SET latitud = -11.9887, longitud = -77.0854 WHERE id_vecindario = 2; -- San Martín de Porres

-- Referencia textual de la ubicación de un reporte (ej. "Cruce
-- Av. Los Álamos con Jr. Las Flores, a media cuadra del parque").
-- Se llena automáticamente con geocodificación inversa (Nominatim)
-- o manualmente si el vecino prefiere escribirla.
ALTER TABLE reportes_incidentes
    ADD COLUMN direccion_referencia VARCHAR(200) NULL AFTER longitud;

-- ------------------------------------------------------------
-- 2. Comunidad: grupos de WhatsApp / Telegram por vecindario
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS grupos_comunidad (
    id_grupo INT AUTO_INCREMENT PRIMARY KEY,
    id_vecindario INT NOT NULL,
    plataforma ENUM('WhatsApp','Telegram') NOT NULL,
    nombre_grupo VARCHAR(100) NOT NULL,
    url_invitacion VARCHAR(255) NOT NULL,
    descripcion VARCHAR(200) NULL,
    activo TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_vecindario) REFERENCES vecindarios(id_vecindario)
);

-- Reemplaza estas URLs por los links reales de tus grupos.
-- El QR de cada grupo se genera en el navegador a partir de esta URL,
-- así que basta con actualizar la fila para que el QR cambie solo.
INSERT INTO grupos_comunidad (id_vecindario, plataforma, nombre_grupo, url_invitacion, descripcion) VALUES
(1, 'WhatsApp', 'Vecinos Los Olivos Central', 'https://chat.whatsapp.com/EJEMPLO-LOS-OLIVOS', 'Alertas y coordinación diaria del vecindario'),
(2, 'WhatsApp', 'Vecinos San Martín',         'https://chat.whatsapp.com/EJEMPLO-SAN-MARTIN', 'Alertas y coordinación diaria del vecindario');

-- ============================================================
-- FIN DE LA EXTENSIÓN
-- ============================================================
