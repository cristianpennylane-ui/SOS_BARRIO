<section class="panel-cabecera panel-cabecera-admin"><div><span class="panel-etiqueta">ADMINISTRACIÓN · ACCESOS</span><h1>Usuarios y permisos</h1><p>Crea oficiales, actualiza roles y configura qué cuenta tiene acceso a cada panel.</p></div><a class="panel-boton" href="index.php?controller=Panel&action=administrador"><i data-lucide="arrow-left"></i> Volver</a></section>
<?php if (!empty($mensaje)): ?><div class="mensaje-auth aviso" role="status"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<?php if (!empty($error)): ?><div class="mensaje-auth error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
<!-- Bandeja de solicitudes privilegiadas: nunca se autoriza desde el registro público. -->
<section class="panel-contenido solicitudes-v5" id="solicitudes">
  <div class="panel-seccion-head"><div><span class="solicitud-eyebrow">VALIDACIÓN DE IDENTIDAD</span><h2>Solicitudes de acceso <span class="solicitudes-contador"><?= count($solicitudes) ?></span></h2><p>Revisa la vinculación con la institución antes de aprobar el acceso como Oficial o Administrador.</p></div></div>
  <?php if (!empty($avisoMigracion)): ?><div class="registro-alerta error" role="alert"><i data-lucide="alert-circle"></i><span><?= htmlspecialchars($avisoMigracion, ENT_QUOTES, 'UTF-8') ?></span></div><?php endif; ?>
  <div class="solicitudes-recomendacion"><i data-lucide="shield-check"></i><p><strong>Antes de aprobar:</strong> confirma personalmente la identidad, el puesto y la autorización del solicitante. No confíes únicamente en el correo o en los datos que escribió.</p></div>
  <?php if (empty($solicitudes) && empty($avisoMigracion)): ?>
    <div class="solicitudes-vacio"><i data-lucide="inbox"></i><strong>No hay solicitudes pendientes</strong><span>Las solicitudes de oficiales y administradores aparecerán aquí.</span></div>
  <?php else: ?>
    <div class="solicitudes-grid">
    <?php foreach($solicitudes as $s): ?>
      <article class="solicitud-tarjeta <?= (int)$s['rol_solicitado']===1?'solicitud-administrador':'solicitud-oficial' ?>">
        <div class="solicitud-tarjeta-top"><span class="solicitud-tag"><?= (int)$s['rol_solicitado']===1?'Administrador':'Oficial / Serenazgo' ?></span><span class="solicitud-id">#<?= (int)$s['id_solicitud'] ?></span></div>
        <h3><?= htmlspecialchars($s['nombres'].' '.$s['apellidos'],ENT_QUOTES,'UTF-8') ?></h3>
        <div class="solicitud-datos">
          <span><i data-lucide="id-card"></i> DNI <?= htmlspecialchars($s['dni'],ENT_QUOTES,'UTF-8') ?></span>
          <span><i data-lucide="mail"></i> <?= htmlspecialchars($s['email'],ENT_QUOTES,'UTF-8') ?></span>
          <span><i data-lucide="map-pin"></i> <?= htmlspecialchars($s['vecindario'],ENT_QUOTES,'UTF-8') ?></span>
          <?php if (!empty($s['telefono'])): ?><span><i data-lucide="phone"></i> <?= htmlspecialchars($s['telefono'],ENT_QUOTES,'UTF-8') ?></span><?php endif; ?>
          <?php if (!empty($s['turno'])): ?><span><i data-lucide="clock"></i> Turno <?= htmlspecialchars($s['turno'],ENT_QUOTES,'UTF-8') ?></span><?php endif; ?>
          <span><i data-lucide="calendar"></i> <?= htmlspecialchars($s['fecha_solicitud'],ENT_QUOTES,'UTF-8') ?></span>
        </div>
        <div class="solicitud-dependencia"><span>INSTITUCIÓN / DEPENDENCIA</span><strong><?= htmlspecialchars($s['institucion'] ?: 'No indicada',ENT_QUOTES,'UTF-8') ?></strong></div>
        <div class="solicitud-motivo"><span>MOTIVO DE ACCESO</span><p><?= nl2br(htmlspecialchars($s['justificacion'] ?: 'No indicado',ENT_QUOTES,'UTF-8')) ?></p></div>
        <form method="POST" class="solicitud-acciones" action="index.php?controller=Admin&action=resolverSolicitud">
          <?= Csrf::campo() ?><input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
          <label class="solicitud-check"><input type="checkbox" name="identidad_verificada" value="1"><span>He verificado su identidad y autorización institucional.</span></label>
          <div class="solicitud-botones">
            <button type="submit" name="decision" value="rechazar" class="solicitud-rechazar" onclick="return confirm('¿Rechazar esta solicitud? No se creará ninguna cuenta.')"><i data-lucide="x"></i> Rechazar</button>
            <button type="submit" name="decision" value="aprobar" class="solicitud-aprobar" onclick="return confirm('¿Aprobar la creación de esta cuenta con permisos de <?= (int)$s['rol_solicitado']===1?'ADMINISTRADOR':'OFICIAL' ?>?')"><i data-lucide="check"></i> Aprobar</button>
          </div>
        </form>
      </article>
    <?php endforeach; ?>
    </div>
  <?php endif; ?>
</section>

<section class="panel-contenido"><div class="panel-seccion-head"><div><h2>Alta de personal de serenazgo</h2><p>Si el DNI ya existe en la tabla de serenazgo, se reutiliza su ficha. Solo el administrador puede dar de alta oficiales.</p></div></div>
<details class="panel-desplegable"><summary><i data-lucide="user-plus"></i> Registrar un nuevo oficial</summary>
<form class="panel-alta-form" action="index.php?controller=Admin&action=crearOficial" method="POST"><?= Csrf::campo() ?>
<label>Nombres <input name="nombres" maxlength="60" required></label><label>Apellidos <input name="apellidos" maxlength="60" required></label>
<label>DNI <input name="dni" type="text" inputmode="numeric" maxlength="8" pattern="[0-9]{8}" required></label><label>Correo electrónico <input name="email" type="email" maxlength="100" required></label>
<label>Teléfono <input name="telefono" maxlength="15"></label><label>Vecindario<select name="id_vecindario" required><?php foreach ($vecindarios as $v): ?><option value="<?= (int)$v['id_vecindario'] ?>"><?= htmlspecialchars($v['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></label>
<label>Turno<select name="turno" required><option value="Mañana">Mañana</option><option value="Tarde">Tarde</option><option value="Noche">Noche</option></select></label>
<label>Contraseña inicial <input type="password" name="password" minlength="10" maxlength="255" autocomplete="new-password" required></label>
<label>Confirmar contraseña <input type="password" name="confirmar_password" minlength="10" maxlength="255" autocomplete="new-password" required></label>
<div class="panel-alta-acciones"><button type="submit" class="panel-boton">Crear cuenta de oficial</button><small>La contraseña no se muestra ni almacena en texto plano.</small></div></form>
</details></section>
<section class="panel-contenido"><div class="panel-seccion-head"><div><h2>Cuentas registradas</h2><p>La sesión de una cuenta refleja los cambios de rol y estado en su siguiente solicitud. No puedes modificar tu propia cuenta desde aquí.</p></div></div>
<div class="panel-buscar-usuarios"><label for="buscar-usuarios">Buscar cuentas</label><input id="buscar-usuarios" type="search" placeholder="Nombre, correo, DNI o vecindario…" autocomplete="off"><span id="contador-usuarios" aria-live="polite"></span></div>
<div class="panel-tabla-wrap"><table class="panel-tabla panel-usuarios"><thead><tr><th>Cuenta</th><th>Documento</th><th>Zona</th><th>Permisos y estado</th></tr></thead><tbody>
<?php foreach($usuarios as $u): ?><tr>
<td><strong><?= htmlspecialchars($u['nombres'].' '.$u['apellidos'], ENT_QUOTES, 'UTF-8') ?></strong><span class="panel-resumen-descripcion"><?= htmlspecialchars($u['email'], ENT_QUOTES, 'UTF-8') ?></span></td>
<td class="panel-id"><?= htmlspecialchars($u['dni'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($u['vecindario'], ENT_QUOTES, 'UTF-8') ?></td>
<td><?php if ((int)$u['id_usuario']===Auth::id()): ?><span class="panel-tag panel-tag-estado"><?= htmlspecialchars($u['rol'].' · '.$u['estado'], ENT_QUOTES, 'UTF-8') ?> (tu cuenta)</span>
<?php else: ?><form method="POST" action="index.php?controller=Admin&action=guardarUsuario" class="panel-form-edicion" onsubmit="return confirm('¿Guardar el nuevo rol, estado y zona de esta cuenta?')">
<?= Csrf::campo() ?><input type="hidden" name="id_usuario" value="<?= (int)$u['id_usuario'] ?>">
<select name="id_rol" aria-label="Rol de <?= htmlspecialchars($u['nombres'], ENT_QUOTES, 'UTF-8') ?>"><option value="2" <?= (int)$u['id_rol']===2?'selected':'' ?>>Vecino</option><option value="3" <?= (int)$u['id_rol']===3?'selected':'' ?>>Oficial</option><option value="1" <?= (int)$u['id_rol']===1?'selected':'' ?>>Administrador</option></select>
<select name="estado" aria-label="Estado"><option value="Activo" <?= $u['estado']==='Activo'?'selected':'' ?>>Activo</option><option value="Inactivo" <?= $u['estado']==='Inactivo'?'selected':'' ?>>Inactivo</option></select>
<select name="id_vecindario" aria-label="Vecindario de <?= htmlspecialchars($u['nombres'], ENT_QUOTES, 'UTF-8') ?>"><?php foreach ($vecindarios as $v): ?><option value="<?= (int)$v['id_vecindario'] ?>" <?= (int)$u['id_vecindario']===(int)$v['id_vecindario']?'selected':'' ?>><?= htmlspecialchars($v['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select>
<select name="turno" aria-label="Turno del oficial"><option value="Mañana" <?= ($u['turno'] ?? 'Mañana')==='Mañana'?'selected':'' ?>>Mañana</option><option value="Tarde" <?= ($u['turno'] ?? '')==='Tarde'?'selected':'' ?>>Tarde</option><option value="Noche" <?= ($u['turno'] ?? '')==='Noche'?'selected':'' ?>>Noche</option></select>
<button type="submit" class="panel-guardar">Guardar</button></form><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div>
</section>
<p class="panel-aclaracion">Importante: las cuentas de serenazgo deben pertenecer a personal verificado. Las operaciones se muestran con alcance del vecindario asociado.</p>
