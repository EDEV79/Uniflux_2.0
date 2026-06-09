# UniFlux 2.0 - subida por bloques a cPanel

Estructura objetivo en este servidor:

```text
/home1/usuario/
|-- apps/
|   |-- uniflux/
|   `-- shared/
|-- logs_apps/
|   `-- uniflux/
|-- uniflux.space/
|   |-- index.php
|   |-- assets/
|   `-- .htaccess
|-- huamartickets.com/
`-- public_html/
```

## Bloque 1 - codigo privado

Subir `apps/uniflux/` a:

```text
/home1/usuario/apps/uniflux/
```

Este bloque contiene configuracion, conexion, modulos, componentes, FPDF, SQL y `.env`.
No debe quedar dentro de `public_html`.

## Bloque 2 - archivos publicos

Subir `uniflux.space/` a:

```text
/home1/usuario/uniflux.space/
```

Este bloque contiene solo puntos de entrada PHP y assets publicos (`assets/css`, `assets/js`, `assets/img`).
La ruta de ingreso sera:

```text
https://uniflux.space/
```

## Bloque 3 - soporte

Crear si no existen:

```text
/home1/usuario/logs_apps/uniflux/
/home1/usuario/apps/shared/
```

`logs_apps/uniflux/` queda reservado para logs propios fuera del webroot.

## Configuracion

En `/home1/usuario/apps/uniflux/.env`:

```dotenv
APP_ENV=production
APP_BASE_PATH=
APP_DEBUG=false
```

Ajustar tambien `DB_NAME`, `DB_USER` y `DB_PASSWORD` con los datos reales de cPanel.

## Verificacion

Probar en este orden:

1. `https://uniflux.space/`
2. Login.
3. Dashboard.
4. Comprobantes y PDF.
5. Gastos.
6. Clientes admin.
7. Solicitudes.
