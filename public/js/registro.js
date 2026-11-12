// Registro adaptable: la interfaz no otorga permisos; el servidor valida el rol.
(() => {
  const root = document.querySelector('.registro-v5');
  if (!root) return;
  const form = document.getElementById('form-registro-roles');
  const radios = [...root.querySelectorAll('input[name="rol_registro"]')];
  const fichas = {
    '2': {titulo: 'Tu comunidad, más conectada', descripcion: 'Registro inmediato. Podrás crear reportes, recibir alertas de tu zona y consultar tu historial.', boton:'Crear mi cuenta vecinal', aviso:'Tu cuenta vecinal se habilitará al finalizar el registro.'},
    '3': {titulo: 'Solicitud para personal de serenazgo', descripcion: 'Necesitamos tu dependencia y turno. Un administrador verificará tu identidad y autorización antes de activar la cuenta.', boton:'Solicitar acceso como oficial', aviso:'Este formulario crea una solicitud pendiente, no una cuenta con permisos de oficial.'},
    '1': {titulo: 'Acceso administrativo supervisado', descripcion: 'Describe tu responsabilidad institucional. Solo otro administrador autorizado puede aprobar tu solicitud.', boton:'Solicitar acceso administrativo', aviso:'Seleccionar Administrador no concede permisos. Se requiere revisión y aprobación previa.'}
  };
  function cambiar() {
    const rol = radios.find(r => r.checked)?.value ?? '2';
    root.dataset.rol = rol;
    const d = fichas[rol];
    document.getElementById('registro-tipo-titulo').textContent = d.titulo;
    document.getElementById('registro-tipo-descripcion').textContent = d.descripcion;
    document.getElementById('registro-enviar-texto').textContent = d.boton;
    document.getElementById('registro-aviso-final').textContent = d.aviso;
    document.getElementById('registro-login-enlace').href = `index.php?controller=Auth&action=login&rol=${rol}`;
    root.querySelectorAll('[data-solo-rol]').forEach(seccion => {
      const visible = seccion.dataset.soloRol.split(',').includes(rol);
      seccion.hidden = !visible;
      seccion.querySelectorAll('input,select,textarea').forEach(campo => {
        campo.disabled = !visible;
        if (campo.id === 'direccion' || campo.id === 'institucion' || campo.id === 'justificacion' || campo.id === 'turno' || campo.id === 'declaracion') campo.required = visible;
      });
    });
    if (history.replaceState) history.replaceState(null, '', `index.php?controller=Auth&action=registro&rol=${rol}`);
  }
  radios.forEach(r => r.addEventListener('change', cambiar));
  cambiar();
  root.querySelectorAll('[data-mostrar]').forEach(btn => btn.addEventListener('click', () => {
    const campo = document.getElementById(btn.dataset.mostrar);
    const mostrar = campo.type === 'password';
    campo.type = mostrar ? 'text' : 'password';
    btn.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    btn.innerHTML = mostrar ? '<i data-lucide="eye-off"></i>' : '<i data-lucide="eye"></i>';
    window.lucide?.createIcons();
  }));
  form.addEventListener('submit', e => {
    const p = document.getElementById('password-registro');
    const c = document.getElementById('confirmar_password');
    c.setCustomValidity(c.value !== p.value ? 'Las contraseñas no coinciden' : '');
    if (!form.reportValidity()) e.preventDefault();
  });
  document.getElementById('confirmar_password').addEventListener('input', e => e.target.setCustomValidity(''));
})();
