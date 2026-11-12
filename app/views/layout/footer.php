    </main>

    <div id="contenedor-toasts" class="contenedor-toasts" aria-live="polite"></div>

    <button id="btn-arriba" class="btn-arriba" title="Volver arriba" aria-label="Volver arriba" hidden>
        <i data-lucide="arrow-up"></i>
    </button>

    <?php if (Auth::tieneRol(Auth::VECINO)): ?>
    <!-- Botón de pánico: fijo en toda la app para el usuario conectado -->
    <button id="btn-panico" class="btn-panico" title="Botón de pánico" data-id-vecindario="<?= (int) Auth::idVecindario() ?>">
        <i data-lucide="siren"></i>
    </button>

    <div id="modal-panico" class="modal-panico">
        <div class="modal-panico-caja">
            <div class="paso-panico" id="paso-confirmar">
                <i data-lucide="alert-triangle" class="icono-modal"></i>
                <h3>¿Necesitas ayuda urgente?</h3>
                <p>Se registrará una alerta de prioridad Alta en esta plataforma de demostración. No reemplaza una llamada a los servicios de emergencia.</p>
                <div class="modal-panico-acciones">
                    <button id="btn-cancelar-panico" class="btn-secundario">Cancelar</button>
                    <button id="btn-confirmar-panico" class="btn-panico-confirmar">Sí, enviar alerta SOS</button>
                </div>
            </div>
            <div class="paso-panico" id="paso-enviando" style="display:none;">
                <div class="spinner"></div>
                <p>Obteniendo tu ubicación y notificando al serenazgo…</p>
            </div>
            <div class="paso-panico" id="paso-exito" style="display:none;">
                <i data-lucide="check-circle-2" class="icono-modal ok"></i>
                <h3>Alerta enviada</h3>
                <p id="texto-exito-panico"></p>
                <div class="modal-panico-acciones">
                    <a id="link-ver-reporte" class="btn-panico-confirmar" href="#">Ver reporte</a>
                    <button id="btn-cerrar-panico" class="btn-secundario">Cerrar</button>
                </div>
            </div>
            <div class="paso-panico" id="paso-error" style="display:none;">
                <i data-lucide="x-circle" class="icono-modal error"></i>
                <p id="texto-error-panico"></p>
                <button id="btn-reintentar-panico" class="btn-secundario">Cerrar</button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <footer class="footer">
        <p>Proyecto académico - Sistema de Alerta y Seguridad Vecinal &copy; <?= date('Y') ?></p>
    </footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/[email protected]/build/qrcode.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="js/app.js?v=4"></script>
    <script src="js/login.js?v=5"></script>
    <script src="js/registro.js?v=5"></script>
    <script src="js/admin.js?v=4"></script>
    <script>if (window.lucide) lucide.createIcons();</script>
</body>
</html>
