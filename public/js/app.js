// ============================================================
// SOS BARRIO — JS principal
// Cada función se activa sola si encuentra sus elementos en la
// página, así que este mismo archivo sirve para todas las vistas.
// ============================================================

document.addEventListener('DOMContentLoaded', () => {
    initFiltrosReportes();
    initFormularioCreate();
    initMapaGeneral();
    initComentariosAjax();
    initComunidadQR();
    initDashboard();
    initMenuMovil();
    initBotonPanico();
    initModoOscuro();
    initEncuesta();
    initConsejosSeguridad();
    initFAQ();
    initTiempoEstimado();
    initContadoresAnimados();
    initBotonArriba();
    initExportarCSV();
    initCredencialQR();
    initCompartirReporte();
    initCalificacion();
    initNotificaciones();
    initZonasRiesgoMapa();
    initImprimirDashboard();
});

// ------------------------------------------------------------
// 1. Filtros en vivo de la tabla de reportes
// ------------------------------------------------------------
function initFiltrosReportes() {
    const filtroTipo = document.getElementById('filtro-tipo');
    const filtroPrioridad = document.getElementById('filtro-prioridad');
    const filtroEstado = document.getElementById('filtro-estado');
    const filtroBusqueda = document.getElementById('filtro-busqueda');
    const filas = document.querySelectorAll('#tabla-reportes tbody tr');
    const contador = document.getElementById('contador-resultados');
    if (!filas.length || !contador) return;

    function aplicarFiltros() {
        const tipo = filtroTipo ? filtroTipo.value : '';
        const prioridad = filtroPrioridad ? filtroPrioridad.value : '';
        const estado = filtroEstado ? filtroEstado.value : '';
        const texto = filtroBusqueda ? filtroBusqueda.value.trim().toLowerCase() : '';
        let visibles = 0;

        filas.forEach(fila => {
            const coincideTipo = !tipo || fila.dataset.tipo === tipo;
            const coincidePrioridad = !prioridad || fila.dataset.prioridad === prioridad;
            const coincideEstado = !estado || fila.dataset.estado === estado;
            const coincideTexto = !texto || fila.textContent.toLowerCase().includes(texto);
            const visible = coincideTipo && coincidePrioridad && coincideEstado && coincideTexto;
            fila.classList.toggle('oculto-filtro', !visible);
            if (visible) visibles++;
        });

        contador.textContent = `${visibles} de ${filas.length} reportes`;
    }

    [filtroTipo, filtroPrioridad, filtroEstado].forEach(el => el && el.addEventListener('change', aplicarFiltros));
    if (filtroBusqueda) filtroBusqueda.addEventListener('input', aplicarFiltros);
    aplicarFiltros();
}

// ------------------------------------------------------------
// 2. Formulario "Nuevo reporte": mapa interactivo, geolocalización,
//    geocodificación inversa y validaciones
// ------------------------------------------------------------
function initFormularioCreate() {
    const mapaDiv = document.getElementById('mapa-crear');
    if (!mapaDiv || typeof L === 'undefined') return;

    const inputLat = document.getElementById('latitud');
    const inputLng = document.getElementById('longitud');
    const inputRef = document.getElementById('direccion_referencia');
    const selectVecindario = document.getElementById('id_vecindario');
    const btnUbicacion = document.getElementById('btn-ubicacion');
    const form = document.getElementById('form-crear-reporte');
    const textarea = document.getElementById('descripcion');
    const contadorChars = document.getElementById('contador-caracteres');
    const selectPrioridad = document.getElementById('prioridad');
    const avisoAlta = document.getElementById('aviso-alta');

    // Centro inicial: coordenadas del vecindario ya seleccionado
    const centroInicial = obtenerCentroVecindario(selectVecindario);
    const mapa = L.map('mapa-crear').setView(centroInicial, 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; colaboradores de OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapa);

    let marcador = null;
    function colocarMarcador(lat, lng) {
        if (marcador) mapa.removeLayer(marcador);
        marcador = L.marker([lat, lng]).addTo(mapa);
        inputLat.value = lat.toFixed(7);
        inputLng.value = lng.toFixed(7);
        geocodificarInverso(lat, lng);
    }

    mapa.on('click', e => colocarMarcador(e.latlng.lat, e.latlng.lng));

    if (selectVecindario) {
        selectVecindario.addEventListener('change', () => {
            const centro = obtenerCentroVecindario(selectVecindario);
            mapa.setView(centro, 15);
        });
    }

    // Buscador de dirección (autocompletado vía Nominatim /search)
    const inputBuscar = document.getElementById('buscar-direccion');
    const listaSugerencias = document.getElementById('sugerencias-direccion');
    if (inputBuscar && listaSugerencias) {
        let temporizadorBusqueda = null;
        inputBuscar.addEventListener('input', () => {
            const q = inputBuscar.value.trim();
            clearTimeout(temporizadorBusqueda);
            if (q.length < 4) { listaSugerencias.innerHTML = ''; return; }
            temporizadorBusqueda = setTimeout(() => {
                const centro = obtenerCentroVecindario(selectVecindario);
                fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(q)}&countrycodes=pe&limit=5&viewbox=${centro[1]-0.05},${centro[0]+0.05},${centro[1]+0.05},${centro[0]-0.05}&bounded=0`)
                    .then(r => r.json())
                    .then(resultados => {
                        listaSugerencias.innerHTML = '';
                        resultados.forEach(res => {
                            const li = document.createElement('li');
                            li.textContent = res.display_name;
                            li.addEventListener('click', () => {
                                const lat = parseFloat(res.lat), lng = parseFloat(res.lon);
                                mapa.setView([lat, lng], 17);
                                colocarMarcador(lat, lng);
                                inputBuscar.value = res.display_name;
                                listaSugerencias.innerHTML = '';
                            });
                            listaSugerencias.appendChild(li);
                        });
                    })
                    .catch(() => { listaSugerencias.innerHTML = ''; });
            }, 450);
        });
        document.addEventListener('click', e => {
            if (e.target !== inputBuscar) listaSugerencias.innerHTML = '';
        });
    }

    // Geolocalización del navegador
    if (btnUbicacion) {
        btnUbicacion.addEventListener('click', () => {
            if (!navigator.geolocation) {
                alert('Tu navegador no soporta geolocalización. Marca el punto directamente en el mapa.');
                return;
            }
            btnUbicacion.disabled = true;
            btnUbicacion.textContent = 'Ubicando…';
            navigator.geolocation.getCurrentPosition(
                pos => {
                    const { latitude, longitude } = pos.coords;
                    mapa.setView([latitude, longitude], 17);
                    colocarMarcador(latitude, longitude);
                    btnUbicacion.disabled = false;
                    btnUbicacion.textContent = '📍 Usar mi ubicación actual';
                },
                () => {
                    alert('No se pudo obtener tu ubicación. Marca el punto directamente en el mapa.');
                    btnUbicacion.disabled = false;
                    btnUbicacion.textContent = '📍 Usar mi ubicación actual';
                },
                { enableHighAccuracy: true, timeout: 8000 }
            );
        });
    }

    // Geocodificación inversa (Nominatim / OpenStreetMap, gratuita)
    let temporizadorGeocod = null;
    function geocodificarInverso(lat, lng) {
        if (!inputRef) return;
        inputRef.value = 'Buscando dirección…';
        clearTimeout(temporizadorGeocod);
        temporizadorGeocod = setTimeout(() => {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(r => r.json())
                .then(data => {
                    inputRef.value = data.display_name || '';
                })
                .catch(() => {
                    inputRef.value = '';
                });
        }, 400);
    }

    // Contador de caracteres de la descripción
    if (textarea && contadorChars) {
        const max = 300;
        const actualizarContador = () => {
            const restantes = max - textarea.value.length;
            contadorChars.textContent = `${textarea.value.length}/${max}`;
            contadorChars.classList.toggle('limite', restantes < 20);
        };
        textarea.addEventListener('input', actualizarContador);
        actualizarContador();
    }

    // Aviso al elegir prioridad Alta
    if (selectPrioridad && avisoAlta) {
        selectPrioridad.addEventListener('change', () => {
            avisoAlta.classList.toggle('visible', selectPrioridad.value === 'Alta');
        });
    }

    // Validación antes de enviar
    if (form) {
        form.addEventListener('submit', e => {
            if (!inputLat.value || !inputLng.value) {
                e.preventDefault();
                alert('Marca la ubicación del incidente en el mapa (o usa "Usar mi ubicación actual") antes de enviar.');
                return;
            }
            const btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.textContent = 'Enviando…';
            }
        });
    }
}

function obtenerCentroVecindario(selectVecindario) {
    if (selectVecindario && selectVecindario.selectedOptions.length) {
        const opt = selectVecindario.selectedOptions[0];
        const lat = parseFloat(opt.dataset.lat);
        const lng = parseFloat(opt.dataset.lng);
        if (!isNaN(lat) && !isNaN(lng)) return [lat, lng];
    }
    return [-11.9776, -77.0782]; // centro aproximado de Lima Norte, respaldo
}

// ------------------------------------------------------------
// 3. Mapa general (todos los reportes con ubicación)
// ------------------------------------------------------------
function initMapaGeneral() {
    const mapaDiv = document.getElementById('mapa-general');
    if (!mapaDiv || typeof L === 'undefined') return;

    const mapa = L.map('mapa-general').setView([-11.9776, -77.0782], 13);
    window.__sosMapaGeneral = mapa;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; colaboradores de OpenStreetMap',
        maxZoom: 19,
    }).addTo(mapa);

    const colores = { Alta: '#C1443C', Media: '#F2A93B', Baja: '#3F8F5F' };

    fetch('index.php?controller=Reporte&action=geojson')
        .then(r => r.json())
        .then(reportes => {
            if (!reportes.length) return;
            const bounds = [];
            reportes.forEach(r => {
                const lat = parseFloat(r.latitud);
                const lng = parseFloat(r.longitud);
                const marcador = L.circleMarker([lat, lng], {
                    radius: 9,
                    color: colores[r.prioridad] || '#0B1F33',
                    fillColor: colores[r.prioridad] || '#0B1F33',
                    fillOpacity: 0.85,
                    weight: 2,
                }).addTo(mapa);

                marcador.bindPopup(`
                    <strong>${escaparHtml(r.tipo_incidente)}</strong> (${escaparHtml(r.prioridad)})<br>
                    ${escaparHtml(r.vecindario)}<br>
                    ${escaparHtml(r.direccion_referencia || 'Sin dirección de referencia')}<br>
                    <a href="index.php?controller=Reporte&action=detalle&id=${r.id_reporte}">Ver reporte #${r.id_reporte}</a>
                `);
                bounds.push([lat, lng]);
            });
            mapa.fitBounds(bounds, { padding: [30, 30] });
        });
}

// ------------------------------------------------------------
// 4. Comentarios por AJAX (sin recargar la página)
// ------------------------------------------------------------
function initComentariosAjax() {
    const form = document.getElementById('form-comentario');
    if (!form) return;

    const lista = document.getElementById('lista-comentarios');
    const textarea = form.querySelector('textarea');
    const estado = document.getElementById('estado-envio');

    form.addEventListener('submit', e => {
        e.preventDefault();
        const texto = textarea.value.trim();
        if (!texto) return;

        const boton = form.querySelector('button');
        boton.disabled = true;

        const datos = new FormData();
        datos.append('id_reporte', form.dataset.idReporte);
        datos.append('comentario', texto);

        fetch('index.php?controller=Reporte&action=comentar', { method: 'POST', body: datos, headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' } })
            .then(r => r.json())
            .then(resp => {
                if (resp.ok) {
                    const vacio = lista.querySelector('.sin-comentarios');
                    if (vacio) vacio.remove();
                    const li = document.createElement('li');
                    li.innerHTML = `<strong>Tú:</strong> ${escaparHtml(texto)}<small>ahora mismo</small>`;
                    lista.appendChild(li);
                    textarea.value = '';
                    estado.textContent = 'Comentario publicado.';
                    estado.className = 'estado-envio ok';
                    mostrarToast('Comentario publicado.', 'ok');
                } else {
                    estado.textContent = resp.error || 'No se pudo publicar el comentario.';
                    estado.className = 'estado-envio error';
                }
            })
            .catch(() => {
                estado.textContent = 'Error de conexión. Intenta de nuevo.';
                estado.className = 'estado-envio error';
            })
            .finally(() => { boton.disabled = false; });
    });
}

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto ?? '';
    return div.innerHTML;
}

// ------------------------------------------------------------
// 5. Código QR de los grupos de comunidad (generado en el navegador)
// ------------------------------------------------------------
function initComunidadQR() {
    const nodos = document.querySelectorAll('[data-qr-url]');
    if (!nodos.length || typeof QRCode === 'undefined') return;
    nodos.forEach(nodo => {
        new QRCode(nodo, {
            text: nodo.dataset.qrUrl,
            width: 140,
            height: 140,
            colorDark: '#0B1F33',
            colorLight: '#ffffff',
        });
    });

    document.querySelectorAll('[data-copiar]').forEach(btn => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.copiar).then(() => {
                const original = btn.textContent;
                btn.textContent = '¡Copiado!';
                mostrarToast('Enlace copiado al portapapeles.', 'ok');
                setTimeout(() => { btn.textContent = original; }, 1500);
            });
        });
    });

    // Descargar el QR de cada tarjeta de grupo como imagen PNG
    document.querySelectorAll('[data-descargar-qr]').forEach(btn => {
        btn.addEventListener('click', () => {
            const tarjeta = btn.closest('.tarjeta-grupo');
            const canvas = tarjeta ? tarjeta.querySelector('canvas') : null;
            if (!canvas) { mostrarToast('No se pudo generar la imagen del QR.', 'error'); return; }
            descargarCanvasComoImagen(canvas, `qr-${slugificar(btn.dataset.descargarQr)}.png`);
            mostrarToast('QR descargado.', 'ok');
        });
    });
}

function descargarCanvasComoImagen(canvas, nombreArchivo) {
    const enlace = document.createElement('a');
    enlace.download = nombreArchivo;
    enlace.href = canvas.toDataURL('image/png');
    document.body.appendChild(enlace);
    enlace.click();
    enlace.remove();
}

function slugificar(texto) {
    return (texto || 'sos-barrio')
        .toString()
        .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/(^-|-$)/g, '');
}

// ------------------------------------------------------------
// 6. Dashboard con Chart.js
// ------------------------------------------------------------
function initDashboard() {
    const raiz = document.getElementById('dashboard-datos');
    if (!raiz || typeof Chart === 'undefined') return;

    fetch('index.php?controller=Dashboard&action=data')
        .then(r => r.json())
        .then(datos => {
            pintarGraficoBarras('grafico-tipo', datos.porTipo, 'tipo', '#0B1F33');
            pintarGraficoBarras('grafico-vecindario', datos.porVecindario, 'vecindario', '#3F8F5F');
            pintarTablaIndicadores(datos.indicadores);
            pintarGraficoTendencia('grafico-tendencia', datos.porDia);
            pintarGraficoRiesgo('grafico-riesgo', datos.zonasRiesgo);
            pintarCalificacionPromedio(datos.calificacion);
        });
}

// Línea de tendencia de los últimos N días (rellena los días sin
// reportes con 0, para que la línea no tenga huecos)
function pintarGraficoTendencia(idCanvas, filas) {
    const canvas = document.getElementById(idCanvas);
    if (!canvas || !filas) return;

    const porFecha = {};
    filas.forEach(f => { porFecha[f.fecha] = parseInt(f.total, 10); });

    const etiquetas = [];
    const valores = [];
    for (let i = 13; i >= 0; i--) {
        const d = new Date();
        d.setDate(d.getDate() - i);
        const clave = d.toISOString().slice(0, 10);
        etiquetas.push(d.toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit' }));
        valores.push(porFecha[clave] || 0);
    }

    new Chart(canvas, {
        type: 'line',
        data: {
            labels: etiquetas,
            datasets: [{
                label: 'Reportes',
                data: valores,
                borderColor: '#F2A93B',
                backgroundColor: 'rgba(242,169,59,0.18)',
                fill: true,
                tension: 0.35,
                pointRadius: 3,
                pointBackgroundColor: '#0B1F33',
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

// Dona de zonas de riesgo (Bajo / Medio / Alto)
function pintarGraficoRiesgo(idCanvas, filas) {
    const canvas = document.getElementById(idCanvas);
    if (!canvas || !filas) return;

    const colores = { Alto: '#C1443C', Medio: '#F2A93B', Bajo: '#3F8F5F' };
    const conteo = { Alto: 0, Medio: 0, Bajo: 0 };
    filas.forEach(z => { conteo[z.nivel_riesgo] = (conteo[z.nivel_riesgo] || 0) + 1; });

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: Object.keys(conteo),
            datasets: [{
                data: Object.values(conteo),
                backgroundColor: Object.keys(conteo).map(k => colores[k]),
                borderWidth: 2,
                borderColor: '#fff',
            }],
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' } },
        },
    });
}

function pintarCalificacionPromedio(calificacion) {
    const nodo = document.getElementById('valor-calificacion-prom');
    if (!nodo || !calificacion) return;
    nodo.textContent = calificacion.promedio ? `${calificacion.promedio} ★` : 'Sin datos';
}

function pintarGraficoBarras(idCanvas, filas, campoEtiqueta, color) {
    const canvas = document.getElementById(idCanvas);
    if (!canvas || !filas) return;
    new Chart(canvas, {
        type: 'bar',
        data: {
            labels: filas.map(f => f[campoEtiqueta]),
            datasets: [{ label: 'Reportes', data: filas.map(f => f.total), backgroundColor: color, borderRadius: 6 }],
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
        },
    });
}

function pintarTablaIndicadores(filas) {
    const tbody = document.getElementById('tabla-indicadores-body');
    if (!tbody || !filas) return;
    tbody.innerHTML = filas.map(f => `
        <tr>
            <td>${escaparHtml(f.vecindario)}</td>
            <td>${f.incidentes_mes}</td>
            <td>${parseFloat(f.tiempo_respuesta_prom).toFixed(1)} min</td>
        </tr>
    `).join('');
}

// ------------------------------------------------------------
// 7. Menú hamburguesa (mobile)
// ------------------------------------------------------------
function initMenuMovil() {
    const boton = document.getElementById('btn-hamburguesa');
    const nav = document.getElementById('nav-principal');
    if (!boton || !nav) return;
    boton.addEventListener('click', () => nav.classList.toggle('abierto'));
}

// ------------------------------------------------------------
// 8. Botón de pánico (SOS de un toque)
// ------------------------------------------------------------
function initBotonPanico() {
    const boton = document.getElementById('btn-panico');
    const modal = document.getElementById('modal-panico');
    if (!boton || !modal) return;

    const pasos = {
        confirmar: document.getElementById('paso-confirmar'),
        enviando: document.getElementById('paso-enviando'),
        exito: document.getElementById('paso-exito'),
        error: document.getElementById('paso-error'),
    };

    function mostrarPaso(nombre) {
        Object.values(pasos).forEach(p => p.style.display = 'none');
        pasos[nombre].style.display = 'block';
    }

    function abrirModal() {
        mostrarPaso('confirmar');
        modal.classList.add('visible');
    }
    function cerrarModal() {
        modal.classList.remove('visible');
    }

    boton.addEventListener('click', abrirModal);
    document.getElementById('btn-cancelar-panico').addEventListener('click', cerrarModal);
    document.getElementById('btn-cerrar-panico').addEventListener('click', cerrarModal);
    document.getElementById('btn-reintentar-panico').addEventListener('click', cerrarModal);
    modal.addEventListener('click', e => { if (e.target === modal) cerrarModal(); });

    document.getElementById('btn-confirmar-panico').addEventListener('click', () => {
        mostrarPaso('enviando');

        if (!navigator.geolocation) {
            document.getElementById('texto-error-panico').textContent = 'Tu navegador no soporta geolocalización, no se puede enviar la alerta.';
            mostrarPaso('error');
            return;
        }

        navigator.geolocation.getCurrentPosition(
            pos => {
                const datos = new FormData();
                datos.append('latitud', pos.coords.latitude);
                datos.append('longitud', pos.coords.longitude);
                datos.append('id_vecindario', boton.dataset.idVecindario);

                fetch('index.php?controller=Reporte&action=panico', { method: 'POST', body: datos, headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' } })
                    .then(r => r.json())
                    .then(resp => {
                        if (resp.ok) {
                            document.getElementById('texto-exito-panico').textContent =
                                `Reporte #${resp.id_reporte} creado y serenazgo notificado. Mantén la calma, ya vienen en camino.`;
                            document.getElementById('link-ver-reporte').href =
                                `index.php?controller=Reporte&action=detalle&id=${resp.id_reporte}`;
                            mostrarPaso('exito');
                        } else {
                            document.getElementById('texto-error-panico').textContent = resp.error || 'No se pudo enviar la alerta.';
                            mostrarPaso('error');
                        }
                    })
                    .catch(() => {
                        document.getElementById('texto-error-panico').textContent = 'Error de conexión. Intenta de nuevo o llama directamente al serenazgo.';
                        mostrarPaso('error');
                    });
            },
            () => {
                document.getElementById('texto-error-panico').textContent = 'No se pudo obtener tu ubicación. Activa el GPS e intenta de nuevo.';
                mostrarPaso('error');
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    });
}

// ------------------------------------------------------------
// 9. Modo oscuro (persistido en localStorage)
// ------------------------------------------------------------
function initModoOscuro() {
    const boton = document.getElementById('btn-modo-oscuro');
    if (!boton) return;

    function aplicarTema(tema) {
        document.documentElement.setAttribute('data-theme', tema);
        boton.innerHTML = tema === 'oscuro' ? '<i data-lucide="sun"></i>' : '<i data-lucide="moon"></i>';
        if (window.lucide) lucide.createIcons();
    }

    const guardado = localStorage.getItem('sos_tema') || 'claro';
    aplicarTema(guardado);

    boton.addEventListener('click', () => {
        const actual = document.documentElement.getAttribute('data-theme') === 'oscuro' ? 'claro' : 'oscuro';
        localStorage.setItem('sos_tema', actual);
        aplicarTema(actual);
    });
}

// ------------------------------------------------------------
// 10. Encuesta de percepción de seguridad (demo local, sin backend)
// ------------------------------------------------------------
function initEncuesta() {
    const panel = document.getElementById('panel-encuesta');
    if (!panel) return;

    const OPCIONES = ['Muy segura', 'Segura', 'Regular', 'Insegura', 'Muy insegura'];
    // Base inicial para que la encuesta no se vea vacía en una instalación nueva
    const BASE = { 'Muy segura': 4, 'Segura': 11, 'Regular': 16, 'Insegura': 7, 'Muy insegura': 3 };

    function semanaActual() {
        const ahora = new Date();
        const inicio = new Date(ahora.getFullYear(), 0, 1);
        const semana = Math.ceil((((ahora - inicio) / 86400000) + inicio.getDay() + 1) / 7);
        return `${ahora.getFullYear()}-W${semana}`;
    }

    const claveVotos = `sos_encuesta_votos_${semanaActual()}`;
    const claveMiVoto = `sos_encuesta_mivoto_${semanaActual()}`;

    function obtenerVotos() {
        try {
            return { ...BASE, ...JSON.parse(localStorage.getItem(claveVotos) || '{}') };
        } catch { return { ...BASE }; }
    }

    function mostrarResultados() {
        const votos = obtenerVotos();
        const total = Object.values(votos).reduce((a, b) => a + b, 0);
        document.getElementById('encuesta-opciones').hidden = true;
        const resultados = document.getElementById('encuesta-resultados');
        resultados.hidden = false;

        resultados.querySelectorAll('.barra-encuesta').forEach(barra => {
            const opcion = barra.dataset.opcion;
            const cantidad = votos[opcion] || 0;
            const pct = total ? Math.round((cantidad / total) * 100) : 0;
            barra.innerHTML = `
                <span class="barra-encuesta-label">${opcion}</span>
                <span class="barra-encuesta-fondo"><span class="barra-encuesta-relleno" style="width:${pct}%"></span></span>
                <span class="barra-encuesta-pct">${pct}%</span>
            `;
        });
        document.getElementById('encuesta-total').textContent = total;
    }

    if (localStorage.getItem(claveMiVoto)) {
        mostrarResultados();
    }

    panel.querySelectorAll('.opcion-encuesta').forEach(btn => {
        btn.addEventListener('click', () => {
            const opcion = btn.dataset.opcion;
            const votos = obtenerVotos();
            votos[opcion] = (votos[opcion] || 0) + 1;
            localStorage.setItem(claveVotos, JSON.stringify(votos));
            localStorage.setItem(claveMiVoto, opcion);
            mostrarResultados();
        });
    });
}

// ------------------------------------------------------------
// 11. Carrusel de consejos de seguridad
// ------------------------------------------------------------
function initConsejosSeguridad() {
    const contenedor = document.getElementById('carrusel-consejos');
    if (!contenedor) return;

    const CONSEJOS = [
        '🔑 Cierra siempre la puerta con llave, incluso si sales "solo un momento".',
        '💡 Mantén el alumbrado exterior de tu casa encendido durante la noche.',
        '👥 Organiza turnos de vigilancia con tus vecinos más cercanos.',
        '📱 Guarda el número del serenazgo de tu zona en tu celular.',
        '🚨 Ante una emergencia real, usa el botón de pánico: notifica al instante.',
        '🚶 Evita caminar distraído con el celular en la mano en la calle.',
        '🏠 No publiques en redes sociales que tu casa estará sola por vacaciones.',
        '🚗 Verifica que tu vehículo quede bien cerrado y sin objetos de valor a la vista.',
    ];

    let indice = 0;
    let temporizador = null;

    function pintar() {
        contenedor.innerHTML = `<p class="consejo-actual">${CONSEJOS[indice]}</p>`;
        const puntos = document.getElementById('puntos-carrusel');
        if (puntos) {
            puntos.innerHTML = CONSEJOS.map((_, i) =>
                `<span class="punto-consejo ${i === indice ? 'activo' : ''}" data-i="${i}"></span>`
            ).join('');
            puntos.querySelectorAll('.punto-consejo').forEach(p => {
                p.addEventListener('click', () => { indice = parseInt(p.dataset.i); pintar(); reiniciarTemporizador(); });
            });
        }
    }

    function siguiente() { indice = (indice + 1) % CONSEJOS.length; pintar(); }
    function reiniciarTemporizador() {
        clearInterval(temporizador);
        temporizador = setInterval(siguiente, 6000);
    }

    pintar();
    reiniciarTemporizador();
    contenedor.addEventListener('mouseenter', () => clearInterval(temporizador));
    contenedor.addEventListener('mouseleave', reiniciarTemporizador);
}

// ------------------------------------------------------------
// 12. Acordeón de preguntas frecuentes
// ------------------------------------------------------------
function initFAQ() {
    const acordeon = document.getElementById('acordeon-faq');
    if (!acordeon) return;

    acordeon.querySelectorAll('.item-faq').forEach(item => {
        const boton = item.querySelector('.pregunta-faq');
        boton.addEventListener('click', () => {
            const abierto = item.classList.contains('abierto');
            acordeon.querySelectorAll('.item-faq').forEach(i => i.classList.remove('abierto'));
            if (!abierto) item.classList.add('abierto');
        });
    });
}

// ------------------------------------------------------------
// 14. Utilidad de notificaciones "toast" (usada por varias
//     funciones: comunidad, calificación, notificaciones, etc.)
// ------------------------------------------------------------
function mostrarToast(mensaje, tipo = 'info') {
    const contenedor = document.getElementById('contenedor-toasts');
    if (!contenedor) return;
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    toast.textContent = mensaje;
    contenedor.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('visible'));
    setTimeout(() => {
        toast.classList.remove('visible');
        setTimeout(() => toast.remove(), 300);
    }, 3600);
}

// ------------------------------------------------------------
// 15. Contadores animados de la tira de estadísticas
// ------------------------------------------------------------
function initContadoresAnimados() {
    const nodos = document.querySelectorAll('[data-contador]');
    if (!nodos.length) return;
    nodos.forEach(nodo => {
        const destino = parseInt(nodo.dataset.contador, 10) || 0;
        const duracion = 900;
        const inicio = performance.now();
        function paso(ahora) {
            const progreso = Math.min((ahora - inicio) / duracion, 1);
            const valor = Math.round(destino * (1 - Math.pow(1 - progreso, 3))); // ease-out cúbico
            nodo.textContent = valor;
            if (progreso < 1) requestAnimationFrame(paso);
        }
        requestAnimationFrame(paso);
    });
}

// ------------------------------------------------------------
// 16. Botón flotante "volver arriba"
// ------------------------------------------------------------
function initBotonArriba() {
    const boton = document.getElementById('btn-arriba');
    if (!boton) return;
    window.addEventListener('scroll', () => {
        boton.hidden = window.scrollY < 420;
    });
    boton.addEventListener('click', () => window.scrollTo({ top: 0, behavior: 'smooth' }));
}

// ------------------------------------------------------------
// 17. Exportar la tabla de reportes visible (respeta los filtros
//     activos) a un archivo CSV, sin tocar el servidor
// ------------------------------------------------------------
function initExportarCSV() {
    const boton = document.getElementById('btn-exportar-csv');
    const tabla = document.getElementById('tabla-reportes');
    if (!boton || !tabla) return;

    boton.addEventListener('click', () => {
        const encabezados = [...tabla.querySelectorAll('thead th')]
            .map(th => th.textContent.trim())
            .filter(Boolean);

        const filas = [...tabla.querySelectorAll('tbody tr')].filter(tr => !tr.classList.contains('oculto-filtro'));
        if (!filas.length) { mostrarToast('No hay reportes visibles para exportar.', 'error'); return; }

        const lineas = [encabezados.join(',')];
        filas.forEach(tr => {
            const celdas = [...tr.querySelectorAll('td')].slice(0, encabezados.length).map(td => {
                const texto = td.textContent.trim().replace(/\s+/g, ' ');
                return `"${texto.replace(/"/g, '""')}"`;
            });
            lineas.push(celdas.join(','));
        });

        const blob = new Blob(['\uFEFF' + lineas.join('\n')], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const enlace = document.createElement('a');
        enlace.href = url;
        enlace.download = `sos-barrio-reportes-${new Date().toISOString().slice(0, 10)}.csv`;
        document.body.appendChild(enlace);
        enlace.click();
        enlace.remove();
        URL.revokeObjectURL(url);
        mostrarToast(`Exportados ${filas.length} reportes a CSV.`, 'ok');
    });
}

// ------------------------------------------------------------
// 18. Credencial vecinal digital: descargar el QR y/o imprimir
//     la tarjeta (el QR ya se generó vía initComunidadQR, que
//     reconoce cualquier nodo con [data-qr-url])
// ------------------------------------------------------------
function initCredencialQR() {
    const tarjeta = document.getElementById('credencial-imprimir');
    if (!tarjeta) return;

    const btnDescargar = document.getElementById('btn-descargar-credencial');
    if (btnDescargar) {
        btnDescargar.addEventListener('click', () => {
            const canvas = document.querySelector('#credencial-qr canvas');
            if (!canvas) { mostrarToast('El QR todavía se está generando, intenta de nuevo.', 'error'); return; }
            descargarCanvasComoImagen(canvas, 'sos-barrio-credencial-qr.png');
            mostrarToast('QR descargado.', 'ok');
        });
    }

    const btnImprimir = document.getElementById('btn-imprimir-credencial');
    if (btnImprimir) btnImprimir.addEventListener('click', () => window.print());
}

// ------------------------------------------------------------
// 19. Panel "Compartir / QR" del detalle de un reporte
// ------------------------------------------------------------
function initCompartirReporte() {
    const boton = document.getElementById('btn-compartir-reporte');
    const panel = document.getElementById('panel-compartir-reporte');
    if (!boton || !panel) return;

    boton.addEventListener('click', () => {
        panel.hidden = !panel.hidden;
        if (window.lucide) lucide.createIcons();
    });

    const btnImprimir = document.getElementById('btn-imprimir-reporte');
    if (btnImprimir) btnImprimir.addEventListener('click', () => window.print());
}

// ------------------------------------------------------------
// 20. Calificación del servicio (estrellas) en reportes cerrados
// ------------------------------------------------------------
function initCalificacion() {
    const panel = document.getElementById('panel-calificacion');
    const grupoEstrellas = document.getElementById('estrellas-input');
    if (!panel || !grupoEstrellas) return;

    let seleccion = 0;
    const botones = [...grupoEstrellas.querySelectorAll('.estrella')];

    function pintarSeleccion(valor) {
        botones.forEach(b => b.classList.toggle('activa', parseInt(b.dataset.valor, 10) <= valor));
    }

    botones.forEach(btn => {
        btn.addEventListener('mouseenter', () => pintarSeleccion(parseInt(btn.dataset.valor, 10)));
        btn.addEventListener('click', () => {
            seleccion = parseInt(btn.dataset.valor, 10);
            pintarSeleccion(seleccion);
        });
    });
    grupoEstrellas.addEventListener('mouseleave', () => pintarSeleccion(seleccion));

    const btnEnviar = document.getElementById('btn-enviar-calificacion');
    if (!btnEnviar) return;

    btnEnviar.addEventListener('click', () => {
        const estado = document.getElementById('estado-calificacion');
        if (!seleccion) {
            estado.textContent = 'Selecciona de 1 a 5 estrellas antes de enviar.';
            estado.className = 'estado-envio error';
            return;
        }
        btnEnviar.disabled = true;

        const datos = new FormData();
        datos.append('id_reporte', panel.dataset.idReporte);
        datos.append('calificacion', seleccion);
        datos.append('comentario', document.getElementById('comentario-calificacion').value.trim());

        fetch('index.php?controller=Reporte&action=calificar', { method: 'POST', body: datos, headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' } })
            .then(r => r.json())
            .then(resp => {
                if (resp.ok) {
                    estado.textContent = '¡Gracias por tu calificación!';
                    estado.className = 'estado-envio ok';
                    mostrarToast('Calificación enviada. ¡Gracias!', 'ok');
                    setTimeout(() => window.location.reload(), 900);
                } else {
                    estado.textContent = resp.error || 'No se pudo enviar la calificación.';
                    estado.className = 'estado-envio error';
                    btnEnviar.disabled = false;
                }
            })
            .catch(() => {
                estado.textContent = 'Error de conexión. Intenta de nuevo.';
                estado.className = 'estado-envio error';
                btnEnviar.disabled = false;
            });
    });
}

// ------------------------------------------------------------
// 21. Campanita de notificaciones (poll cada 30s vía AJAX)
// ------------------------------------------------------------
function initNotificaciones() {
    const boton = document.getElementById('btn-notificaciones');
    const dropdown = document.getElementById('dropdown-notificaciones');
    const badge = document.getElementById('badge-notificaciones');
    const lista = document.getElementById('lista-notificaciones');
    if (!boton || !dropdown) return;

    function cargar() {
        fetch('index.php?controller=Notificacion&action=listar')
            .then(r => r.json())
            .then(datos => {
                if (!datos.ok) return;
                badge.hidden = datos.noLeidas === 0;
                badge.textContent = datos.noLeidas > 9 ? '9+' : datos.noLeidas;

                if (!datos.items.length) {
                    lista.innerHTML = '<li class="notificacion-vacia">Todavía no tienes notificaciones.</li>';
                    return;
                }
                lista.innerHTML = datos.items.map(n => `
                    <li class="${n.leido == 0 ? 'no-leida' : ''}">
                        <span class="notificacion-tipo tipo-${(n.tipo || 'general').toLowerCase()}">${escaparHtml(n.tipo || 'General')}</span>
                        <p>${escaparHtml(n.mensaje)}</p>
                        <small>${escaparHtml(n.fecha_hora)}</small>
                    </li>
                `).join('');
            })
            .catch(() => {});
    }

    boton.addEventListener('click', () => {
        dropdown.hidden = !dropdown.hidden;
        if (!dropdown.hidden) cargar();
    });
    document.addEventListener('click', e => {
        if (!dropdown.hidden && !dropdown.contains(e.target) && e.target !== boton && !boton.contains(e.target)) {
            dropdown.hidden = true;
        }
    });

    const btnMarcar = document.getElementById('btn-marcar-leidas');
    if (btnMarcar) {
        btnMarcar.addEventListener('click', () => {
            fetch('index.php?controller=Notificacion&action=marcarLeidas', { method: 'POST', headers: { 'X-CSRF-Token': document.querySelector('meta[name=csrf-token]')?.content || '' } })
                .then(() => cargar());
        });
    }

    cargar();
    setInterval(cargar, 30000);
}

// ------------------------------------------------------------
// 22. Panel de zonas de riesgo en la página del mapa
// ------------------------------------------------------------
function initZonasRiesgoMapa() {
    const lista = document.getElementById('lista-zonas-riesgo');
    if (!lista) return;

    fetch('index.php?controller=Reporte&action=zonasriesgo')
        .then(r => r.json())
        .then(zonas => {
            if (!zonas.length) {
                lista.innerHTML = '<p class="mapa-nota">Aún no hay zonas de riesgo registradas.</p>';
                return;
            }
            lista.innerHTML = zonas.map(z => `
                <div class="zona-riesgo-item nivel-${z.nivel_riesgo.toLowerCase()}">
                    <span class="zona-riesgo-punto"></span>
                    <div class="zona-riesgo-info">
                        <strong>${escaparHtml(z.nombre_zona)}</strong>
                        <span>${escaparHtml(z.vecindario)} · ${z.cantidad_incidentes_mes} incidentes este mes</span>
                    </div>
                    <span class="badge badge-estado nivel-${z.nivel_riesgo.toLowerCase()}">${escaparHtml(z.nivel_riesgo)}</span>
                </div>
            `).join('');

            // Si el mapa general ya está pintado, agrega un círculo
            // translúcido por zona para reforzar la lectura visual
            const intentos = setInterval(() => {
                if (!window.__sosMapaGeneral) return;
                clearInterval(intentos);
                const colores = { Alto: '#C1443C', Medio: '#F2A93B', Bajo: '#3F8F5F' };
                zonas.forEach(z => {
                    if (!z.latitud || !z.longitud) return;
                    L.circle([z.latitud, z.longitud], {
                        radius: 350,
                        color: colores[z.nivel_riesgo] || '#0B1F33',
                        fillColor: colores[z.nivel_riesgo] || '#0B1F33',
                        fillOpacity: 0.12,
                        weight: 1,
                        dashArray: '4 4',
                    }).addTo(window.__sosMapaGeneral);
                });
            }, 200);
        })
        .catch(() => { lista.innerHTML = '<p class="mapa-nota">No se pudieron cargar las zonas de riesgo.</p>'; });
}

// ------------------------------------------------------------
// 23. Exportar/imprimir el resumen del dashboard
// ------------------------------------------------------------
function initImprimirDashboard() {
    const boton = document.getElementById('btn-imprimir-dashboard');
    if (!boton) return;
    boton.addEventListener('click', () => window.print());
}

// ------------------------------------------------------------
// 13. Tiempo de respuesta estimado en el formulario de reporte
//     (reutiliza el endpoint del dashboard, sin tocar SQL nuevo)
// ------------------------------------------------------------
function initTiempoEstimado() {
    const texto = document.getElementById('tiempo-estimado');
    const selectVecindario = document.getElementById('id_vecindario');
    if (!texto || !selectVecindario) return;

    fetch('index.php?controller=Dashboard&action=data')
        .then(r => r.json())
        .then(datos => {
            function actualizar() {
                const nombre = selectVecindario.selectedOptions[0]?.dataset.nombre;
                const indicador = (datos.indicadores || []).find(i => i.vecindario === nombre);
                if (indicador && indicador.tiempo_respuesta_prom) {
                    texto.textContent = `⏱ Tiempo de respuesta promedio en esta zona: ${parseFloat(indicador.tiempo_respuesta_prom).toFixed(0)} min.`;
                } else {
                    texto.textContent = '⏱ Aún no hay suficientes reportes cerrados en esta zona para estimar el tiempo de respuesta.';
                }
            }
            actualizar();
            selectVecindario.addEventListener('change', actualizar);
        })
        .catch(() => { texto.textContent = ''; });
}
