<?php
require_once __DIR__ . '/../../core/Auth.php';
$rutaActual = $_GET['action'] ?? 'index';
$controladorActual = $_GET['controller'] ?? 'Reporte';
function navActiva($controlador, $accion, $controladorActual, $rutaActual) {
    return ($controladorActual === $controlador && $rutaActual === $accion) ? 'activo' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
    <title>SOS Barrio - Seguridad Vecinal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Inter:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <script>try { document.documentElement.dataset.theme = localStorage.getItem('sos_tema') === 'oscuro' ? 'oscuro' : 'claro'; } catch(e) { document.documentElement.dataset.theme='claro'; }</script>
    <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>
    <header class="navbar">
        <div class="marca">
            <a href="index.php?controller=<?= Auth::estaConectado() ? 'Panel' : 'Reporte' ?>&action=index" class="logo-link">
                <span class="logo-icono"><i data-lucide="shield-alert"></i></span>
                <span class="logo-texto">SOS Barrio</span>
            </a>
            <span class="pulso-en-vivo"><span class="punto-pulso"></span> EN VIVO</span>
        </div>

        <button class="btn-hamburguesa" id="btn-hamburguesa" aria-label="Abrir menú">
            <i data-lucide="menu"></i>
        </button>

        <nav id="nav-principal">
            <a class="<?= navActiva('Reporte', 'index', $controladorActual, $rutaActual) ?>" href="index.php?controller=Reporte&action=index"><i data-lucide="list"></i> Reportes</a>
            <a class="<?= navActiva('Reporte', 'mapa', $controladorActual, $rutaActual) ?>" href="index.php?controller=Reporte&action=mapa"><i data-lucide="map"></i> Mapa</a>
            <a class="<?= navActiva('Comunidad', 'index', $controladorActual, $rutaActual) ?>" href="index.php?controller=Comunidad&action=index"><i data-lucide="users"></i> Comunidad</a>
            <?php if (Auth::estaConectado()): ?>
                <a class="<?= $controladorActual === 'Panel' ? 'activo' : '' ?>" href="index.php?controller=Panel&action=index"><i data-lucide="layout-dashboard"></i> Mi panel</a>
            <?php endif; ?>
            <?php if (Auth::esAdmin()): ?>
                <a class="<?= navActiva('Dashboard', 'index', $controladorActual, $rutaActual) ?>" href="index.php?controller=Dashboard&action=index"><i data-lucide="bar-chart-3"></i> Estadísticas</a>
                <a class="<?= $controladorActual === 'Admin' ? 'activo' : '' ?>" href="index.php?controller=Admin&action=usuarios"><i data-lucide="shield-check"></i> Usuarios</a>
            <?php endif; ?>
            <?php if (Auth::tieneRol(Auth::VECINO)): ?>
                <a class="<?= navActiva('Perfil', 'credencial', $controladorActual, $rutaActual) ?>" href="index.php?controller=Perfil&action=credencial"><i data-lucide="id-card"></i> Mi credencial</a>
            <?php endif; ?>
            <?php if (Auth::estaConectado()): ?>
                <a class="<?= navActiva('Perfil', 'seguridad', $controladorActual, $rutaActual) ?>" href="index.php?controller=Perfil&action=seguridad"><i data-lucide="lock"></i> Seguridad</a>
            <?php endif; ?>
            <?php if (!Auth::estaConectado() || Auth::tieneRol(Auth::VECINO)): ?>
                <a class="cta" href="index.php?controller=Reporte&action=create"><i data-lucide="plus"></i> Nuevo reporte</a>
            <?php endif; ?>

            <div class="sesion-usuario">
                <?php if (Auth::estaConectado()): ?>
                    <div class="campanita-envoltura">
                        <button id="btn-notificaciones" class="btn-modo-oscuro" title="Notificaciones" aria-label="Ver notificaciones">
                            <i data-lucide="bell"></i>
                            <span class="badge-notificaciones" id="badge-notificaciones" hidden>0</span>
                        </button>
                        <div class="dropdown-notificaciones" id="dropdown-notificaciones" hidden>
                            <div class="dropdown-notificaciones-cabecera">
                                <span>Notificaciones</span>
                                <button type="button" id="btn-marcar-leidas">Marcar todas leídas</button>
                            </div>
                            <ul id="lista-notificaciones">
                                <li class="notificacion-vacia">Cargando…</li>
                            </ul>
                        </div>
                    </div>
                <?php endif; ?>
                <button id="btn-modo-oscuro" class="btn-modo-oscuro" title="Cambiar tema" aria-label="Cambiar tema claro/oscuro">
                    <i data-lucide="moon"></i>
                </button>
                <?php if (Auth::estaConectado()): ?>
                    <div class="chip-usuario">
                        <i data-lucide="user-circle"></i>
                        <span><?= htmlspecialchars(Auth::nombre()) ?></span>
                    </div>
                    <form action="index.php?controller=Auth&action=logout" method="POST" class="form-logout">
                        <?= Csrf::campo() ?>
                        <button type="submit" class="salir" title="Cerrar sesión" aria-label="Cerrar sesión"><i data-lucide="log-out"></i></button>
                    </form>
                <?php else: ?>
                    <a class="entrar" href="index.php?controller=Auth&action=login"><i data-lucide="log-in"></i> Ingresar</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <div class="franja-baliza"></div>
    <main class="container">
