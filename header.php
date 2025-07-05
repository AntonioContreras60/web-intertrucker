<?php
/* Arranca sesión solo si aún no lo han hecho */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* Banner solo cuando vienes de impersonar */
if (!empty($_SESSION['impersonador_id'])) {
    echo '
    <div style="
        background:#ffc107;
        color:#000;
        padding:8px 14px;
        text-align:center;
        font-weight:bold;
        position:sticky;
        top:0;left:0;right:0;
        z-index:1000;">
        ⚠️  Modo impersonación activo — Estás viendo la cuenta de
        <strong>'.htmlspecialchars($_SESSION["nombre_usuario"]).'</strong>.
        <a href="/s14_ctrl/salir_impersonar.php"
           style="margin-left:20px;
                  background:#d00;color:#fff;
                  padding:6px 10px;border-radius:4px;
                  text-decoration:none;">
           Salir
        </a>
    </div>';
}
?>

<header class="site-header">
    <nav>
        <!-- Botón desplegable Menú -->
        <div class="menu-wrapper">
            <button class="menu-button" onclick="toggleDropdown('menuDropdown')">
                Menú
            </button>

            <div id="menuDropdown">
                <a href="/portes_nuevos_recibidos.php">
                    <img src="/imagenes/iconos/paquete.svg" alt="Portes Nuevos">
                    Portes Nuevos
                </a>
                <a href="/portes_cedidos.php">
                    <img src="/imagenes/iconos/transferidos.svg" alt="Portes Transferidos">
                    Portes Transferidos
                </a>
                <a href="/portes_trucks.php">
                    <img src="/imagenes/iconos/portes_camiones.svg" alt="Portes a Camiones">
                    Portes a Camiones
                </a>
                <a href="/listado_expedidor.php">
                    <img src="/imagenes/iconos/package_truck_ramp.svg?v=1" alt="Expedidor Destinatario">
                    Salida-Entrada Almacén
                </a>
                <a href="/facturas.php">
                    <img src="/imagenes/iconos/facturas.svg" alt="Facturas">
                    Facturas
                </a>
                <a href="/my_network.php">
                    <img src="/imagenes/iconos/mis_contactos.svg" alt="Mis contactos">
                    Mis contactos
                </a>

                <button class="gestion-btn" onclick="toggleDropdown('gestionInternaDropdown')">
                    Gestión Interna ▾
                </button>
                <div id="gestionInternaDropdown">
                    <a href="/my_truckers.php">
                        Camioneros
                    </a>
                    <a href="/mis_asociados.php">
                        Asociados
                    </a>
                    <a href="/my_trucks.php">
                        Vehículos
                    </a>
                    <a href="/gestionar_colaboradores.php">
                        Gestores
                    </a>
                </div>
                <a href="https://intertrucker.net/Perfil/perfil_usuario.php">
                    <img src="/imagenes/iconos/perfil.svg" alt="Mi Perfil">
                    Gestión
                </a>
                <a href="https://intertrucker.net/Perfil/logout.php">
                    <img src="/imagenes/iconos/cerrar_sesion.svg" alt="Cerrar sesión">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Espaciador para separar menú y logo -->
    <div class="flex-grow"></div>

    <!-- Contenedor del Logo y el nombre del usuario -->
    <div id="logo">
        <a href="/portes_nuevos_recibidos.php">
            <img src="/imagenes/logos/intertrucker_chato.jpg"
                 alt="InterTrucker Logo">
        </a>
        <div>
            <!-- Muestra el nombre del usuario -->
            <?php echo $_SESSION['nombre_usuario']; ?>
        </div>
    </div>

    <!-- Espaciador para alinear la lupa a la derecha -->
    <div class="flex-grow"></div>

    <!-- Enlace simple a buscador_general.php al pulsar la lupa -->
    <a href="https://intertrucker.net/buscador_general.php" class="search-link">
       🔍
    </a>
</header>
