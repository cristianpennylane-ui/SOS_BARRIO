// Accesibilidad y estado del campo contraseña de acceso
(() => {
  const b = document.getElementById('ver-password');
  const input = document.getElementById('password');
  if (!b || !input) return;
  b.addEventListener('click', () => {
    const mostrar = input.type === 'password';
    input.type = mostrar ? 'text' : 'password';
    b.setAttribute('aria-pressed', String(mostrar));
    b.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    b.innerHTML = mostrar ? '<i data-lucide="eye-off"></i>' : '<i data-lucide="eye"></i>';
    if (window.lucide) window.lucide.createIcons();
  });
})();

// Actualiza el enlace de registro al seleccionar otro panel.
(() => {
  const radios = [...document.querySelectorAll('.acceso-roles input[name="rol_acceso"]')];
  const link = document.getElementById('acceso-registro-link');
  const nombre = document.getElementById('acceso-registro-rol');
  const consejo = document.getElementById('acceso-consejo-rol');
  if (!radios.length || !link || !nombre || !consejo) return;
  const textos = {
    '2': ['Vecino', 'Como vecino puedes registrarte ahora y comenzar a utilizar tu panel.'],
    '3': ['Oficial', 'Como oficial debes solicitar una cuenta y esperar la validación institucional.'],
    '1': ['Administrador', 'Para administrar el sistema debes presentar una solicitud que aprobará otro administrador.']
  };
  function actualizar() {
    const rol=radios.find(r=>r.checked)?.value || '2';
    nombre.textContent=textos[rol][0];
    consejo.textContent=textos[rol][1];
    link.href=`index.php?controller=Auth&action=registro&rol=${rol}`;
  }
  radios.forEach(r=>r.addEventListener('change', actualizar));
  actualizar();
})();
