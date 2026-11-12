<div class="stat-strip">
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
</div>

<!-- ===================== ¿Cómo funciona? ===================== -->
<section class="seccion-como-funciona">
    <h2>¿Cómo funciona SOS Barrio?</h2>
    <p class="subtitulo-seccion">Del reporte a la respuesta, en cuatro pasos automáticos.</p>
    <div class="pasos-funciona">
        <div class="paso-funciona">
            <div class="paso-icono"><i data-lucide="edit-3"></i><span class="paso-num">1</span></div>
            <h4>Reportas</h4>
            <p>Marcas el punto exacto en el mapa (o usas tu ubicación GPS) y describes lo que está pasando.</p>
        </div>
        <div class="paso-flecha"><i data-lucide="arrow-right"></i></div>
        <div class="paso-funciona">
            <div class="paso-icono"><i data-lucide="zap"></i><span class="paso-num">2</span></div>
            <h4>Se notifica solo</h4>
            <p>Si marcas prioridad Alta, un trigger de la base de datos avisa de inmediato a todo el serenazgo disponible de tu zona.</p>
        </div>
        <div class="paso-flecha"><i data-lucide="arrow-right"></i></div>
        <div class="paso-funciona">
            <div class="paso-icono"><i data-lucide="shield-check"></i><span class="paso-num">3</span></div>
            <h4>Serenazgo responde</h4>
            <p>Se asigna automáticamente al efectivo disponible con menos carga, y el estado pasa a Asignado → Atendido.</p>
        </div>
        <div class="paso-flecha"><i data-lucide="arrow-right"></i></div>
        <div class="paso-funciona">
            <div class="paso-icono"><i data-lucide="clipboard-check"></i><span class="paso-num">4</span></div>
            <h4>Se cierra y queda en el mapa</h4>
            <p>El caso se cierra, alimenta el dashboard y ayuda a identificar las zonas de mayor riesgo del barrio.</p>
        </div>
    </div>
</section>

<div class="grid-didactico">
    <!-- ===================== Encuesta de percepción ===================== -->
    <section class="tarjeta panel-encuesta" id="panel-encuesta">
        <h3><i data-lucide="bar-chart-2"></i> Encuesta de la semana</h3>
        <p class="pregunta-encuesta">¿Cómo sientes la seguridad de tu vecindario esta semana?</p>

        <div id="encuesta-opciones" class="encuesta-opciones">
            <button class="opcion-encuesta" data-opcion="Muy segura">😄 Muy segura</button>
            <button class="opcion-encuesta" data-opcion="Segura">🙂 Segura</button>
            <button class="opcion-encuesta" data-opcion="Regular">😐 Regular</button>
            <button class="opcion-encuesta" data-opcion="Insegura">😟 Insegura</button>
            <button class="opcion-encuesta" data-opcion="Muy insegura">😨 Muy insegura</button>
        </div>

        <div id="encuesta-resultados" class="encuesta-resultados" hidden>
            <div class="barra-encuesta" data-opcion="Muy segura"></div>
            <div class="barra-encuesta" data-opcion="Segura"></div>
            <div class="barra-encuesta" data-opcion="Regular"></div>
            <div class="barra-encuesta" data-opcion="Insegura"></div>
            <div class="barra-encuesta" data-opcion="Muy insegura"></div>
            <p class="encuesta-nota">Gracias por votar · <span id="encuesta-total"></span> vecinos participaron esta semana</p>
        </div>
        <p class="encuesta-disclaimer">Encuesta informal y anónima, guardada en tu navegador (se reinicia cada semana).</p>
    </section>

    <!-- ===================== Consejos de seguridad ===================== -->
    <section class="tarjeta panel-consejos">
        <h3><i data-lucide="lightbulb"></i> Consejo de seguridad</h3>
        <div id="carrusel-consejos" class="carrusel-consejos"></div>
        <div class="puntos-carrusel" id="puntos-carrusel"></div>
    </section>
</div>

<!-- ===================== Glosario de prioridades ===================== -->
<section class="tarjeta panel-glosario">
    <h3><i data-lucide="info"></i> ¿Qué significa cada prioridad?</h3>
    <div class="glosario-grid">
        <div class="glosario-item">
            <span class="badge badge-alta"><span class="punto-pulso"></span> Alta</span>
            <p>Riesgo inmediato para la integridad de alguien (asalto en curso, accidente). Notifica al serenazgo al instante.</p>
        </div>
        <div class="glosario-item">
            <span class="badge badge-media">Media</span>
            <p>Situación que necesita atención el mismo día, pero no pone vidas en riesgo inmediato (disturbios, sospechosos).</p>
        </div>
        <div class="glosario-item">
            <span class="badge badge-baja">Baja</span>
            <p>Incidencias de seguimiento, sin urgencia (infraestructura, alumbrado, reportes informativos).</p>
        </div>
    </div>
</section>

<h2>Reportes de incidentes</h2>

<div class="filtros">
    <select id="filtro-tipo">
        <option value="">Todos los tipos</option>
        <?php foreach (array_unique(array_column($reportes, 'tipo_incidente')) as $tipo): ?>
            <option value="<?= htmlspecialchars($tipo) ?>"><?= htmlspecialchars($tipo) ?></option>
        <?php endforeach; ?>
    </select>
    <select id="filtro-prioridad">
        <option value="">Toda prioridad</option>
        <option value="Alta">Alta</option>
        <option value="Media">Media</option>
        <option value="Baja">Baja</option>
    </select>
    <select id="filtro-estado">
        <option value="">Todo estado</option>
        <option value="Pendiente">Pendiente</option>
        <option value="Asignado">Asignado</option>
        <option value="Atendido">Atendido</option>
        <option value="Cerrado">Cerrado</option>
    </select>
    <input type="search" id="filtro-busqueda" placeholder="Buscar por descripción, vecindario…">
    <button type="button" class="btn-copiar" id="btn-exportar-csv"><i data-lucide="file-down"></i> Exportar CSV</button>
    <span class="contador-resultados" id="contador-resultados"></span>
</div>

<?php if (empty($reportes)): ?>
    <div class="vacio">
        <p>Todavía no hay reportes registrados.</p>
        <a class="btn-ver" href="index.php?controller=Reporte&action=create">Reportar el primer incidente</a>
    </div>
<?php else: ?>
<div class="tabla-wrap">
<table class="tabla" id="tabla-reportes">
    <thead>
        <tr>
            <th>#</th>
            <th>Tipo</th>
            <th>Vecindario</th>
            <th>Reportado por</th>
            <th>Fecha</th>
            <th>Prioridad</th>
            <th>Estado</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($reportes as $r): ?>
        <tr data-tipo="<?= htmlspecialchars($r['tipo_incidente']) ?>" data-prioridad="<?= htmlspecialchars($r['prioridad']) ?>" data-estado="<?= htmlspecialchars($r['estado']) ?>">
            <td class="id-reporte">#<?= $r['id_reporte'] ?><?php if ($r['origen'] === 'Panico'): ?> <span title="Enviado con el botón de pánico">🚨</span><?php endif; ?></td>
            <td><?= htmlspecialchars($r['tipo_incidente']) ?></td>
            <td><?= htmlspecialchars($r['vecindario']) ?></td>
            <td><?= htmlspecialchars($r['usuario_nombres'] . ' ' . $r['usuario_apellidos']) ?></td>
            <td><?= htmlspecialchars($r['fecha_hora']) ?></td>
            <td>
                <span class="badge badge-<?= strtolower($r['prioridad']) ?>">
                    <?php if ($r['prioridad'] === 'Alta'): ?><span class="punto-pulso"></span><?php endif; ?>
                    <?= htmlspecialchars($r['prioridad']) ?>
                </span>
            </td>
            <td><span class="badge badge-estado <?= htmlspecialchars($r['estado']) ?>"><?= htmlspecialchars($r['estado']) ?></span></td>
            <td>
                <a class="btn-ver" href="index.php?controller=Reporte&action=detalle&id=<?= $r['id_reporte'] ?>">Ver</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>

<!-- ===================== Preguntas frecuentes ===================== -->
<section class="tarjeta panel-faq">
    <h3><i data-lucide="help-circle"></i> Preguntas frecuentes</h3>
    <div class="acordeon-faq" id="acordeon-faq">
        <div class="item-faq">
            <button class="pregunta-faq">¿Quién ve mi reporte? <i data-lucide="chevron-down"></i></button>
            <div class="respuesta-faq"><p>Todos los vecinos registrados pueden ver el listado y el mapa de reportes (transparencia del barrio), y el serenazgo de tu zona recibe una notificación inmediata si es prioridad Alta.</p></div>
        </div>
        <div class="item-faq">
            <button class="pregunta-faq">¿Qué pasa si uso el botón de pánico por error? <i data-lucide="chevron-down"></i></button>
            <div class="respuesta-faq"><p>Se crea un reporte real de prioridad Alta y se notifica al serenazgo, así que solo debe usarse en una emergencia real. El modal de confirmación existe justamente para evitar toques accidentales.</p></div>
        </div>
        <div class="item-faq">
            <button class="pregunta-faq">¿Cómo se asigna automáticamente el serenazgo? <i data-lucide="chevron-down"></i></button>
            <div class="respuesta-faq"><p>El procedimiento <code>sp_asignar_serenazgo</code> busca, dentro de tu vecindario, al efectivo disponible con menos reportes activos asignados en ese momento.</p></div>
        </div>
        <div class="item-faq">
            <button class="pregunta-faq">¿La encuesta de seguridad es oficial? <i data-lucide="chevron-down"></i></button>
            <div class="respuesta-faq"><p>No, es una encuesta informal y anónima pensada para que el vecindario tenga una idea rápida de cómo se sienten los demás; no reemplaza a un reporte formal.</p></div>
        </div>
        <div class="item-faq">
            <button class="pregunta-faq">¿Puedo usar SOS Barrio desde el celular? <i data-lucide="chevron-down"></i></button>
            <div class="respuesta-faq"><p>Sí, el sitio es responsive, y además existe una API REST propia pensada para conectar en el futuro una app móvil nativa.</p></div>
        </div>
    </div>
</section>
