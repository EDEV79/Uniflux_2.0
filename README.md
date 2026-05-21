# UniFlux 2.0

Plataforma SaaS multi-tenant desarrollada en PHP + MySQL, enfocada en operacion administrativa, control de clientes, suscripciones, comprobantes y gastos desde un panel unificado.

## Resumen para portfolio

UniFlux 2.0 es una evolucion de un sistema administrativo tradicional hacia una arquitectura mas ordenada y escalable para escenarios SaaS.

El proyecto integra:

- Control de acceso por roles y capacidades (RBAC).
- Gestion de clientes con estado de suscripcion.
- Flujo operativo de comprobantes y gastos.
- Dashboard con indicadores financieros.
- Generacion de comprobantes en PDF.

## Objetivo del proyecto

Construir una base realista para operar un producto administrativo multi-cliente, con seguridad, trazabilidad y una experiencia de uso moderna en entorno web.

## Tecnologias utilizadas

- PHP 8+
- MySQL / MariaDB
- HTML5
- CSS3
- JavaScript
- FPDF (documentos PDF)
- XAMPP (entorno local)

## Funcionalidades principales

- Autenticacion de usuarios.
- Recuperacion de contrasena con validaciones de seguridad.
- Gestion de clientes (usuarios) con roles y estado.
- Control de permisos por modulo/capacidad.
- Gestion de comprobantes (crear, listar, actualizar, eliminar).
- Gestion de gastos con filtros y paginacion.
- Gestion de solicitudes operativas.
- Dashboard con metricas clave de negocio.

## Arquitectura del proyecto

Estructura modular orientada a mantenimiento:

- `modules/`: dominios funcionales (`dashboard`, `comprobantes`, `gastos`, `clientes`, `servicios`).
- `components/`: bloques reutilizables de interfaz.
- `includes/`: bootstrap, layout y helpers globales.
- `connection/` y `config/`: conexion y configuracion.
- `database/`: scripts SQL de migracion y esquema.

## Seguridad implementada

- Tokens CSRF en formularios sensibles.
- Hash de contrasenas con `password_hash` y verificacion segura.
- Validaciones de acceso por rol/capacidad.
- Restricciones de acceso para modulos administrativos.
- Saneamiento basico de salida con helper de escape HTML.

## Ejecucion en local

1. Clonar el repositorio.
2. Copiar/ajustar variables en `.env` segun tu entorno local.
3. Crear la base de datos e importar esquema desde `database/schema_full.sql` o aplicar migraciones necesarias.
4. Levantar Apache + MySQL en XAMPP.
5. Abrir el proyecto en navegador (`http://localhost/Uniflux_2.0`).

## Estado actual

Proyecto activo en proceso de consolidacion SaaS, con mejoras recientes en:

- RBAC por capacidades.
- Normalizacion del flujo de login/recuperacion.
- Reorganizacion modular para mantenimiento y escalabilidad.

## Autor

Desarrollado como parte de mi portfolio profesional para demostrar:

- Backend con PHP y MySQL en casos reales.
- Refactor y modernizacion de sistemas heredados.
- Implementacion de seguridad y control de acceso en aplicaciones web.
