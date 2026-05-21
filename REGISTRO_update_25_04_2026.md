# Registro de cambios - Uniflux 2.0

Fecha: 2026-04-25
Proyecto: Uniflux_2.0

## Resumen de cambios realizados hoy

1. Refactor del modulo de recuperacion de contrasena.
   - Se elimino el flujo anterior basado en SMS.
   - Se dejo un flujo clasico de recuperacion en 2 pasos.
   - La nueva validacion usa captcha matematico generado en servidor.
   - Se agrego rate limiting por sesion y proteccion CSRF.

2. Reescritura de la logica en `includes/password_reset.php`.
   - Se limpiaron restos del codigo anterior que habian quedado concatenados.
   - Se dejaron funciones para:
     - generar y validar captcha
     - controlar intentos fallidos
     - validar identidad del usuario
     - actualizar la contrasena con `password_hash`

3. Reescritura de la pagina `cambiopass.php`.
   - Se corrigio la vista para usuarios invitados y usuarios logueados.
   - Se corrigio un problema visual donde la tarjeta quedaba invisible por no cargar `js/admin-ui.js`.
   - Se ajusto el formulario para que renderice correctamente con los estilos existentes.

4. Cambio de criterio de validacion de identidad.
   - Inicialmente se habia dejado `cedula/usuario + numero de empleado`.
   - Luego se cambio a `cedula/usuario + correo electronico`.
   - Finalmente se ajusto a `celular + correo electronico`, que es el flujo vigente.
   - La validacion actual consulta la tabla `usuario` usando:
     - columna `celular`
     - columna `email`
   - El celular se normaliza a solo digitos para evitar errores por espacios, guiones o parentesis.

5. Limpieza de configuracion local.
   - Se eliminaron del `.env` local las variables SMS que ya no se usan.
   - No se requirieron cambios en base de datos para este ajuste.

## Archivos modificados hoy

- `cambiopass.php`
- `includes/password_reset.php`
- `.env`

## Archivos que se deben subir al servidor

- `cambiopass.php`
- `includes/password_reset.php`

## Validaciones realizadas

- Verificacion de sintaxis con `php -l` en:
  - `cambiopass.php`
  - `includes/password_reset.php`
- Verificacion visual del formulario en navegador local.
- Confirmacion de que la pantalla carga y muestra el flujo con `celular + correo electronico`.

## Estado actual

- La pagina `cambiopass.php` carga correctamente.
- El formulario de recuperacion se muestra correctamente.
- La validacion de identidad vigente es por `celular + correo electronico`.
- El servidor solo necesita recibir los 2 archivos PHP actualizados.
