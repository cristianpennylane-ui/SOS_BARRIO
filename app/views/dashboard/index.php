<h2>Dashboard</h2>

<div class="dashboard-encabezado">
    <p class="mapa-nota">Estadísticas en vivo del sistema, calculadas con funciones y procedimientos de la base de datos.</p>
    <button type="button" class="btn-ubicacion" id="btn-imprimir-dashboard"><i data-lucide="printer"></i> Exportar resumen</button>
</div>

<div class="stat-strip" id="dashboard-imprimible">
    <div class="stat-card">
        <span class="stat-num" data-contador="<?= (int)$resumen['total'] ?>">0</span>
        <span class="stat-label">Reportes totales</span>
    </div>
    <div class="stat-card urgente">
        <span class="stat-num" data-contador="<?= (int)$resumen['altos'] ?>">0</span>
        <span class="stat-label">Prioridad alta</span>
    </div>
    <div class="stat-card">
        <span class="stat-num" data-contador="<?= (int)$resumen['pendientes'] ?>">0</span>
        <span class="stat-label">Pendientes</span>
    </div>
    <div class="stat-card ok">
        <span class="stat-num" data-contador="<?= (int)$resumen['cerrados'] ?>">0</span>
        <span class="stat-label">Cerrados</span>
    </div>
    <div class="stat-card urgente">
        <span class="stat-num" data-contador="<?= (int)$resumen['panico'] ?>">0</span>
        <span class="stat-label">Alertas de pánico</span>
    </div>
    <div class="stat-card" id="tarjeta-calificacion">
        <span class="stat-num" id="valor-calificacion-prom">—</span>
        <span class="stat-label">Calificación promedio</span>
    </div>
</div>

<div id="dashboard-datos" class="grid-dashboard">
    <div class="tarjeta">
        <h3>Reportes por tipo de incidente</h3>
        <canvas id="grafico-tipo" height="220"></canvas>
    </div>
    <div class="tarjeta">
        <h3>Reportes por vecindario</h3>
        <canvas id="grafico-vecindario" height="220"></canvas>
    </div>
    <div class="tarjeta">
        <h3>Tendencia de los últimos 14 días</h3>
        <canvas id="grafico-tendencia" height="220"></canvas>
    </div>
    <div class="tarjeta">
        <h3>Zonas de riesgo</h3>
        <canvas id="grafico-riesgo" height="220"></canvas>
    </div>
    <div class="tarjeta">
        <h3>Indicadores del mes por vecindario</h3>
        <table class="tabla-indicadores">
            <thead>
                <tr><th>Vecindario</th><th>Incidentes (mes)</th><th>Tiempo resp. prom.</th></tr>
            </thead>
            <tbody id="tabla-indicadores-body">
                <tr><td colspan="3">Cargando…</td></tr>
            </tbody>
        </table>
        <p class="mapa-nota">Calculado con las funciones SQL fn_incidentes_por_zona_mes y fn_tiempo_respuesta_promedio.</p>
    </div>
</div>
