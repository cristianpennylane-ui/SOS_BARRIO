// Búsqueda local del listado de usuarios (no expone datos adicionales).
(() => {
  const input=document.getElementById('buscar-usuarios');
  const tabla=document.querySelector('.panel-usuarios');
  const contador=document.getElementById('contador-usuarios');
  if (!input || !tabla) return;
  const filas=[...tabla.querySelectorAll('tbody tr')];
  const actualizar=()=> {
    const q=input.value.trim().toLocaleLowerCase('es');
    let visibles=0;
    filas.forEach(fila=>{const si=fila.textContent.toLocaleLowerCase('es').includes(q);fila.hidden=!si; if(si) visibles++;});
    if(contador) contador.textContent=`${visibles} de ${filas.length} cuentas`;
  };
  input.addEventListener('input', actualizar);actualizar();
})();
