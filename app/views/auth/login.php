<div class="acceso-layout">
  <aside class="acceso-intro" aria-label="Presentación del sistema">
    <div class="acceso-marca"><span class="acceso-marca-icon"><i data-lucide="shield-check"></i></span><span>SOS BARRIO <small>PLATAFORMA DE SEGURIDAD VECINAL</small></span></div>
    <div class="acceso-intro-copy"><span class="acceso-kicker">PLATAFORMA DE GESTIÓN</span>
      <h1>Un barrio más <em>conectado</em> empieza aquí.</h1>
      <p>Una plataforma organizada para reportar incidentes, coordinar atenciones y administrar la comunidad desde un solo lugar.</p>
      <div class="acceso-funciones"><div><i data-lucide="radio-tower"></i><span>Reportes y seguimiento</span></div><div><i data-lucide="shield"></i><span>Gestión de serenazgo</span></div><div><i data-lucide="chart-no-axes-combined"></i><span>Control administrativo</span></div></div>
    </div>
    <div class="acceso-intro-pie"><span class="acceso-punto"></span>SOS BARRIO</div>
  </aside>
  <section class="acceso-principal" aria-labelledby="titulo-acceso">
    <div class="acceso-topline"><span class="acceso-kicker">ÁREA DE ACCESO</span><span class="acceso-seguro"><i data-lucide="lock-keyhole"></i> Acceso por rol</span></div>
    <h2 id="titulo-acceso">Bienvenido de nuevo</h2>
    <p class="acceso-subtitulo">Selecciona el tipo de panel al que deseas acceder e inicia sesión con tu cuenta autorizada.</p>
    <?php if (!empty($redir)): ?><div class="mensaje-auth aviso" role="status">Debes iniciar sesión para continuar.</div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="mensaje-auth error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
    <form action="index.php?controller=Auth&action=procesarLogin" method="POST" class="formulario formulario-acceso">
      <?= Csrf::campo() ?>
      <fieldset class="acceso-roles"><legend>¿A qué panel quieres entrar?</legend>
        <div class="acceso-roles-grid">
          <label class="acceso-rol"><input type="radio" name="rol_acceso" value="2" <?= ($rolSeleccionado ?? 2) === 2 ? 'checked' : '' ?> required><span class="acceso-rol-caja"><i data-lucide="house"></i><strong>Vecino</strong><small>Reportar y consultar</small></span></label>
          <label class="acceso-rol"><input type="radio" name="rol_acceso" value="3" <?= ($rolSeleccionado ?? 2) === 3 ? 'checked' : '' ?>><span class="acceso-rol-caja"><i data-lucide="shield"></i><strong>Oficial</strong><small>Gestionar incidentes</small></span></label>
          <label class="acceso-rol"><input type="radio" name="rol_acceso" value="1" <?= ($rolSeleccionado ?? 2) === 1 ? 'checked' : '' ?>><span class="acceso-rol-caja"><i data-lucide="settings-2"></i><strong>Admin</strong><small>Control del sistema</small></span></label>
        </div>
      </fieldset>
      <div class="acceso-input-grupo"><label for="identificador">DNI o correo electrónico</label><div class="acceso-input-icon"><i data-lucide="user-round"></i><input type="text" id="identificador" name="identificador" maxlength="100" autocomplete="username" placeholder="Ingresa tu DNI o correo" required autofocus></div></div>
      <div class="acceso-input-grupo"><label for="password">Contraseña</label><div class="acceso-input-icon"><i data-lucide="key-round"></i><input type="password" id="password" name="password" autocomplete="current-password" placeholder="Ingresa tu contraseña" required><button type="button" id="ver-password" class="acceso-mostrar" aria-label="Mostrar contraseña" aria-pressed="false"><i data-lucide="eye"></i></button></div></div>
      <button class="acceso-boton" type="submit">Entrar al panel <i data-lucide="arrow-right"></i></button>
    </form>
    <div class="acceso-footer">
      <p>¿Aún no tienes cuenta? <a id="acceso-registro-link" href="index.php?controller=Auth&action=registro&rol=<?= (int)$rolSeleccionado ?>">Crear cuenta de <span id="acceso-registro-rol">tu perfil</span></a></p>
      <div class="acceso-consejo"><i data-lucide="lightbulb"></i><span id="acceso-consejo-rol">Elige un rol. Tu cuenta debe estar autorizada para ingresar a ese panel.</span></div>
      <small><i data-lucide="lock" style="width:13px;height:13px;vertical-align:-2px"></i> Los roles de Oficial y Administrador requieren aprobación antes de acceder. Seleccionar un panel no concede permisos.</small>
    </div>
  </section>
</div>
