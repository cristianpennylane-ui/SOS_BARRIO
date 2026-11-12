<section class="panel-cabecera panel-cabecera-serenazgo">
  <div><span class="panel-etiqueta">OPERACIONES · SERENAZGO</span><h1>Centro de incidentes</h1>
  <p>Hola, <?= htmlspecialchars(Auth::nombre(), ENT_QUOTES, 'UTF-8') ?>. Consulta tu vecindario y actualiza solo tus asignaciones autorizadas.</p></div>
  <a href="index.php?controller=Reporte&action=mapa" class="panel-boton"><i data-lucide="map"></i> Mapa de incidentes</a>
</section>
<?php if (!empty($mensaje)): ?><div class="mensaje-auth aviso" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="mensaje-auth error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (empty($agente)): ?>
<div class="panel-aviso-vinculo"><i data-lucide="shield-alert"></i><div><strong>Ficha de oficial pendiente</strong><p>Tu cuenta tiene rol Serenazgo, pero tu DNI no está asociado a la tabla de personal. El administrador debe completar tu ficha antes de asignar o atender incidentes.</p></div></div>
<?php else: ?>
<div class="panel-ficha-agente"><span><i data-lucide="badge-check"></i> Ficha oficial #<?= (int)$agente['id_serenazgo'] ?></span><span>Turno: <strong><?= htmlspecialchars($agente['turno'], ENT_QUOTES, 'UTF-8') ?></strong></span><span class="panel-tag <?= $agente['disponible'] ? 'panel-tag-estado' : 'panel-tag-alerta' ?>"><?= $agente['disponible'] ? 'Disponible' : 'No disponible' ?></span></div>
<?php endif; ?>
<section class="panel-metricas">
  <div class="panel-metrica"><span>Incidentes en mi zona</span><strong><?= (int)($resumen['total'] ?? 0) ?></strong><i data-lucide="map-pin"></i></div>
  <div class="panel-metrica"><span>Pendientes / asignados</span><strong><?= (int)($resumen['abiertos'] ?? 0) ?></strong><i data-lucide="clock-3"></i></div>
  <div class="panel-metrica panel-metrica-urgente"><span>Prioridad alta sin resolver</span><strong><?= (int)($resumen['urgentes'] ?? 0) ?></strong><i data-lucide="siren"></i></div>
</section>
<section class="panel-contenido"><div class="panel-seccion-head"><div><h2>Cola de trabajo</h2><p>Toma reportes pendientes y registra la atención de aquellos asignados a ti.</p></div></div>
<div class="panel-tabla-wrap"><table class="panel-tabla"><thead><tr><th>Incidente</th><th>Prioridad</th><th>Estado</th><th>Asignación</th><th>Acciones</th></tr></thead><tbody>
<?php if (!$reportes): ?><tr><td colspan="5" class="panel-sin-datos">No hay incidentes en tu zona.</td></tr><?php endif; ?>
<?php foreach ($reportes as $r): ?>
<tr><td><a class="panel-link" href="index.php?controller=Reporte&action=detalle&id=<?= (int)$r['id_reporte'] ?>">#<?= (int)$r['id_reporte'] ?> · <?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?></a><span class="panel-resumen-descripcion"><?= htmlspecialchars($r['descripcion'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td><span class="panel-tag <?= $r['prioridad']==='Alta'?'panel-tag-alerta':'panel-tag-neutral' ?>"><?= htmlspecialchars($r['prioridad'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td><span class="panel-tag panel-tag-estado"><?= htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td><?= empty($r['agente_asignado']) ? 'Sin asignar' : (((int)($agente['id_serenazgo'] ?? 0)===(int)$r['agente_asignado']) ? 'Asignado a mí' : 'Otro oficial') ?></td>
<td class="panel-acciones-agente">
<?php if (!empty($agente) && $agente['disponible'] && $r['estado']==='Pendiente' && empty($r['agente_asignado'])): ?>
<form method="POST" action="index.php?controller=Serenazgo&action=tomar"><?= Csrf::campo() ?><input type="hidden" name="id_reporte" value="<?= (int)$r['id_reporte'] ?>"><button class="panel-guardar" type="submit">Tomar caso</button></form>
<?php elseif (!empty($agente) && $r['estado']==='Asignado' && (int)($r['agente_asignado'] ?? 0)===(int)$agente['id_serenazgo']): ?>
<form class="panel-atender" method="POST" action="index.php?controller=Serenazgo&action=atender"><?= Csrf::campo() ?><input type="hidden" name="id_reporte" value="<?= (int)$r['id_reporte'] ?>"><input type="text" name="observaciones" minlength="4" maxlength="300" placeholder="Detalle de la atención" aria-label="Detalle de la atención para reporte <?= (int)$r['id_reporte'] ?>" required><button class="panel-guardar" type="submit">Marcar atendido</button></form>
<?php else: ?><span class="panel-accion-inactiva">Solo lectura</span><?php endif; ?></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
<p class="panel-aclaracion">Sistema de demostración académica. Las alertas no sustituyen los canales oficiales de emergencia.</p>
