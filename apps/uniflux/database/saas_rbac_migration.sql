ALTER TABLE usuario
    ADD COLUMN IF NOT EXISTS suscripcion_hasta DATE NULL AFTER status,
    ADD COLUMN IF NOT EXISTS subscription_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER suscripcion_hasta,
    ADD COLUMN IF NOT EXISTS ultimo_pago_at DATETIME NULL AFTER subscription_status,
    ADD COLUMN IF NOT EXISTS bloqueado_motivo VARCHAR(255) NULL AFTER ultimo_pago_at,
    ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER bloqueado_motivo,
    ADD COLUMN IF NOT EXISTS blocked_capabilities TEXT NULL AFTER tenant_id;

ALTER TABLE eventos
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER creador,
    ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER user_id;

ALTER TABLE gastos
    ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER printdate,
    ADD COLUMN IF NOT EXISTS tenant_id INT NULL AFTER user_id;

CREATE TABLE IF NOT EXISTS saas_tenants (
    id INT NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(120) NOT NULL,
    slug VARCHAR(120) NOT NULL,
    billing_email VARCHAR(120) NULL,
    plan_code VARCHAR(50) NOT NULL DEFAULT 'base',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    subscription_status VARCHAR(20) NOT NULL DEFAULT 'active',
    suscripcion_hasta DATE NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saas_tenants_slug (slug)
);

CREATE TABLE IF NOT EXISTS saas_roles (
    id INT NOT NULL AUTO_INCREMENT,
    role_key VARCHAR(50) NOT NULL,
    role_name VARCHAR(80) NOT NULL,
    level INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saas_roles_key (role_key)
);

CREATE TABLE IF NOT EXISTS saas_permissions (
    id INT NOT NULL AUTO_INCREMENT,
    permission_key VARCHAR(80) NOT NULL,
    permission_name VARCHAR(120) NOT NULL,
    module_key VARCHAR(80) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_saas_permissions_key (permission_key)
);

CREATE TABLE IF NOT EXISTS saas_role_permissions (
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (role_id, permission_id),
    CONSTRAINT fk_saas_role_permissions_role FOREIGN KEY (role_id) REFERENCES saas_roles (id) ON DELETE CASCADE,
    CONSTRAINT fk_saas_role_permissions_permission FOREIGN KEY (permission_id) REFERENCES saas_permissions (id) ON DELETE CASCADE
);

INSERT INTO saas_tenants (id, nombre, slug, billing_email, plan_code, status, subscription_status, suscripcion_hasta)
VALUES (1, 'Tenant Demo', 'tenant-demo', 'demo@ahe.local', 'pro', 'active', 'active', '2026-12-31')
ON DUPLICATE KEY UPDATE
    nombre = VALUES(nombre),
    billing_email = VALUES(billing_email),
    plan_code = VALUES(plan_code),
    status = VALUES(status),
    subscription_status = VALUES(subscription_status),
    suscripcion_hasta = VALUES(suscripcion_hasta);

INSERT INTO saas_roles (role_key, role_name, level)
VALUES
    ('cliente', 'Cliente', 0),
    ('operador', 'Operador', 1),
    ('administrador', 'Administrador', 2)
ON DUPLICATE KEY UPDATE
    role_name = VALUES(role_name),
    level = VALUES(level);

INSERT INTO saas_permissions (permission_key, permission_name, module_key)
VALUES
    ('dashboard.view', 'Ver dashboard', 'dashboard'),
    ('perfil.manage', 'Gestionar perfil', 'perfil'),
    ('servicios.view', 'Ver servicios', 'servicios'),
    ('servicios.manage', 'Gestionar servicios', 'servicios'),
    ('comprobantes.manage', 'Gestionar comprobantes', 'comprobantes'),
    ('gastos.manage', 'Gestionar gastos', 'gastos'),
    ('solicitudes.manage', 'Gestionar solicitudes', 'solicitudes'),
    ('clientes.manage', 'Gestionar clientes', 'clientes'),
    ('rbac.manage', 'Gestionar RBAC', 'rbac')
ON DUPLICATE KEY UPDATE
    permission_name = VALUES(permission_name),
    module_key = VALUES(module_key);

INSERT INTO saas_role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM saas_roles r
JOIN saas_permissions p
    ON (
        (r.role_key = 'cliente' AND p.permission_key IN ('dashboard.view', 'perfil.manage', 'servicios.view', 'servicios.manage', 'comprobantes.manage', 'gastos.manage'))
        OR (r.role_key = 'operador' AND p.permission_key IN ('dashboard.view', 'perfil.manage', 'servicios.view', 'servicios.manage', 'comprobantes.manage', 'gastos.manage', 'solicitudes.manage'))
        OR (r.role_key = 'administrador' AND p.permission_key IN ('dashboard.view', 'perfil.manage', 'servicios.view', 'servicios.manage', 'comprobantes.manage', 'gastos.manage', 'solicitudes.manage', 'clientes.manage', 'rbac.manage'))
    )
WHERE NOT EXISTS (
    SELECT 1
    FROM saas_role_permissions rp
    WHERE rp.role_id = r.id
      AND rp.permission_id = p.id
);

UPDATE usuario
SET tenant_id = COALESCE(tenant_id, 1),
    subscription_status = CASE
        WHEN subscription_status IS NULL OR subscription_status = '' THEN 'active'
        ELSE subscription_status
    END
WHERE id IS NOT NULL;

UPDATE usuario
SET permiso = 2,
    status = 1,
    tenant_id = 1,
    subscription_status = 'active',
    suscripcion_hasta = '2026-12-31',
    bloqueado_motivo = NULL
WHERE id = 16;

UPDATE usuario
SET permiso = 0,
    status = 1,
    tenant_id = 1,
    subscription_status = 'active',
    suscripcion_hasta = '2026-12-31',
    bloqueado_motivo = NULL
WHERE id = 17;
