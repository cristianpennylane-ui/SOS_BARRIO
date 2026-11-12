<?php
// URL absoluta relativa al mismo host, para que el QR funcione al
// escanearlo desde el celular de otra persona (serenazgo, vecino, etc.)
$urlBase = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$urlVerificacion = rtrim($urlBase, '/') . '/index.php?controller=Perfil&action=verificar&dni=' . urlencode($usuario['dni']);
?>
<h2><i data-lucide="id-card"></i> Mi credencial vecinal</h2>
<p class="mapa-nota">Muestra este QR al serenazgo o a un vecino para confirmar que perteneces a la junta vecinal. También puedes descargarla o imprimirla.</p>

<div class="credencial-envoltura">
    <div class="credencial-tarjeta" id="credencial-imprimir">
        <div class="credencial-franja"></div>
        <div class="credencial-encabezado">
            <span class="credencial-marca"><i data-lucide="shield-alert"></i> SOS BARRIO</span>
            <span class="credencial-tipo">Credencial vecinal</span>
        </div>

        <div class="credencial-cuerpo">
            <div class="credencial-avatar"><?= htmlspecialchars(mb_substr($usuario['nombres'], 0, 1) . mb_substr($usuario['apellidos'], 0, 1)) ?></div>
            <div class="credencial-datos">
                <h3><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></h3>
                <p><i data-lucide="badge-check"></i> DNI <?= htmlspecialchars($usuario['dni']) ?></p>
                <p><i data-lucide="map-pin"></i> <?= htmlspecialchars($usuario['vecindario']) ?>, <?= htmlspecialchars($usuario['distrito']) ?></p>
                <p><i data-lucide="calendar"></i> Vecino desde <?= htmlspecialchars(date('d/m/Y', strtotime($usuario['fecha_registro']))) ?></p>
                <span class="badge <?= $usuario['estado'] === 'Activo' ? 'badge-estado Cerrado' : 'badge-estado' ?>"><?= htmlspecialchars($usuario['estado']) ?></span>
            </div>
            <div class="credencial-qr" id="credencial-qr" data-qr-url="<?= htmlspecialchars($urlVerificacion) ?>"></div>
        </div>

        <div class="credencial-pie">
            <span>Escanea para verificar</span>
            <span class="credencial-id">#SB-<?= str_pad($usuario['id_usuario'], 5, '0', STR_PAD_LEFT) ?></span>
        </div>
    </div>

    <div class="credencial-acciones">
        <button type="button" class="btn-ubicacion" id="btn-descargar-credencial"><i data-lucide="download"></i> Descargar QR</button>
        <button type="button" class="btn-ubicacion" id="btn-imprimir-credencial"><i data-lucide="printer"></i> Imprimir credencial</button>
        <p class="mapa-nota">El QR no expone tu contraseña, correo ni teléfono: solo confirma tu nombre, vecindario y estado de socio.</p>
    </div>
</div>
