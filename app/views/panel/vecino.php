<section class="panel-cabecera">
  <div><span class="panel-etiqueta">CENTRO VECINAL</span><h1>Hola, <?= htmlspecialchars(Auth::nombre(), ENT_QUOTES, 'UTF-8') ?></h1>
  <p>Consulta tus avisos y mantente al tanto de las incidencias que reportaste.</p></div>
  <a href="index.php?controller=Reporte&action=create" class="panel-boton"><i data-lucide="plus"></i> Crear reporte</a>
</section>
<section class="panel-metricas">
  <div class="panel-metrica"><span>Mis reportes</span><strong><?= (int)($resumen['total'] ?? 0) ?></strong><i data-lucide="files"></i></div>
  <div class="panel-metrica"><span>Pendientes / asignados</span><strong><?= (int)($resumen['abiertos'] ?? 0) ?></strong><i data-lucide="clock-3"></i></div>
  <div class="panel-metrica"><span>Cerrados</span><strong><?= (int)($resumen['cerrados'] ?? 0) ?></strong><i data-lucide="check-circle-2"></i></div>
</section>
<section class="panel-contenido"><div class="panel-seccion-head"><div><h2>Mis reportes recientes</h2><p>Únicamente los reportes de tu cuenta.</p></div><a href="index.php?controller=Reporte&action=mapa" class="panel-link">Ver mapa público</a></div>
<?php require __DIR__ . '/_tabla_reportes.php'; ?></section>
