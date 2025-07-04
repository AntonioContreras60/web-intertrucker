<?php
// Verificar si la sesión no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar variable para el nombre del camionero
$nombre_camionero = $nombre_camionero_header ?? "Desconocido";
?>
<!-- Recursos de estilos y scripts para el header -->
<link rel="stylesheet" href="/header.css">
<script src="/header.js"></script>

<header class="site-header">
    <nav>
        <div class="menu-wrapper">
            <button class="menu-button" onclick="toggleDropdown('menuDropdown')">
                Menú
            </button>
            <div id="menuDropdown">
                <a href="/tren_portes_camionero.php">
                    <img src="/imagenes/iconos/portes_camiones.svg" alt="Portes a Camiones"> Portes a Camiones
                </a>
                <a href="/facturas_camionero.php">
                    <img src="/imagenes/iconos/facturas.svg" alt="Facturas"> Facturas
                </a>
                <a href="Perfil/logout.php">
                    <img src="/imagenes/iconos/cerrar_sesion.svg" alt="Cerrar sesión"> Cerrar sesión
                </a>
            </div>
        </div>
    </nav>
    <div class="flex-grow"></div>
    <div id="logo">
        <a href="/tren_portes_camionero.php">
            <img src="/imagenes/logos/intertrucker_chato.jpg" alt="InterTrucker Logo">
        </a>
        <div>
            <?php echo htmlspecialchars($nombre_camionero); ?>
        </div>
    </div>
    <div class="flex-grow"></div>
</header>

