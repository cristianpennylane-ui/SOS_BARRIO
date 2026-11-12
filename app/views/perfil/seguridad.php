<section class="panel-cabecera"><div><span class="panel-etiqueta">MI CUENTA · PROTECCIÓN</span><h1>Seguridad de la cuenta</h1><p>Actualiza tu contraseña personal. Este cambio solo afecta a tu propia cuenta.</p></div><a class="panel-boton" href="index.php?controller=Panel&action=index"><i data-lucide="arrow-left"></i> Mi panel</a></section>
<?php if (!empty($mensaje)): ?><div class="mensaje-auth aviso" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="mensaje-auth error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<section class="panel-contenido seguridad-contenido"><div class="panel-seccion-head"><div><h2>Cambiar contraseña</h2><p>Usa una contraseña única, de al menos 10 caracteres.</p></div></div>
<form action="index.php?controller=Perfil&action=cambiarPassword" class="formulario formulario-seguridad" method="POST"><?= Csrf::campo() ?>
<label for="password_actual">Contraseña actual</label><input id="password_actual" type="password" name="password_actual" autocomplete="current-password" required>
<label for="password_nuevo">Nueva contraseña</label><input id="password_nuevo" type="password" name="password_nuevo" minlength="10" maxlength="128" autocomplete="new-password" required>
<label for="password_confirmar">Confirmar nueva contraseña</label><input id="password_confirmar" type="password" name="password_confirmar" minlength="10" maxlength="128" autocomplete="new-password" required>
<button type="submit">Guardar nueva contraseña</button></form></section>
