<h2><i data-lucide="scan-line"></i> Verificación de vecino</h2>

<?php if ($resultado): ?>
    <div class="verificacion-caja ok">
        <i data-lucide="badge-check" class="icono-modal ok"></i>
        <h3>Identidad verificada</h3>
        <p class="verificacion-nombre"><?= htmlspecialchars($resultado['nombres'] . ' ' . $resultado['apellidos']) ?></p>
        <p><?= htmlspecialchars($resultado['rol']) ?> · <?= htmlspecialchars($resultado['vecindario']) ?>, <?= htmlspecialchars($resultado['distrito']) ?></p>
        <p>Vecino registrado desde <?= htmlspecialchars(date('d/m/Y', strtotime($resultado['fecha_registro']))) ?></p>
        <span class="badge <?= $resultado['estado'] === 'Activo' ? 'badge-estado Cerrado' : 'badge-estado' ?>"><?= htmlspecialchars($resultado['estado']) ?></span>
    </div>
<?php else: ?>
    <div class="verificacion-caja error">
        <i data-lucide="x-circle" class="icono-modal error"></i>
        <h3>No se pudo verificar</h3>
        <p>No existe ningún vecino registrado con el DNI <?= htmlspecialchars($dni ?: '(vacío)') ?>. Verifica que el código QR sea el correcto.</p>
    </div>
<?php endif; ?>

<p style="margin-top:22px;"><a href="index.php?controller=Reporte&action=index">&larr; Ir a SOS Barrio</a></p>
