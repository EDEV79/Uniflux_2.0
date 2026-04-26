# Deploy / Actualizacion en cPanel – UniFlux 2.0

> Guia valida tanto para instalacion nueva como para **actualizar** una instalacion existente.
> Incluye el modulo de **recuperacion de contrasena por SMS (OTP)** agregado en abril 2026.

---

## 1) Requisitos del servidor

| Requisito       | Minimo                     | Recomendado |
| --------------- | -------------------------- | ----------- |
| PHP             | 7.4                        | 8.1 +       |
| Extensiones PHP | mysqli, pdo_mysql, openssl | igual       |
| MySQL / MariaDB | 5.7                        | 10.4 +      |
| mod_rewrite     | opcional                   | activado    |
| Node.js         | NO requerido               | —           |

---

## 2) Archivos a subir (actualizacion)

### Opcion A – Subir todo (mas seguro)

Sube todo el contenido del proyecto reemplazando lo existente.
Carpeta destino en cPanel: `public_html/` o el subdirectorio de tu dominio.

### Opcion B – Solo archivos cambiados

Si ya tienes la app corriendo y solo quieres aplicar la actualizacion, sube estos archivos/carpetas:

```
cambiopass.php                  ← pagina de recuperacion de contrasena (NUEVA)
includes/bootstrap.php          ← actualizado: app_hash_password, app_pdo(), env SMS
includes/password_reset.php     ← NUEVO: logica OTP completa
includes/header.php             ← actualizado: enlace "Olvide mi contrasena"
index.php                       ← actualizado: rehash bcrypt al login
database/password_reset_sms_otp.sql   ← SQL referencia (la tabla se autocrea)
database/schema_full.sql        ← schema completo actualizado
.env.example                    ← referencia de variables nuevas
```

> **NOTA:** La tabla `password_reset_codes` se crea automaticamente la primera vez
> que alguien accede a `cambiopass.php`. No necesitas importar SQL manualmente para esa tabla.

---

## 3) Configuracion del archivo .env

En cPanel > File Manager, edita el archivo `.env` en la raiz del proyecto.
Si no existe, copia `.env.example` y renonbralo a `.env`.

```dotenv
APP_NAME="UniFlux"
APP_ENV=production

# Base de datos (usa los datos del cPanel MySQL)
DB_HOST=localhost
DB_PORT=3306
DB_NAME=cpanel_db_name        # nombre real de tu BD en cPanel
DB_USER=cpanel_db_user         # usuario de BD en cPanel
DB_PASSWORD=TuClaveAqui
DB_CHARSET=utf8mb4

# SMS para recuperacion de contrasena
# Opciones: vonage | twilio | log
# "log" = muestra el codigo en pantalla (no envia SMS real, util para pruebas/emergencias)
SMS_PROVIDER=vonage

# --- OPCION RECOMENDADA: Vonage (registro gratuito, sin tarjeta) ---
# Registro: https://dashboard.nexmo.com/sign-up
VONAGE_API_KEY=xxxxxxxx               # 8 caracteres, ej: a1b2c3d4
VONAGE_API_SECRET=xxxxxxxxxxxxxxxx    # 16 caracteres
VONAGE_FROM=UniFlux                   # nombre del remitente (max 11 caracteres)

# --- ALTERNATIVA: Twilio ---
# TWILIO_ACCOUNT_SID=ACxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
# TWILIO_AUTH_TOKEN=xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
# TWILIO_FROM=+1XXXXXXXXXX

# Politica de codigos OTP
PASSWORD_RESET_CODE_TTL_MINUTES=10    # minutos de validez del codigo
PASSWORD_RESET_MAX_ATTEMPTS=5         # intentos fallidos antes de invalidar
PASSWORD_RESET_RESEND_WAIT_SECONDS=60 # segundos de espera entre reenvios
```

### Si todavia no tienes cuenta SMS

Pon `SMS_PROVIDER=log`. El codigo OTP aparece visible en la pantalla de recuperacion
para que puedas ingresar sin SMS real (util en emergencias).

### Como crear cuenta Vonage gratis (5 minutos, sin tarjeta)

1. Ve a **https://dashboard.nexmo.com/sign-up**
2. Registrate con tu email
3. En el dashboard principal veras tu **API Key** (8 digitos) y **API Secret** (16 digitos)
4. Pegalos en el `.env` como `VONAGE_API_KEY` y `VONAGE_API_SECRET`
5. Cambia `SMS_PROVIDER=vonage`
6. Los creditos de prueba cubren envios a numeros de Panama (+507)

---

## 4) Base de datos

### Instalacion nueva

1. En cPanel > MySQL Databases: crea la BD, el usuario y asigna todos los permisos.
2. En cPanel > phpMyAdmin: importa `database/schema_full.sql`.
3. Edita `.env` con las credenciales.

### Actualizacion desde version anterior (sin SMS)

Solo necesitas crear la tabla nueva. Dos formas:

**Forma 1 – Automatica (recomendada):**
Accede a `https://tudominio.com/cambiopass.php` una vez.
La funcion `password_reset_ensure_schema()` crea la tabla sola.

**Forma 2 – Manual via phpMyAdmin:**
Importa o ejecuta el archivo `database/password_reset_sms_otp.sql`:

```sql
CREATE TABLE IF NOT EXISTS password_reset_codes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    celular VARCHAR(32) NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'pending',
    attempts INT NOT NULL DEFAULT 0,
    max_attempts INT NOT NULL DEFAULT 5,
    expires_at DATETIME NOT NULL,
    verified_at DATETIME NULL,
    used_at DATETIME NULL,
    sent_at DATETIME NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_password_reset_lookup (celular, status, expires_at),
    INDEX idx_password_reset_user (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

---

## 5) Permisos de archivos (cPanel File Manager)

| Tipo                                    | Permiso                                              |
| --------------------------------------- | ---------------------------------------------------- |
| Archivos `.php`, `.html`, `.css`, `.js` | 644                                                  |
| Carpetas                                | 755                                                  |
| Archivo `.env`                          | 600 (solo lectura del servidor)                      |
| Carpeta `tmp/`                          | 755 (NO subas archivos de esta carpeta a produccion) |

---

## 6) Verificacion post-despliegue

Sigue este orden de pruebas:

1. **Login normal** → `index.php` con un usuario existente.
2. **Rehash automatico** → si el usuario tenia SHA1, al logearse el sistema actualiza a bcrypt automaticamente.
3. **Recuperacion por SMS** → ir a `cambiopass.php`, ingresar celular, recibir codigo, cambiar contrasena.
4. **Modulo comprobantes** → crear, editar, eliminar, generar PDF.
5. **Modulo gastos** → CRUD completo.
6. **Dashboard** → graficas de ingresos, gastos y eventos.

---

## 7) Resetear contrasena de un usuario manualmente (emergencia)

Si un usuario no puede entrar y no tiene celular registrado, ejecuta esto en
cPanel > phpMyAdmin > pestaña SQL:

```sql
-- Reemplaza 'NuevaClave123!' y 'numero_de_usuario' con los valores reales
-- PRIMERO genera el hash en PHP: echo password_hash('NuevaClave123!', PASSWORD_DEFAULT);
-- Luego pega el hash resultante abajo:

UPDATE usuario
SET contrasena = '$2y$10$HASH_GENERADO_AQUI'
WHERE usuario = 'numero_de_usuario';
```

O usa cPanel > Terminal (si esta habilitada):

```bash
php -r "echo password_hash('NuevaClave123!', PASSWORD_DEFAULT);"
```

---

## 8) Diagnostico rapido

| Problema            | Causa probable                       | Solucion                                   |
| ------------------- | ------------------------------------ | ------------------------------------------ |
| Pagina en blanco    | `display_errors=Off` y hay error PHP | Revisa `error_log` en File Manager         |
| No conecta BD       | Host o credenciales incorrectas      | Verifica `.env` y permisos del usuario BD  |
| SMS no llega        | Twilio mal configurado               | Pon `SMS_PROVIDER=log` para probar         |
| Codigo OTP invalido | Reloj del servidor desincronizado    | Verifica timezone del servidor en cPanel   |
| Tabla no existe     | Primera vez con modulo SMS           | Accede a `cambiopass.php` o importa el SQL |
| Loop de redirect    | APP_BASE_PATH incorrecto             | Ajusta en `.env`                           |

---

## 9) Variables de entorno – referencia completa

Ver `.env.example` en la raiz del proyecto. Todas las variables tienen valor por defecto seguro excepto las credenciales de BD y Twilio.
