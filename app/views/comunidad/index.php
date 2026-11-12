<h2>Comunidad</h2>
<p class="mapa-nota">Únete al grupo de tu vecindario para recibir alertas al instante. Escanea el QR con tu celular o toca "Unirme".</p>

<?php if (empty($grupos)): ?>
    <div class="vacio">
        <p>Todavía no hay grupos de comunidad registrados.</p>
    </div>
<?php else: ?>
<div class="grid-comunidad">
    <?php foreach ($grupos as $g): ?>
        <div class="tarjeta-grupo">
            <span class="plataforma"><?= htmlspecialchars($g['plataforma']) ?></span>
            <h3><?= htmlspecialchars($g['vecindario']) ?></h3>
            <p class="descripcion-grupo"><?= htmlspecialchars($g['descripcion'] ?: $g['nombre_grupo']) ?></p>
            <div data-qr-url="<?= htmlspecialchars($g['url_invitacion']) ?>"></div>
            <div class="acciones-grupo">
                <a class="btn-unirse" href="<?= htmlspecialchars($g['url_invitacion']) ?>" target="_blank" rel="noopener">Unirme al grupo</a>
                <button type="button" class="btn-copiar" data-copiar="<?= htmlspecialchars($g['url_invitacion']) ?>">Copiar enlace</button>
                <button type="button" class="btn-copiar" data-descargar-qr="<?= htmlspecialchars($g['nombre_grupo']) ?>"><i data-lucide="download"></i> Descargar QR</button>
            </div>
        </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
