<?php
// Verificar si la sesión no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inicializar variable para el nombre del camionero
$nombre_camionero = $nombre_camionero_header ?? "Desconocido";
?>

<header style="background-color:#001c7d; display: flex; align-items: center; justify-content: space-between; padding: 0px 10px;">
    <nav style="display: flex; align-items: center; flex-wrap: wrap;">
        <div style="position: relative;">
            <button onclick="toggleDropdown('menuDropdown')" style="padding: 30px 16px; background-color: rgba(0, 0, 0, 0.6); color: white; border-radius: 5px; font-weight: bold; font-size: 1.2em; border: none;">
                Menú
            </button>
            <div id="menuDropdown" style="display: none; position: absolute; top: 100%; left: 0; background-color: #f1f1f1; min-width: 345px; box-shadow: 0px 8px 16px rgba(0,0,0,0.2); z-index: 9999;">
                <a href="/tren_portes_camionero.php" style="display: block; padding: 16px 24px; color: black; font-size: 1.5em; text-decoration: none;">
                    <img src="/imagenes/iconos/portes_camiones.svg" alt="Portes a Camiones" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 12px;"> Portes a Camiones
                </a>
                <a href="/facturas_camionero.php" style="display: block; padding: 16px 24px; color: black; font-size: 1.5em; text-decoration: none;">
                    <img src="/imagenes/iconos/facturas.svg" alt="Facturas" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 12px;"> Facturas
                </a>
                <a href="Perfil/logout.php" style="display: block; padding: 16px 24px; color: black; font-size: 1.2em; text-decoration: none;">
                    <img src="/imagenes/iconos/cerrar_sesion.svg" alt="Cerrar sesión" style="width: 24px; height: 24px; vertical-align: middle; margin-right: 12px;"> Cerrar sesión
                </a>
            </div>
        </div>
    </nav>
    <div style="flex-grow: 1;"></div>
    <div id="logo" style="text-align: center;">
        <a style="max-width: 1000px;">
            <img src="/imagenes/logos/intertrucker_chato.jpg" alt="InterTrucker Logo" style="max-height: 100px; width: auto; height: auto; max-width: 100%;">
        </a>
        <div style='background: #001c7d; padding: 3px; color: white; font-size: 20px;'>
            <?php echo htmlspecialchars($nombre_camionero); ?>
        </div>
    </div>
    <div style="flex-grow: 1;"></div>
</header>

<script>
function toggleDropdown(id) {
    const dropdown = document.getElementById(id);
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}
</script>
