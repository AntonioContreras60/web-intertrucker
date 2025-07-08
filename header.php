<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Header InterTrucker</title>
    <style>
        /* Estilo general del header */
        header {
            background-color: #001c7d;
            display: flex;
            align-items: center;
            justify-content: space-between;
            /* Ajusta la altura mínima */
            min-height: 70px;
            /* Si deseas quitar cualquier límite de altura máxima, simplemente comenta o elimina esta línea */
            /* max-height: 200px; */
            /* Ajusta el padding para reducir o ampliar la distancia interior */
            padding: 5px 20px;
        }

        /* Estilo del botón (Menú, etc.) */
        button:hover {
            color: #ffc107;
        }

        /* Media query para ocultar solo la imagen del logo en pantallas pequeñas (por debajo de 600px) */
        @media (max-width: 600px) {
            #logo img {
                display: none;
            }
        }

        /*
         * NUEVA media query para ajustar el tamaño de letra en móviles
         * Con !important para que sobrescriba los estilos en línea.
         */
        @media (max-width: 600px) {
            button {
                font-size: 1.2em !important;
            }
            #menuDropdown a {
                font-size: 1.2em !important;
            }
        }
    </style>
</head>
<body>
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

<header style="background-color:#001c7d; display: flex; align-items: center; justify-content: space-between; padding: 0px 10px;">
    <nav style="display: flex; align-items: center; flex-wrap: wrap;">
        <!-- Botón desplegable Menú -->
        <div style="position: relative;">
            <button onclick="toggleDropdown('menuDropdown')" 
                    style="padding: 10px 16px; 
                           background-color: rgba(0, 0, 0, 0.6); 
                           color: white; 
                           border-radius: 5px; 
                           font-weight: bold; 
                           font-size: 1em; 
                           border: none;">
                Menú
            </button>

            <div id="menuDropdown" 
                 style="display: none; 
                        position: absolute; 
                        top: 100%; 
                        left: 0; 
                        background-color: #f1f1f1; 
                        min-width: 345px; 
                        box-shadow: 0px 8px 16px rgba(0,0,0,0.2); 
                        z-index: 9999; 
                        max-height: 70vh; 
                        overflow-y: auto;">
                <a href="/portes_nuevos_recibidos.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/paquete.svg" alt="Portes Nuevos" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Portes Nuevos
                </a>
                <a href="/portes_cedidos.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/transferidos.svg" alt="Portes Transferidos" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Portes Transferidos
                </a>
                <a href="/portes_trucks.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/portes_camiones.svg" alt="Portes a Camiones" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Portes a Camiones
                </a>
                <a href="/listado_expedidor.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/package_truck_ramp.svg?v=1" alt="Expedidor Destinatario" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Salida-Entrada Almacén
                </a>
                <a href="/facturas.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/facturas.svg" alt="Facturas" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Crear Facturas
                </a>
                <a href="/my_network.php"
                   style="display: block;
                          padding: 16px 24px;
                          color: black;
                          font-size: 1em;
                          text-decoration: none;">
                    <img src="/imagenes/iconos/mis_contactos.svg" alt="Mis contactos"
                         style="width: 24px;
                                height: 24px;
                                vertical-align: middle;
                                margin-right: 12px;">
                    Mis contactos
                </a>

                <button onclick="toggleDropdown('gestionInternaDropdown')"
                        style="display: block;
                               width: 100%;
                               padding: 16px 24px;
                               background: none;
                               border: none;
                               color: black !important;
                               font-size: 1em;
                               text-align: left;">
                    Gestión Interna ▾
                </button>
                <div id="gestionInternaDropdown" style="display:none;">
                    <a href="/my_truckers.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Camioneros
                    </a>
                    <a href="/mis_asociados.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Asociados
                    </a>
                    <a href="/my_trucks.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Vehículos
                    </a>
                    <a href="/gestionar_colaboradores.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Gestores
                    </a>
                </div>
                <button onclick="toggleDropdown('gestionEconomicaDropdown')"
                        style="display: block;
                               width: 100%;
                               padding: 16px 24px;
                               background: none;
                               border: none;
                               color: black !important;
                               font-size: 1em;
                               text-align: left;">
                    Gestión Económica ▾
                </button>
                <div id="gestionEconomicaDropdown" style="display:none;">
                    <a href="/Perfil/portes_consumo_mensual.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Consultar consumo mensual
                    </a>
                    <a href="/facturas_intertrucker.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Facturas
                    </a>
                    <a href="/datos_facturacion.php"
                       style="display: block;
                              padding: 16px 24px;
                              padding-left: 40px;
                              color: black;
                              font-size: 1em;
                              text-decoration: none;">
                        Datos de facturación
                    </a>
                </div>
                <a href="https://intertrucker.net/Perfil/perfil_usuario.php"
                style="display: block;
                          padding: 16px 24px;
                          color: black;
                          font-size: 1em;
                          text-decoration: none;">
                    <img src="/imagenes/iconos/perfil.svg" alt="Mi Perfil"
                         style="width: 24px;
                                height: 24px;
                                vertical-align: middle;
                                margin-right: 12px;">
                    Perfil
                </a>
                <a href="https://intertrucker.net/Perfil/logout.php" 
                   style="display: block; 
                          padding: 16px 24px; 
                          color: black; 
                          font-size: 1em; 
                          text-decoration: none;">
                    <img src="/imagenes/iconos/cerrar_sesion.svg" alt="Cerrar sesión" 
                         style="width: 24px; 
                                height: 24px; 
                                vertical-align: middle; 
                                margin-right: 12px;">
                    Cerrar sesión
                </a>
            </div>
        </div>
    </nav>

    <!-- Espaciador para separar menú y logo -->
    <div style="flex-grow: 1;"></div>

    <!-- Contenedor del Logo y el nombre del usuario -->
    <div id="logo" style="text-align: center;">
        <a href="/portes_nuevos_recibidos.php" style="max-width: 1000px;">
            <img src="/imagenes/logos/intertrucker_chato.jpg" 
                 alt="InterTrucker Logo" 
                 style="max-height: 100px; 
                        width: auto; 
                        height: auto; 
                        max-width: 100%;">
        </a>
        <div style="background: #001c7d; padding: 3px; color: white; font-size: 20px;">
            <!-- Muestra el nombre del usuario -->
            <?php echo $_SESSION['nombre_usuario']; ?>
        </div>
    </div>

    <!-- Espaciador para alinear la lupa a la derecha -->
    <div style="flex-grow: 1;"></div>

    <!-- Enlace simple a buscador_general.php al pulsar la lupa -->
    <a href="https://intertrucker.net/buscador_general.php" 
       style="font-size:2rem; color:white; text-decoration:none;">
       🔍
    </a>
</header>

<!-- ========= SCRIPT para MENÚ DESPLEGABLE (mantenemos solo esto) ========= -->
<script>
function toggleDropdown(id) {
    const d = document.getElementById(id);
    d.style.display = (d.style.display === 'block') ? 'none' : 'block';
}
</script>

</body>
</html>
