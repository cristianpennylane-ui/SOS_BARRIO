<?php
$h = static fn($valor) => htmlspecialchars((string)($valor ?? ''), ENT_QUOTES, 'UTF-8');
$anterior = static fn($clave) => $h($anteriores[$clave] ?? '');
$rolSeleccionado = (int)($rolSeleccionado ?? Auth::VECINO);
?>
<div class="registro-v5" data-rol-inicial="<?= $rolSeleccionado ?>">
  <aside class="registro-guia">
    <div class="registro-sello"><i data-lucide="shield-check"></i><span>SOS BARRIO <small>IDENTIDAD Y ACCESOS</small></span></div>
    <div class="registro-guia-centro">
      <span class="registro-eyebrow">NUEVAS CUENTAS</span>
      <h1>Un acceso para cada <em>responsabilidad.</em></h1>
      <p>Elige tu rol y completa el formulario correspondiente. Así protegemos el acceso a los reportes y las tareas operativas.</p>
      <div class="registro-guia-roles">
        <div><span class="guia-icon vecino"><i data-lucide="home"></i></span><span><b>Vecino</b><small>Cuenta inmediata para tu comunidad</small></span></div>
        <div><span class="guia-icon oficial"><i data-lucide="shield"></i></span><span><b>Oficial</b><small>Solicita autorización institucional</small></span></div>
        <div><span class="guia-icon admin"><i data-lucide="settings-2"></i></span><span><b>Administrador</b><small>Acceso aprobado por otro administrador</small></span></div>
      </div>
    </div>
    <div class="registro-guia-pie"><i data-lucide="info"></i></div>
  </aside>
  <section class="registro-contenido">
    <div class="registro-cabecera"><span class="registro-etapa">01 / 03 · ELEGIR PERFIL</span><a href="index.php?controller=Auth&action=login"><i data-lucide="arrow-left"></i> Iniciar sesión</a></div>
    <h2>Crea tu cuenta</h2>
    <p class="registro-lead">Selecciona cómo utilizarás SOS Barrio. Cada perfil tiene permisos diferentes.</p>

    <?php if (!empty($mensaje)): ?><div class="registro-alerta exito" role="status"><i data-lucide="check-circle-2"></i><span><?= $h($mensaje) ?></span></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="registro-alerta error" role="alert"><i data-lucide="alert-circle"></i><span><?= $h($error) ?></span></div><?php endif; ?>

    <form action="index.php?controller=Auth&action=procesarRegistro" method="POST" id="form-registro-roles" class="registro-form">
      <?= Csrf::campo() ?>
      <fieldset class="registro-perfiles">
        <legend>Elige el tipo de cuenta</legend>
        <div class="registro-perfiles-grid">
          <label class="registro-perfil perfil-vecino"><input type="radio" name="rol_registro" value="2" <?= $rolSeleccionado===2?'checked':'' ?> required><span class="registro-perfil-contenido"><i data-lucide="house"></i><b>Vecino</b><small>Participa y reporta</small></span></label>
          <label class="registro-perfil perfil-oficial"><input type="radio" name="rol_registro" value="3" <?= $rolSeleccionado===3?'checked':'' ?>><span class="registro-perfil-contenido"><i data-lucide="shield"></i><b>Oficial</b><small>Atiende incidentes</small></span></label>
          <label class="registro-perfil perfil-admin"><input type="radio" name="rol_registro" value="1" <?= $rolSeleccionado===1?'checked':'' ?>><span class="registro-perfil-contenido"><i data-lucide="shield-check"></i><b>Admin</b><small>Gestiona el sistema</small></span></label>
        </div>
      </fieldset>
      <div class="registro-info-rol" id="registro-info-rol" aria-live="polite">
        <span class="registro-info-icon"><i data-lucide="lightbulb"></i></span>
        <div><strong id="registro-tipo-titulo">Cuenta vecinal</strong><p id="registro-tipo-descripcion">Tendrás acceso a reportes, alertas y seguimiento de tu zona.</p></div>
      </div>

      <div class="registro-separador"><span>02 / 03 · TUS DATOS</span></div>
      <div class="registro-campos-grid">
        <div class="registro-campo"><label for="nombres">Nombres <span>*</span></label><input id="nombres" name="nombres" type="text" maxlength="60" autocomplete="given-name" value="<?= $anterior('nombres') ?>" placeholder="Tu nombre" required></div>
        <div class="registro-campo"><label for="apellidos">Apellidos <span>*</span></label><input id="apellidos" name="apellidos" type="text" maxlength="60" autocomplete="family-name" value="<?= $anterior('apellidos') ?>" placeholder="Tus apellidos" required></div>
        <div class="registro-campo"><label for="dni">DNI <span>*</span></label><input id="dni" name="dni" type="text" maxlength="8" pattern="[0-9]{8}" inputmode="numeric" autocomplete="off" value="<?= $anterior('dni') ?>" placeholder="8 dígitos" required></div>
        <div class="registro-campo"><label for="telefono">Teléfono</label><input id="telefono" name="telefono" type="tel" maxlength="15" autocomplete="tel" value="<?= $anterior('telefono') ?>" placeholder="Ej.: 987654321"></div>
        <div class="registro-campo registro-ancho"><label for="email">Correo electrónico <span>*</span></label><input id="email" name="email" type="email" maxlength="100" autocomplete="email" value="<?= $anterior('email') ?>" placeholder="tu@correo.com" required></div>
        <div class="registro-campo registro-ancho"><label for="id_vecindario">Zona / vecindario <span>*</span></label><select id="id_vecindario" name="id_vecindario" required><option value="">Selecciona tu zona</option><?php foreach ($vecindarios as $v): ?><option value="<?= (int)$v['id_vecindario'] ?>" <?= (int)($anteriores['vecindario']??0)===(int)$v['id_vecindario']?'selected':'' ?>><?= $h($v['nombre']) ?> · <?= $h($v['distrito']) ?></option><?php endforeach; ?></select></div>
      </div>

      <div data-solo-rol="2" class="registro-dinamico">
        <div class="registro-campo"><label for="direccion">Dirección de referencia <span>*</span></label><input id="direccion" name="direccion" type="text" maxlength="150" value="<?= $anterior('direccion') ?>" autocomplete="street-address" placeholder="Jr. o avenida y número"><small>Se utiliza para vincular tu cuenta con la zona.</small></div>
      </div>
      <div data-solo-rol="3,1" class="registro-dinamico">
        <div class="registro-campos-grid">
          <div class="registro-campo registro-ancho"><label for="institucion">Institución o dependencia <span>*</span></label><input id="institucion" name="institucion" type="text" maxlength="120" value="<?= $anterior('institucion') ?>" placeholder="Ej.: Municipalidad, central de serenazgo"></div>
          <div class="registro-campo registro-ancho" data-solo-rol="3"><label for="turno">Turno de trabajo <span>*</span></label><select name="turno" id="turno"><option value="">Elige tu turno</option><?php foreach (['Mañana','Tarde','Noche'] as $t): ?><option value="<?= $t ?>" <?= ($anteriores['turno']??'')===$t?'selected':'' ?>><?= $t ?></option><?php endforeach; ?></select></div>
          <div class="registro-campo registro-ancho"><label for="justificacion">Motivo de la solicitud <span>*</span></label><textarea id="justificacion" name="justificacion" rows="3" maxlength="500" placeholder="Indica tus funciones, vínculo con la institución y motivo para acceder al panel."><?= $anterior('justificacion') ?></textarea><small>Mínimo 15 caracteres. La administración verificará tus datos antes de habilitar el acceso.</small></div>
        </div>
        <label class="registro-acepto"><input type="checkbox" name="declaracion" value="1" id="declaracion"><span>Declaro que los datos que proporciono son auténticos y que cuento con autorización para solicitar este rol.</span></label>
      </div>

      <div class="registro-separador"><span>03 / 03 · PROTECCIÓN DE CUENTA</span></div>
      <div class="registro-campos-grid">
        <div class="registro-campo"><label for="password-registro">Contraseña <span>*</span></label><div class="registro-clave"><input id="password-registro" name="password" type="password" minlength="10" maxlength="255" autocomplete="new-password" placeholder="Mínimo 10 caracteres" required><button type="button" class="registro-ver" data-mostrar="password-registro" aria-label="Mostrar contraseña"><i data-lucide="eye"></i></button></div></div>
        <div class="registro-campo"><label for="confirmar_password">Confirmar contraseña <span>*</span></label><div class="registro-clave"><input id="confirmar_password" name="confirmar_password" type="password" minlength="10" maxlength="255" autocomplete="new-password" placeholder="Repite tu contraseña" required><button type="button" class="registro-ver" data-mostrar="confirmar_password" aria-label="Mostrar contraseña"><i data-lucide="eye"></i></button></div></div>
      </div>
      <div class="registro-consejo"><i data-lucide="lock-keyhole"></i><span><strong>Consejo:</strong> utiliza una contraseña única de al menos 10 caracteres. Evita compartirla con otras personas y no reutilices la de tu correo.</span></div>
      <button type="submit" class="registro-enviar" id="registro-enviar"><span id="registro-enviar-texto">Crear mi cuenta vecinal</span><i data-lucide="arrow-right"></i></button>
      <p class="registro-aviso-final" id="registro-aviso-final">Tu cuenta vecinal se habilitará al finalizar el registro.</p>
    </form>
    <p class="registro-pie">¿Ya tienes una cuenta? <a href="index.php?controller=Auth&action=login" id="registro-login-enlace">Inicia sesión</a></p>
  </section>
</div>
