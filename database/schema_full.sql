SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS `uniflux_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `uniflux_db`;

DROP TABLE IF EXISTS `saas_role_permissions`;
DROP TABLE IF EXISTS `saas_permissions`;
DROP TABLE IF EXISTS `saas_roles`;
DROP TABLE IF EXISTS `password_reset_codes`;
DROP TABLE IF EXISTS `solicitudes`;
DROP TABLE IF EXISTS `comprobanteuber`;
DROP TABLE IF EXISTS `inventariouber`;
DROP TABLE IF EXISTS `gastos`;
DROP TABLE IF EXISTS `eventos`;
DROP TABLE IF EXISTS `usuario`;
DROP TABLE IF EXISTS `saas_tenants`;

CREATE TABLE `usuario` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nempleado` varchar(10) NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `apellido` varchar(50) NOT NULL,
  `email` varchar(50) NOT NULL,
  `celular` varchar(25) NOT NULL,
  `direccion` varchar(50) DEFAULT NULL,
  `dir_entrega` varchar(50) DEFAULT NULL,
  `usuario` varchar(50) NOT NULL DEFAULT '',
  `contrasena` varchar(255) NOT NULL DEFAULT '',
  `permiso` int(11) NOT NULL DEFAULT 0,
  `status` int(11) NOT NULL DEFAULT 1,
  `suscripcion_hasta` date DEFAULT NULL,
  `subscription_status` varchar(20) NOT NULL DEFAULT 'active',
  `ultimo_pago_at` datetime DEFAULT NULL,
  `bloqueado_motivo` varchar(255) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `blocked_capabilities` text DEFAULT NULL,
  `reg_date` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`, `nempleado`) USING BTREE,
  KEY `idx_usuario_celular` (`celular`),
  KEY `idx_usuario_usuario` (`usuario`),
  KEY `idx_usuario_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `eventos` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL DEFAULT '',
  `cedula` varchar(20) NOT NULL,
  `celular` varchar(50) NOT NULL DEFAULT '',
  `email` varchar(150) NOT NULL,
  `fecha` date NOT NULL,
  `hora` time NOT NULL,
  `lugar` varchar(150) NOT NULL,
  `precio_show` decimal(10,2) NOT NULL DEFAULT 0.00,
  `comentarios` varchar(255) NOT NULL,
  `estado` varchar(20) NOT NULL DEFAULT 'Pendiente',
  `creador` varchar(100) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_eventos_fecha` (`fecha`),
  KEY `idx_eventos_user` (`user_id`),
  KEY `idx_eventos_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `gastos` (
  `idgastos` int(11) NOT NULL AUTO_INCREMENT,
  `proveedor` varchar(50) DEFAULT '',
  `nfactura` varchar(50) DEFAULT '',
  `fecha` date DEFAULT NULL,
  `subtotal` decimal(50,2) DEFAULT NULL,
  `itbms` decimal(50,2) DEFAULT NULL,
  `total` decimal(50,2) DEFAULT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `vendedor` varchar(50) DEFAULT NULL,
  `printdate` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  `user_id` int(11) DEFAULT NULL,
  `tenant_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`idgastos`) USING BTREE,
  KEY `idx_gastos_fecha` (`fecha`),
  KEY `idx_gastos_user` (`user_id`),
  KEY `idx_gastos_tenant` (`tenant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci ROW_FORMAT=DYNAMIC;

CREATE TABLE `saas_tenants` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nombre` varchar(120) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `billing_email` varchar(120) DEFAULT NULL,
  `plan_code` varchar(50) NOT NULL DEFAULT 'base',
  `status` varchar(20) NOT NULL DEFAULT 'active',
  `subscription_status` varchar(20) NOT NULL DEFAULT 'active',
  `suscripcion_hasta` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_tenants_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `saas_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_key` varchar(50) NOT NULL,
  `role_name` varchar(80) NOT NULL,
  `level` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_roles_key` (`role_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `saas_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `permission_key` varchar(80) NOT NULL,
  `permission_name` varchar(120) NOT NULL,
  `module_key` varchar(80) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_saas_permissions_key` (`permission_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `saas_role_permissions` (
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`role_id`, `permission_id`),
  CONSTRAINT `fk_saas_role_permissions_role` FOREIGN KEY (`role_id`) REFERENCES `saas_roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_saas_role_permissions_permission` FOREIGN KEY (`permission_id`) REFERENCES `saas_permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `password_reset_codes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `celular` varchar(32) NOT NULL,
  `code_hash` varchar(255) NOT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'pending',
  `attempts` int(11) NOT NULL DEFAULT 0,
  `max_attempts` int(11) NOT NULL DEFAULT 5,
  `expires_at` datetime NOT NULL,
  `verified_at` datetime DEFAULT NULL,
  `used_at` datetime DEFAULT NULL,
  `sent_at` datetime NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_password_reset_lookup` (`celular`, `status`, `expires_at`),
  KEY `idx_password_reset_user` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `solicitudes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `nombre` varchar(120) NOT NULL,
  `detalle` text NOT NULL,
  `fecha` date DEFAULT NULL,
  `usuario` varchar(120) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_solicitudes_placa` (`placa`),
  KEY `idx_solicitudes_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `inventariouber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `placa` varchar(20) NOT NULL,
  `marca` varchar(80) DEFAULT NULL,
  `modelo` varchar(80) DEFAULT NULL,
  `anio` varchar(10) DEFAULT NULL,
  `status` varchar(20) NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inventariouber_placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE `comprobanteuber` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `socio_app` varchar(120) NOT NULL,
  `celular` varchar(25) NOT NULL,
  `placa` varchar(20) NOT NULL,
  `platdigital` varchar(50) DEFAULT NULL,
  `fecha_inicial` date DEFAULT NULL,
  `fecha_final` date DEFAULT NULL,
  `tarifa_neta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `cuota_semanal` decimal(10,2) NOT NULL DEFAULT 0.00,
  `adelantos_extras` decimal(10,2) NOT NULL DEFAULT 0.00,
  `abonos` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total_patrono` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comprobanteuber_celular` (`celular`),
  KEY `idx_comprobanteuber_placa` (`placa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `saas_tenants` (`id`, `nombre`, `slug`, `billing_email`, `plan_code`, `status`, `subscription_status`, `suscripcion_hasta`)
VALUES
  (1, 'Tenant Demo', 'tenant-demo', 'demo@uniflux.local', 'pro', 'active', 'active', '2026-12-31');

INSERT INTO `saas_roles` (`id`, `role_key`, `role_name`, `level`)
VALUES
  (1, 'cliente', 'Cliente', 0),
  (2, 'operador', 'Operador', 1),
  (3, 'administrador', 'Administrador', 2);

INSERT INTO `saas_permissions` (`id`, `permission_key`, `permission_name`, `module_key`)
VALUES
  (1, 'dashboard.view', 'Ver dashboard', 'dashboard'),
  (2, 'perfil.manage', 'Gestionar perfil', 'perfil'),
  (3, 'servicios.view', 'Ver servicios', 'servicios'),
  (4, 'servicios.manage', 'Gestionar servicios', 'servicios'),
  (5, 'comprobantes.manage', 'Gestionar comprobantes', 'comprobantes'),
  (6, 'gastos.manage', 'Gestionar gastos', 'gastos'),
  (7, 'solicitudes.manage', 'Gestionar solicitudes', 'solicitudes'),
  (8, 'clientes.manage', 'Gestionar clientes', 'clientes'),
  (9, 'rbac.manage', 'Gestionar RBAC', 'rbac');

INSERT INTO `saas_role_permissions` (`role_id`, `permission_id`)
VALUES
  (1, 1), (1, 2), (1, 3), (1, 4), (1, 5), (1, 6),
  (2, 1), (2, 2), (2, 3), (2, 4), (2, 5), (2, 6), (2, 7),
  (3, 1), (3, 2), (3, 3), (3, 4), (3, 5), (3, 6), (3, 7), (3, 8), (3, 9);

INSERT INTO `usuario` (`id`, `nempleado`, `nombre`, `apellido`, `email`, `celular`, `direccion`, `dir_entrega`, `usuario`, `contrasena`, `permiso`, `status`, `suscripcion_hasta`, `subscription_status`, `ultimo_pago_at`, `bloqueado_motivo`, `tenant_id`, `reg_date`)
VALUES
  (16, '0001', 'Edwin ', 'Ulate', 'capeulate@gmail.com', '65122433', 'Chanis', NULL, '65122433', '$2y$10$CZyHLjToaeFzM7Qf5G0vJOW.2cDmWuy8kF/CD4NJGroMf.WL8pQzO', 2, 1, '2026-12-31', 'active', NULL, NULL, 1, '2026-04-16 21:45:47'),
  (17, '0002', 'Aldo', 'Huamanchumo', 'aldoarielhm@gmail.com', '68811840', 'chanis', NULL, '68811840', '$2y$10$CZyHLjToaeFzM7Qf5G0vJOW.2cDmWuy8kF/CD4NJGroMf.WL8pQzO', 0, 1, '2026-12-31', 'active', NULL, NULL, 1, '2026-04-17 04:09:47');

INSERT INTO `eventos` (`id`, `nombre`, `apellido`, `cedula`, `celular`, `email`, `fecha`, `hora`, `lugar`, `precio_show`, `comentarios`, `estado`, `creador`, `user_id`, `tenant_id`, `creado_en`)
VALUES
  (4, 'Edwin Ulate', '', '8-729-759', '', 'capeulate@gmail.com', '2025-06-14', '21:00:00', 'Atlapa', 7000.00, 'Guaro para rokear', 'Pendiente', 'Edwin  Ulate', 16, NULL, '2025-06-12 21:44:53'),
  (5, 'Luigi Baretta', '', '9-999-999', '', 'Mercadeo@cottonstorepma.com', '2025-06-12', '16:00:00', 'Costo de Albrook', 900.00, 'Servicios Por Activacion y Videos Rikiplops celular 69839439', 'Pendiente', 'Edwin  Ulate', 16, NULL, '2025-06-12 22:22:19'),
  (6, 'Luigi Baretta', '', '9-999-999', '6983-9439', 'Mercadeo@cottonstorepma.com', '2025-06-12', '14:00:00', 'Costo de Albrook', 900.00, 'Servicios por activacion y videos Rikiplops celular 69839439 hora de 2:00 pm a 5:00 pm', 'Pendiente', 'Aldo Huamanchumo', 17, NULL, '2025-06-12 22:30:38'),
  (8, 'Aldo Ariel', '', '9-999-999', '6799-9999', 'soporte@henter.com', '2025-06-14', '15:00:00', 'albrok mall', 7000.00, 'Servicios por activacion y videos Rikiplops', 'Pendiente', 'Aldo Huamanchumo', 17, NULL, '2025-06-12 22:56:31'),
  (9, 'Edwin Alberto', '', '8-729-759', '65122433', 'capeulate@gmail.com', '2026-04-09', '19:30:00', 'albrook mall', 1500.00, 'reservas por evento con riquiplox en el mol w', 'Completado', 'Aldo Huamanchumo', 17, NULL, '2026-04-10 01:56:25');

ALTER TABLE `usuario` AUTO_INCREMENT = 18;
ALTER TABLE `eventos` AUTO_INCREMENT = 10;
ALTER TABLE `gastos` AUTO_INCREMENT = 3;
ALTER TABLE `saas_tenants` AUTO_INCREMENT = 2;
ALTER TABLE `saas_roles` AUTO_INCREMENT = 4;
ALTER TABLE `saas_permissions` AUTO_INCREMENT = 10;

SET FOREIGN_KEY_CHECKS = 1;

