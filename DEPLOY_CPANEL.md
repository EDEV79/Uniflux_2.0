# Deploy en cPanel - adminhuamartentert.local

## 1) Requisitos

- PHP 7.4+ (recomendado PHP 8.1+)
- MySQL/MariaDB
- mod_rewrite opcional
- Sin Node.js y sin build obligatorio

## 2) Archivos a subir

Sube todo el contenido del proyecto al document root del dominio/subdominio en cPanel.

Ejemplo:

- public_html/adminhuamartentert.local/

## 3) Configuracion de entorno

1. Copia `.env.example` a `.env`
2. Ajusta credenciales:

- DB_HOST
- DB_PORT
- DB_NAME
- DB_USER
- DB_PASSWORD
- APP_URL
- APP_BASE_PATH (ej: adminhuamartentert.local si no cuelga en raiz)

## 4) Base de datos

1. Crea la base de datos en cPanel
2. Crea usuario y asigna permisos
3. Importa `usuario.sql` o el dump de produccion

## 5) Permisos

- Archivos: 644
- Carpetas: 755

## 6) Verificacion funcional

- Login
- Modulo comprobantes (crear, editar, eliminar)
- Generacion PDF
- Busqueda, paginacion y ordenamiento en tabla

## 7) Optimizacion recomendada

- Activar compresion GZIP en Apache
- Activar cache del navegador para CSS/JS
- Desactivar display_errors en produccion

## 8) Diagnostico rapido

Si una URL redirige mal, ajusta APP_BASE_PATH en `.env`.
Si no conecta DB, valida host y puerto desde cPanel.
