<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>InterTrucker – Registro de Empresa</title>
<style>
  /* ---------- Cabecera y aspecto general ---------- */
  header{background:#001C80;padding:20px 0;text-align:center}
  .logo img{max-width:200px}
  main{padding:20px;font-family:Arial,sans-serif}
  h1{color:#007bff;text-align:center}

  /* ---------- Formulario ---------- */
  form{max-width:450px;margin:20px auto}
  label{display:block;margin-top:1em;font-weight:bold}

  /* ancho completo solo para inputs de texto / email / tel / password y los select */
  input:not([type="checkbox"]):not([type="radio"]),
  select{
    width:100%;
    padding:10px;
    margin-top:5px;
    box-sizing:border-box;
  }

  /* excepción: checkbox y radio mantienen su tamaño natural */
  input[type="checkbox"],
  input[type="radio"]{
    width:auto;
  }

  button{
    margin-top:1em;
    padding:10px 20px;
    font-size:1em;
    color:#fff;
    background:#007bff;
    border:none;
    border-radius:5px;
    cursor:pointer
  }
  #camposAdmin{display:none;margin-bottom:1em}
  #camposCamionero{display:none;margin-top:1em}

  /* Radios alineados */
  .radio-group{display:flex;gap:25px;align-items:center;margin-top:0.5em}
  .radio-inline{display:flex;align-items:center;gap:5px;font-weight:normal}

  /* alineación de la casilla “soy también camionero” */
  label input[type="checkbox"]{
    vertical-align:middle;   /* centra la cajita */
    margin-right:6px;        /* separa cajita y texto */
  }
</style>

</head>
<body>

<header>
    <div class="logo"><img src="/imagenes/logos/logo2.png" alt="InterTrucker Logo"></div>
</header>

<main>
    <h1>Registro de Empresa</h1>
    <p>Este usuario adquiere el rol “administrador” y gestiona toda la empresa.</p>

    <form action="procesar_registro.php" method="POST">
        <!-- ==== Datos de empresa ==== -->
        <label for="nombre_empresa">Nombre de la Empresa:</label>
        <input id="nombre_empresa" name="nombre_empresa" type="text" required minlength="2" maxlength="255"
               pattern="^[A-Za-z0-9\s\.\,\-\&\(\)\']+$"
               title="Solo letras, números, espacios y . , - & ( ).">

        <!-- Coincidencia con admin -->
        <label>¿Coincide con el nombre del administrador?</label>
        <div class="radio-group">
            <label class="radio-inline">
                <input type="radio" id="coincide_si" name="coincide_admin" value="si" checked> Sí
            </label>
            <label class="radio-inline">
                <input type="radio" id="coincide_no" name="coincide_admin" value="no"> No
            </label>
        </div>

        <!-- Datos admin distintos -->
        <div id="camposAdmin">
            <label for="nombre_usuario">Nombre del Administrador:</label>
            <input id="nombre_usuario" name="nombre_usuario" type="text"
                   pattern="^[A-Za-z0-9\s\.\,\-\&\(\)\']+$"
                   title="Solo letras, números y . , - & ( ).">

            <label for="apellidos">Apellidos del Administrador:</label>
            <input id="apellidos" name="apellidos" type="text"
                   pattern="^[A-Za-z0-9\s\.\,\-\&\(\)\']+$"
                   title="Solo letras, números y . , - & ( ).">
        </div>

        <!-- Email y contraseña -->
        <label for="email">Correo Electrónico:</label>
        <input id="email" name="email" type="email" required
               pattern="^[\\w\\.-]+@[a-zA-Z\\d\\.-]+\\.[a-zA-Z]{2,6}$"
               title="Correo electrónico válido.">

        <label for="contrasena">Contraseña:</label>
        <input id="contrasena" name="contrasena" type="password" required minlength="8">
        <button type="button" onclick="togglePassword('contrasena')">Mostrar</button>

        <label for="confirmar_contrasena">Confirmar Contraseña:</label>
        <input id="confirmar_contrasena" name="confirmar_contrasena" type="password" required minlength="8">
        <button type="button" onclick="togglePassword('confirmar_contrasena')">Mostrar</button>

        <!-- CIF -->
        <label for="cif">CIF / NIF / VAT:</label>
        <input id="cif" name="cif" type="text" required minlength="9" maxlength="20" pattern="^[A-Za-z0-9]+$"
               title="Solo letras y números.">

        <!-- ==== Dirección fiscal ==== -->
        <label for="nombre_via">Calle / Vía:</label>
        <input id="nombre_via" name="nombre_via" type="text" required maxlength="255">

        <label for="numero_via">Número:</label>
        <input id="numero_via" name="numero_via" type="text" required maxlength="50">

        <label for="complemento">Complemento (opcional):</label>
        <input id="complemento" name="complemento" type="text" maxlength="255">

        <label for="codigo_postal">Código Postal:</label>
        <input id="codigo_postal" name="codigo_postal" type="text" required maxlength="20">

        <label for="ciudad">Ciudad:</label>
        <input id="ciudad" name="ciudad" type="text" required maxlength="100">

        <label for="estado_provincia">Provincia / Estado:</label>
        <input id="estado_provincia" name="estado_provincia" type="text" required maxlength="100">

        <!-- País -->
        <label for="pais">País:</label>
        <select id="pais" name="pais" required onchange="prefijarTelefono()">
            <option value="">--Selecciona tu país--</option>
            <option value="España" data-pref="+34">España (+34)</option>
            <option value="Francia" data-pref="+33">Francia (+33)</option>
            <option value="Portugal" data-pref="+351">Portugal (+351)</option>
            <option value="Alemania" data-pref="+49">Alemania (+49)</option>
            <option value="Estados Unidos" data-pref="+1">Estados Unidos (+1)</option>
            <option value="México" data-pref="+52">México (+52)</option>
            <!-- … añade el resto de países que ya tenías … -->
        </select>

        <!-- Teléfono -->
        <label for="telefono">Teléfono de Contacto:</label>
        <input id="telefono" name="telefono" type="tel" required
               pattern="^\+?[0-9\s\-]{7,15}$"
               title="Entre 7 y 15 dígitos, con prefijo internacional.">

        <!-- Checkbox camionero -->
        <label style="margin-top:1em;">
            <input type="checkbox" id="es_camionero" name="es_camionero"> Soy también camionero
        </label>

        <!-- Datos de camionero -->
        <div id="camposCamionero">
            <label for="tipo_carnet">Tipo de carnet:</label>
            <select id="tipo_carnet" name="tipo_carnet">
                <option value="C+E">C+E (tráiler)</option>
                <option value="C">C (rígido &lt;32 t)</option>
                <option value="B">B</option>
            </select>
        </div>

        <button type="submit">Registrarse</button>
    </form>

    <p style="margin-top:1em">
        Una vez confirmes el email, podrás iniciar sesión y gestionar tu empresa.<br>
        Mientras no haya gestores inscritos, no aparecerá la sección “Compañeros” en Portes Nuevos.
    </p>
</main>

<?php include '../footer.php'; ?>

<script>
/* ---------- Scripts ---------- */
function togglePassword(id){
    const inp=document.getElementById(id);
    const btn=inp.nextElementSibling;
    if(inp.type==='password'){inp.type='text';btn.textContent='Ocultar';}
    else{inp.type='password';btn.textContent='Mostrar';}
}
function toggleCamposAdmin(){
    document.getElementById('camposAdmin').style.display=
        document.querySelector('input[name="coincide_admin"]:checked').value==='no'?'block':'none';
}
function toggleCamposCamionero(){
    const chk=document.getElementById('es_camionero');
    const div=document.getElementById('camposCamionero');
    const sel=document.getElementById('tipo_carnet');
    if(chk.checked){div.style.display='block';sel.required=true;}
    else{div.style.display='none';sel.required=false;}
}
function prefijarTelefono(){
    const sel=document.getElementById('pais');
    const tel=document.getElementById('telefono');
    const pref=sel.options[sel.selectedIndex].dataset.pref||'';
    if(pref){tel.value=pref+' ';tel.focus();}
}
document.querySelector('form').addEventListener('submit',e=>{
    if(document.getElementById('contrasena').value!==document.getElementById('confirmar_contrasena').value){
        alert('Las contraseñas no coinciden.');e.preventDefault();
    }
});
document.addEventListener('DOMContentLoaded',()=>{
    toggleCamposAdmin();
    toggleCamposCamionero();
    document.querySelectorAll('input[name="coincide_admin"]').forEach(r=>r.addEventListener('change',toggleCamposAdmin));
    document.getElementById('es_camionero').addEventListener('change',toggleCamposCamionero);
});
</script>

</body>
</html>
