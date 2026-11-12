<h2>Reportar un incidente</h2>
<?php if (!empty($error)): ?><div class="mensaje-auth error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

<form id="form-crear-reporte" action="index.php?controller=Reporte&action=store" method="POST" class="formulario">
    <?= Csrf::campo() ?>

    <label for="id_tipo_incidente">Tipo de incidente</label>
    <select id="id_tipo_incidente" name="id_tipo_incidente" required>
        <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id_tipo_incidente'] ?>"><?= htmlspecialchars($t['nombre']) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="id_vecindario">Vecindario</label>
    <select id="id_vecindario" name="id_vecindario" required>
        <?php foreach ($vecindarios as $v): ?>
            <option value="<?= $v['id_vecindario'] ?>" data-lat="<?= $v['latitud'] ?>" data-lng="<?= $v['longitud'] ?>" data-nombre="<?= htmlspecialchars($v['nombre']) ?>">
                <?= htmlspecialchars($v['nombre']) ?> — <?= htmlspecialchars($v['distrito']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <p class="tiempo-estimado" id="tiempo-estimado"></p>

    <label>Ubicación exacta <span class="ayuda">— busca tu calle, toca el mapa, o usa tu ubicación actual</span></label>
    <div class="buscador-direccion">
        <input type="text" id="buscar-direccion" placeholder="Buscar calle, cruce o referencia…" autocomplete="off">
        <ul id="sugerencias-direccion" class="sugerencias-direccion"></ul>
    </div>
    <button type="button" id="btn-ubicacion" class="btn-ubicacion">📍 Usar mi ubicación actual</button>
    <div id="mapa-crear" class="mapa-caja"></div>
    <p class="mapa-nota">La dirección de referencia se completa automáticamente al marcar el punto (puedes editarla).</p>

    <input type="hidden" id="latitud" name="latitud">
    <input type="hidden" id="longitud" name="longitud">
    <input type="text" id="direccion_referencia" name="direccion_referencia" placeholder="Dirección de referencia (se completa sola al marcar el mapa)">

    <label for="descripcion">Descripción</label>
    <textarea id="descripcion" name="descripcion" rows="4" maxlength="300" required></textarea>
    <span class="contador-caracteres" id="contador-caracteres">0/300</span>

    <label for="prioridad">Prioridad</label>
    <select id="prioridad" name="prioridad" required>
        <option value="Baja">Baja</option>
        <option value="Media" selected>Media</option>
        <option value="Alta">Alta (asigna serenazgo automáticamente)</option>
    </select>
    <div class="aviso-alta" id="aviso-alta">
        ⚠️ Al marcar prioridad Alta, se notificará de inmediato a todo el serenazgo disponible de tu vecindario.
    </div>

    <button type="submit">Enviar reporte</button>
</form>
