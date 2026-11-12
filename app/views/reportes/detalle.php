<?php if ($reporte): ?>
<?php
    $urlBaseReporte = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
    $urlReporte = rtrim($urlBaseReporte, '/') . '/index.php?controller=Reporte&action=detalle&id=' . $reporte['id_reporte'];
?>
<div class="detalle-encabezado">
    <h2>Reporte #<?= $reporte['id_reporte'] ?></h2>
    <div class="detalle-acciones-top">
        <button type="button" class="btn-secundario btn-compartir" id="btn-compartir-reporte"><i data-lucide="qr-code"></i> Compartir / QR</button>
        <?php if (class_exists('Auth') && Auth::esAdmin() && $reporte['estado'] !== 'Cerrado'): ?>
            <form action="index.php?controller=Reporte&action=cerrar" method="POST" class="form-cerrar-reporte" onsubmit="return confirm('¿Cerrar este reporte como atendido?');">
                <?= Csrf::campo() ?>
                <input type="hidden" name="id_reporte" value="<?= $reporte['id_reporte'] ?>">
                <button type="submit" class="btn-panico-confirmar"><i data-lucide="check-circle-2"></i> Cerrar reporte</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Panel de QR / compartir (oculto hasta que se pulsa el botón) -->
<div class="panel-compartir" id="panel-compartir-reporte" hidden>
    <div class="panel-compartir-qr" id="qr-reporte" data-qr-url="<?= htmlspecialchars($urlReporte) ?>"></div>
    <div class="panel-compartir-info">
        <p>Escanea este código para abrir el reporte #<?= $reporte['id_reporte'] ?> directamente desde otro celular, o compártelo por WhatsApp con tus vecinos.</p>
        <div class="acciones-grupo">
            <button type="button" class="btn-copiar" id="btn-copiar-link-reporte" data-copiar="<?= htmlspecialchars($urlReporte) ?>">Copiar enlace</button>
            <button type="button" class="btn-copiar" id="btn-imprimir-reporte"><i data-lucide="printer"></i> Imprimir ficha</button>
        </div>
    </div>
</div>

<div class="detalle-reporte" id="ficha-imprimible">
    <p><strong>Descripción:</strong> <?= htmlspecialchars($reporte['descripcion']) ?></p>
    <?php if (!empty($reporte['direccion_referencia'])): ?>
        <p><strong>Ubicación de referencia:</strong> <?= htmlspecialchars($reporte['direccion_referencia']) ?></p>
    <?php endif; ?>

    <div class="fila">
        <div>
            <div class="campo-label">Fecha</div>
            <div class="campo-valor"><?= htmlspecialchars($reporte['fecha_hora']) ?></div>
        </div>
        <div>
            <div class="campo-label">Prioridad</div>
            <div class="campo-valor">
                <span class="badge badge-<?= strtolower($reporte['prioridad']) ?>"><?= htmlspecialchars($reporte['prioridad']) ?></span>
            </div>
        </div>
        <div>
            <div class="campo-label">Estado</div>
            <div class="campo-valor">
                <span class="badge badge-estado <?= htmlspecialchars($reporte['estado']) ?>"><?= htmlspecialchars($reporte['estado']) ?></span>
            </div>
        </div>
    </div>

    <?php
        $pasosEstado = ['Pendiente', 'Asignado', 'Atendido', 'Cerrado'];
        $pasoActual = array_search($reporte['estado'], $pasosEstado);
    ?>
    <div class="timeline-estado">
        <?php foreach ($pasosEstado as $i => $paso): ?>
            <div class="paso-timeline <?= $i <= $pasoActual ? 'completado' : '' ?> <?= $i === $pasoActual ? 'actual' : '' ?>">
                <span class="punto-timeline"></span>
                <span class="texto-timeline"><?= $paso ?></span>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (!empty($reporte['latitud']) && !empty($reporte['longitud'])): ?>
        <div id="mapa-detalle" class="mapa-caja"
             data-lat="<?= $reporte['latitud'] ?>" data-lng="<?= $reporte['longitud'] ?>"></div>
        <script>
            // Mapa de un solo punto: se pinta aquí mismo porque solo aplica a esta vista
            document.addEventListener('DOMContentLoaded', () => {
                const el = document.getElementById('mapa-detalle');
                if (el && typeof L !== 'undefined') {
                    const lat = parseFloat(el.dataset.lat), lng = parseFloat(el.dataset.lng);
                    const mapa = L.map('mapa-detalle').setView([lat, lng], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '&copy; colaboradores de OpenStreetMap',
                    }).addTo(mapa);
                    L.marker([lat, lng]).addTo(mapa);
                }
            });
        </script>
    <?php endif; ?>
</div>

<h3>Comentarios de vecinos</h3>
<ul class="comentarios" id="lista-comentarios">
    <?php foreach ($comentarios as $c): ?>
        <li><strong><?= htmlspecialchars($c['nombres']) ?>:</strong> <?= htmlspecialchars($c['comentario']) ?>
            <small><?= htmlspecialchars($c['fecha_hora']) ?></small>
        </li>
    <?php endforeach; ?>
    <?php if (empty($comentarios)): ?>
        <li class="sin-comentarios">Aún no hay comentarios.</li>
    <?php endif; ?>
</ul>

<form id="form-comentario" class="form-comentario" data-id-reporte="<?= $reporte['id_reporte'] ?>">
    <textarea placeholder="Aporta información sobre este incidente…" maxlength="300" required></textarea>
    <button type="submit">Comentar</button>
</form>
<p class="estado-envio" id="estado-envio"></p>

<?php if ($reporte['estado'] === 'Cerrado'): ?>
<section class="tarjeta panel-calificacion" id="panel-calificacion" data-id-reporte="<?= $reporte['id_reporte'] ?>" data-es-dueno="<?= (class_exists('Auth') && Auth::estaConectado() && (int) Auth::id() === (int) $reporte['id_usuario']) ? '1' : '0' ?>">
    <h3><i data-lucide="star"></i> Calificación del servicio</h3>
    <?php if (!empty($calificacion)): ?>
        <div class="estrellas-mostrar" data-valor="<?= (int) $calificacion['calificacion'] ?>"></div>
        <?php if (!empty($calificacion['comentario'])): ?>
            <p class="comentario-calificacion">"<?= htmlspecialchars($calificacion['comentario']) ?>"</p>
        <?php endif; ?>
        <p class="mapa-nota">Gracias por calificar la atención de este caso.</p>
    <?php else: ?>
        <div id="form-calificacion-envoltura">
            <p class="mapa-nota" id="texto-calificacion-ayuda">¿Cómo fue la atención de este reporte?</p>
            <div class="estrellas-input" id="estrellas-input">
                <button type="button" class="estrella" data-valor="1"><i data-lucide="star"></i></button>
                <button type="button" class="estrella" data-valor="2"><i data-lucide="star"></i></button>
                <button type="button" class="estrella" data-valor="3"><i data-lucide="star"></i></button>
                <button type="button" class="estrella" data-valor="4"><i data-lucide="star"></i></button>
                <button type="button" class="estrella" data-valor="5"><i data-lucide="star"></i></button>
            </div>
            <textarea id="comentario-calificacion" maxlength="200" placeholder="Cuéntanos brevemente cómo fue la atención (opcional)"></textarea>
            <button type="button" class="btn-ver" id="btn-enviar-calificacion">Enviar calificación</button>
            <p class="estado-envio" id="estado-calificacion"></p>
        </div>
    <?php endif; ?>
</section>
<?php endif; ?>

<p style="margin-top:22px;"><a href="index.php?controller=Reporte&action=index">&larr; Volver al listado</a></p>

<?php else: ?>
    <div class="vacio">
        <p>Reporte no encontrado.</p>
        <a class="btn-ver" href="index.php?controller=Reporte&action=index">Volver al listado</a>
    </div>
<?php endif; ?>
