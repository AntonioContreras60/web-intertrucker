<?php
if (session_status() === PHP_SESSION_NONE) session_start();
if (!empty($_SESSION['impersonador_id'])) {
    echo '
    <div style="background:#ffc107;color:#000;padding:8px 14px;text-align:center;font-weight:bold;position:sticky;top:0;left:0;right:0;z-index:1000;">
      ⚠️  Modo impersonación activo — Estás viendo la cuenta de
      <strong>'.htmlspecialchars($_SESSION["nombre_usuario"]).'</strong>.
      <a href="/s14_ctrl/salir_impersonar.php" style="margin-left:20px;background:#d00;color:#fff;padding:6px 10px;border-radius:4px;text-decoration:none;">Salir</a>
    </div>';
}
?><!DOCTYPE html><html lang="es"><head><meta charset="UTF-8"><title>Header</title>
<style>
header{background:#001c7d;display:flex;align-items:center;justify-content:space-between;min-height:70px;padding:5px 20px;}
button:hover{color:#ffc107;}
#menuDropdown{display:none;position:absolute;top:100%;left:0;width:360px;max-height:72vh;overflow-y:auto;background:#fff;border-radius:8px;box-shadow:0 8px 22px rgba(0,0,0,.18);z-index:9999;}
.dp-item,.dp-subbtn{display:flex;align-items:center;gap:14px;padding:15px 24px;font-size:1.22em;font-weight:600;text-decoration:none;border:0;cursor:pointer;}
.dp-item{color:#0d0f12;background:#fff;}
.dp-item:hover,.dp-subbtn:hover{background:#f3f4f6;}
.dp-ico{width:26px;height:26px;filter:grayscale(1) brightness(0) invert(0);}
.dp-subbtn{
  width:100%;
  background:#f5f6f7;
  color:#0d0f12 !important;          /* ←– fuerza negro y visible   */
  border-radius:8px;margin:6px 0 0 0;
  text-align:left;outline:0;
}
.dp-arr{margin-left:auto;color:inherit;}
.dp-sub{
  display:none;flex-direction:column;
  background:#fff;border-left:3px solid #e2e3e6;border-radius:0 0 8px 8px;
  padding:0 0 6px 10px;
}
.dp-sub a{display:block;padding:10px 12px;font-size:1.05em;font-weight:500;color:#0d0f12;text-decoration:none;}
.dp-sub a:hover{background:#eef0f4;}
@media(max-width:600px){#logo img{display:none;} .dp-item,.dp-subbtn{font-size:1.2em!important;}}
</style>
</head><body>

<header>
  <!-- BOTÓN MENÚ -->
  <nav style="display:flex;align-items:center;position:relative;">
    <button onclick="toggleDropdown('menuDropdown')" style="padding:9px 16px;background:#0d1117;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer;">Menú</button>

    <div id="menuDropdown">
      <a class="dp-item" href="/portes_nuevos_recibidos.php"><img class="dp-ico" src="/imagenes/iconos/paquete.svg">Portes Nuevos</a>
      <a class="dp-item" href="/portes_cedidos.php"><img class="dp-ico" src="/imagenes/iconos/transferidos.svg">Portes Transferidos</a>
      <a class="dp-item" href="/portes_trucks.php"><img class="dp-ico" src="/imagenes/iconos/portes_camiones.svg">Portes a Camiones</a>
      <a class="dp-item" href="/listado_expedidor.php"><img class="dp-ico" src="/imagenes/iconos/package_truck_ramp.svg?v=1">Salida-Entrada Almacén</a>
      <a class="dp-item" href="/facturas.php"><img class="dp-ico" src="/imagenes/iconos/facturas.svg">Crear Facturas</a>
      <a class="dp-item" href="/my_network.php"><img class="dp-ico" src="/imagenes/iconos/mis_contactos.svg">Mis contactos</a>

      <!-- Gestión Interna -->
      <button class="dp-subbtn" onclick="toggleSub('giSub')"><img class="dp-ico" src="/imagenes/iconos/gestion_interna.svg">Gestión Interna<span class="dp-arr">▾</span></button>
      <div id="giSub" class="dp-sub">
        <a href="/my_truckers.php">Camioneros</a>
        <a href="/mis_asociados.php">Asociados</a>
        <a href="/my_trucks.php">Vehículos</a>
<?php if(!empty($_SESSION['rol']) && $_SESSION['rol']==='administrador'):?>
        <a href="/gestionar_colaboradores.php">Gestores</a>
<?php endif;?>
        <a href="/Perfil/gestionar_direcciones_empresa.php">Direcciones empresa</a>
      </div>

      <!-- Gestión Económica -->
      <button class="dp-subbtn" onclick="toggleSub('geSub')"><img class="dp-ico" src="/imagenes/iconos/gestion_economica.svg">Gestión Económica<span class="dp-arr">▾</span></button>
      <div id="geSub" class="dp-sub">
        <a href="/Perfil/portes_consumo_mensual.php">Consultar consumo mensual</a>
        <a href="/facturas_intertrucker.php">Facturas</a>
        <a href="/datos_facturacion.php">Datos de facturación</a>
      </div>

      <a class="dp-item" href="/Perfil/perfil_usuario.php"><img class="dp-ico" src="/imagenes/iconos/perfil.svg">Perfil</a>
      <a class="dp-item" href="/Perfil/logout.php"><img class="dp-ico" src="/imagenes/iconos/cerrar_sesion.svg">Cerrar sesión</a>
    </div>
  </nav>

  <!-- LOGO + USUARIO -->
  <div id="logo" style="text-align:center;">
     <a href="/portes_nuevos_recibidos.php"><img src="/imagenes/logos/intertrucker_chato.jpg" style="max-height:100px;width:auto;"></a>
     <div style="background:#001c7d;color:#fff;font-size:20px;"><?php echo $_SESSION['nombre_usuario']; ?></div>
  </div>

  <!-- LUPA -->
  <a href="/buscador_general.php" style="font-size:2rem;color:#fff;text-decoration:none;">🔍</a>
</header>

<script>
function toggleDropdown(id){
  const m=document.getElementById(id);
  m.style.display=(m.style.display==='block')?'none':'block';
  if(m.style.display==='none') document.querySelectorAll('.dp-sub').forEach(s=>s.style.display='none');
}
function toggleSub(id){
  const s=document.getElementById(id);
  s.style.display=(s.style.display==='block')?'none':'block';
  document.querySelectorAll('.dp-sub').forEach(o=>{if(o.id!==id) o.style.display='none';});
}
document.addEventListener('click',e=>{
  const m=document.getElementById('menuDropdown');
  const btn=document.querySelector('button[onclick^="toggleDropdown"]');
  if(m.style.display==='block' && !m.contains(e.target) && !btn.contains(e.target)){
      m.style.display='none';
      document.querySelectorAll('.dp-sub').forEach(s=>s.style.display='none');
  }
});
</script>
</body></html>
