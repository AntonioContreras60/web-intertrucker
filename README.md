# InterTrucker Web

This project requires database connection credentials provided via environment variables.

Set the following variables in your environment or in a `.env` file before running any PHP scripts:

```
DB_HOST=your-database-host
DB_USER=your-database-user
DB_PASS=your-database-password
DB_NAME=your-database-name
```

An example file is provided as `.env.example`.

## Autologin desde la app

Para permitir que gestores y administradores accedan a la web sin volver a introducir credenciales, la app puede solicitar un token temporal a `/api/autologin.php`:

1. Realiza una petición `POST` con el parámetro `usuario_id` y encabezado `Authorization: Bearer <token_sesion>`.
2. La respuesta incluye una URL de autologin que contiene el token seguro.
3. Abre esa URL en el navegador del dispositivo para iniciar sesión en la web.

El token es válido durante un minuto y solo puede usarse una vez. Al visitarlo, `autologin_mecanismo.php` valida el token, crea la sesión PHP y redirige a la página principal. Si el usuario cierra la sesión web, simplemente solicita un nuevo token desde la app y abra la URL resultante.

> **Note**
> El script `login_directo.php` se ha eliminado. Utiliza siempre la URL devuelta por `/api/autologin.php`, que redirige a `api/autologin_mecanismo.php` para completar el inicio de sesión.

## Limpieza de tokens de autologin

El script `cleanup_autologin.php` elimina de la tabla `autologin_tokens` los registros caducados (campo `fecha_expira` en el pasado) o que ya han sido usados (`usado=1`).

Ejecuta manualmente con:

```
php cleanup_autologin.php
```

Puedes programar su ejecución periódica mediante `cron`. Por ejemplo, para ejecutarlo una vez cada hora añade una línea similar a esta:

```
0 * * * * /usr/bin/php /ruta/a/web-intertrucker/cleanup_autologin.php >/dev/null 2>&1
```

## Actualización de la base de datos

Se ha añadido un índice único sobre el campo `token` de la tabla `autologin_tokens`.
Para actualizar una instalación existente ejecuta el siguiente comando SQL:

```sql
ALTER TABLE autologin_tokens ADD UNIQUE KEY `token` (`token`);
```

