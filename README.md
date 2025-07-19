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
