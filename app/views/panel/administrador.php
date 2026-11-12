<section class="panel-cabecera panel-cabecera-admin">
  <div><span class="panel-etiqueta">ADMINISTRACIÓN DEL SISTEMA</span><h1>Panel de control</h1>
  <p>Bienvenido, <?= htmlspecialchars(Auth::nombre(), ENT_QUOTES, 'UTF-8') ?>. Supervisa la actividad y controla el acceso a las cuentas.</p></div>
  <a href="index.php?controller=Admin&action=usuarios" class="panel-boton"><i data-lucide="users"></i> Gestionar usuarios</a>
</section>
<section class="panel-metricas">
  <a class="panel-metrica panel-metrica-solicitudes" href="index.php?controller=Admin&action=usuarios#solicitudes"><span>Solicitudes de acceso</span><strong><?= ($solicitudes_pendientes ?? null)===null ? '—' : (int)$solicitudes_pendientes ?></strong><i data-lucide="user-round-check"></i></a>
  <div class="panel-metrica"><span>Usuarios registrados</span><strong><?= (int)($usuarios['total'] ?? 0) ?></strong><i data-lucide="users"></i></div>
  <div class="panel-metrica"><span>Usuarios activos</span><strong><?= (int)($usuarios['activos'] ?? 0) ?></strong><i data-lucide="user-check"></i></div>
  <div class="panel-metrica"><span>Cuentas de serenazgo</span><strong><?= (int)($usuarios['agentes'] ?? 0) ?></strong><i data-lucide="shield"></i></div>
  <div class="panel-metrica"><span>Reportes totales</span><strong><?= (int)($resumen['total'] ?? 0) ?></strong><i data-lucide="files"></i></div>
  <div class="panel-metrica"><span>Pendientes / asignados</span><strong><?= (int)($resumen['abiertos'] ?? 0) ?></strong><i data-lucide="clock-3"></i></div>
  <div class="panel-metrica panel-metrica-urgente"><span>Alertas de prioridad alta</span><strong><?= (int)($resumen['urgentes'] ?? 0) ?></strong><i data-lucide="alert-triangle"></i></div>
</section>
<div class="panel-accesos">
  <a href="index.php?controller=Admin&action=usuarios"><i data-lucide="user-cog"></i><span><strong>Usuarios y roles</strong><small>Aprobar solicitudes, crear oficiales y controlar accesos</small></span></a>
  <a href="index.php?controller=Dashboard&action=index"><i data-lucide="chart-no-axes-combined"></i><span><strong>Estadísticas generales</strong><small>Reportes, tendencias y zonas</small></span></a>
  <a href="index.php?controller=Reporte&action=index"><i data-lucide="clipboard-list"></i><span><strong>Revisar reportes</strong><small>Gestionar incidentes y cierres</small></span></a>
</div>
<section class="panel-contenido"><div class="panel-seccion-head"><div><h2>Últimos reportes</h2><p>Actividad general del sistema.</p></div></div>
<?php require __DIR__ . '/_tabla_reportes.php'; ?></section>
