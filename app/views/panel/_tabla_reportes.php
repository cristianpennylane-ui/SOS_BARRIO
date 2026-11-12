<?php // Variables esperadas: $reportes (filtrados por el controlador según rol). ?>
<div class="panel-tabla-wrap">
<table class="panel-tabla">
  <thead><tr><th>Reporte</th><th>Incidente</th><th>Prioridad</th><th>Estado</th><th>Fecha</th><th></th></tr></thead>
  <tbody>
    <?php if (empty($reportes)): ?>
      <tr><td colspan="6" class="panel-sin-datos">No hay reportes para mostrar.</td></tr>
    <?php endif; ?>
    <?php foreach ($reportes as $r): ?>
      <tr>
        <td class="panel-id">#<?= (int)$r['id_reporte'] ?></td>
        <td><strong><?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?></strong><span class="panel-resumen-descripcion"><?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><span class="panel-tag <?= $r['prioridad'] === 'Alta' ? 'panel-tag-alerta' : 'panel-tag-neutral' ?>"><?= htmlspecialchars($r['prioridad'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><span class="panel-tag panel-tag-estado"><?= htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['fecha_hora'])), ENT_QUOTES, 'UTF-8') ?></td>
        <td><a class="panel-link" href="index.php?controller=Reporte&action=detalle&id=<?= (int)$r['id_reporte'] ?>">Ver detalle</a></td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div>
