<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!function_exists('clientes_ensure_schema')) {
    function clientes_ensure_schema(PDO $pdo)
    {
        if (!app_table_exists($pdo, 'usuario')) {
            return;
        }

        if (!app_column_exists($pdo, 'usuario', 'suscripcion_hasta')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN suscripcion_hasta DATE NULL AFTER status");
        }

        if (!app_column_exists($pdo, 'usuario', 'subscription_status')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN subscription_status VARCHAR(20) NOT NULL DEFAULT 'active' AFTER suscripcion_hasta");
        }

        if (!app_column_exists($pdo, 'usuario', 'ultimo_pago_at')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN ultimo_pago_at DATETIME NULL AFTER subscription_status");
        }

        if (!app_column_exists($pdo, 'usuario', 'bloqueado_motivo')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN bloqueado_motivo VARCHAR(255) NULL AFTER ultimo_pago_at");
        }

        if (!app_column_exists($pdo, 'usuario', 'tenant_id')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN tenant_id INT NULL AFTER bloqueado_motivo");
        }

        if (!app_column_exists($pdo, 'usuario', 'blocked_capabilities')) {
            $pdo->exec("ALTER TABLE usuario ADD COLUMN blocked_capabilities TEXT NULL AFTER tenant_id");
        }

        if (!app_table_exists($pdo, 'saas_tenants')) {
            $pdo->exec(
                "CREATE TABLE saas_tenants (
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
                )"
            );
        }
    }
}

if (!function_exists('clientes_default_access_data')) {
    function clientes_default_access_data()
    {
        return array(
            'id' => 0,
            'permiso' => 0,
            'status' => 1,
            'suscripcion_hasta' => '',
            'subscription_status' => 'active',
            'bloqueado_motivo' => '',
            'blocked_capabilities' => array(),
        );
    }
}

if (!function_exists('clientes_access_request_data')) {
    function clientes_access_request_data(array $source)
    {
        return array(
            'id' => isset($source['id']) ? (int) $source['id'] : 0,
            'permiso' => isset($source['permiso']) ? (int) $source['permiso'] : 0,
            'status' => isset($source['status']) ? (int) $source['status'] : 0,
            'suscripcion_hasta' => isset($source['suscripcion_hasta']) ? trim((string) $source['suscripcion_hasta']) : '',
            'subscription_status' => isset($source['subscription_status']) ? trim((string) $source['subscription_status']) : 'active',
            'bloqueado_motivo' => isset($source['bloqueado_motivo']) ? trim((string) $source['bloqueado_motivo']) : '',
            'blocked_capabilities' => isset($source['blocked_capabilities']) && is_array($source['blocked_capabilities']) ? $source['blocked_capabilities'] : array(),
        );
    }
}

if (!function_exists('clientes_validate_access_data')) {
    function clientes_validate_access_data(PDO $pdo, array $data)
    {
        $errors = array();

        if ($data['id'] <= 0) {
            $errors['general'] = 'Usuario invalido.';
            return $errors;
        }

        if (!in_array($data['permiso'], array(0, 1, 2), true)) {
            $errors['general'] = 'Rol invalido.';
            return $errors;
        }

        if (!in_array($data['status'], array(0, 1), true)) {
            $errors['general'] = 'Estado invalido.';
            return $errors;
        }

        if (!in_array($data['subscription_status'], array('active', 'trial', 'past_due', 'suspended', 'cancelled'), true)) {
            $errors['general'] = 'Estado de suscripcion invalido.';
            return $errors;
        }

        if ($data['status'] === 1 && in_array($data['subscription_status'], array('active', 'trial'), true) && $data['suscripcion_hasta'] === '') {
            $errors['general'] = 'Define una fecha de suscripcion para mantener el acceso activo.';
            return $errors;
        }

        $rawDate = $data['suscripcion_hasta'];
        if ($rawDate !== '') {
            $date = DateTime::createFromFormat('Y-m-d', $rawDate);
            if (!($date instanceof DateTime) || $date->format('Y-m-d') !== $rawDate) {
                $errors['general'] = 'Fecha de suscripcion invalida. Usa formato YYYY-MM-DD.';
                return $errors;
            }
        }

        $allowedCapabilities = array_keys(app_capability_map());
        foreach ($data['blocked_capabilities'] as $capability) {
            $capability = is_string($capability) ? trim($capability) : '';
            if ($capability === '' || !in_array($capability, $allowedCapabilities, true)) {
                $errors['general'] = 'Permiso de pagina invalido.';
                return $errors;
            }
        }

        $existsStmt = $pdo->prepare('SELECT COUNT(*) FROM usuario WHERE id = :id');
        $existsStmt->execute(array('id' => $data['id']));
        if ((int) $existsStmt->fetchColumn() === 0) {
            $errors['general'] = 'El usuario no existe.';
        }

        return $errors;
    }
}

if (!function_exists('clientes_count')) {
    function clientes_count(PDO $pdo, $searchTerm)
    {
        $sql = 'SELECT COUNT(*) FROM usuario';
        $parameters = array();

        if ($searchTerm !== '') {
            $sql .= ' WHERE nombre LIKE :search OR apellido LIKE :search OR email LIKE :search OR celular LIKE :search OR nempleado LIKE :search';
            $parameters['search'] = '%' . $searchTerm . '%';
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }
}

if (!function_exists('clientes_paginated')) {
    function clientes_paginated(PDO $pdo, $searchTerm, $limit, $offset, $sortColumn = 'id', $sortDirection = 'desc')
    {
        $allowedSortColumns = array('id', 'nempleado', 'nombre', 'email', 'permiso', 'status', 'subscription_status', 'suscripcion_hasta');
        if (!in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'id';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'ASC' : 'DESC';

        $sql = 'SELECT id, nempleado, nombre, apellido, email, celular, usuario, permiso, status, suscripcion_hasta, subscription_status, bloqueado_motivo, tenant_id, blocked_capabilities, reg_date FROM usuario';
        $parameters = array();

        if ($searchTerm !== '') {
            $sql .= ' WHERE nombre LIKE :search OR apellido LIKE :search OR email LIKE :search OR celular LIKE :search OR nempleado LIKE :search';
            $parameters['search'] = '%' . $searchTerm . '%';
        }

        $sql .= sprintf(' ORDER BY %s %s LIMIT :limit OFFSET :offset', $sortColumn, $sortDirection);

        $statement = $pdo->prepare($sql);
        if (isset($parameters['search'])) {
            $statement->bindValue(':search', $parameters['search'], PDO::PARAM_STR);
        }

        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}

if (!function_exists('clientes_update_access')) {
    function clientes_update_access(PDO $pdo, array $data)
    {
        $statement = $pdo->prepare(
            'UPDATE usuario
             SET permiso = :permiso,
                 status = :status,
                 suscripcion_hasta = :suscripcion_hasta,
                 subscription_status = :subscription_status,
                 bloqueado_motivo = :bloqueado_motivo,
                 blocked_capabilities = :blocked_capabilities
             WHERE id = :id'
        );

        $statement->execute(array(
            'id' => $data['id'],
            'permiso' => $data['permiso'],
            'status' => $data['status'],
            'suscripcion_hasta' => $data['suscripcion_hasta'] === '' ? null : $data['suscripcion_hasta'],
            'subscription_status' => $data['subscription_status'],
            'bloqueado_motivo' => $data['bloqueado_motivo'] === '' ? null : $data['bloqueado_motivo'],
            'blocked_capabilities' => empty($data['blocked_capabilities']) ? null : json_encode(array_values(array_unique($data['blocked_capabilities']))),
        ));
    }
}

if (!function_exists('clientes_blocked_capabilities_options')) {
    function clientes_blocked_capabilities_options()
    {
        return app_capability_labels();
    }
}

if (!function_exists('clientes_status_badge')) {
    function clientes_status_badge(array $item)
    {
        if ((int) $item['status'] === 0) {
            return array('label' => 'Suspendido', 'class' => 'badge-soft-danger');
        }

        if (!empty($item['subscription_status'])) {
            $subscriptionStatus = strtolower((string) $item['subscription_status']);
            if ($subscriptionStatus === 'past_due') {
                return array('label' => 'Pago pendiente', 'class' => 'badge-soft-warning');
            }

            if (in_array($subscriptionStatus, array('suspended', 'cancelled'), true)) {
                return array('label' => 'Bloqueado', 'class' => 'badge-soft-danger');
            }

            if ($subscriptionStatus === 'trial') {
                return array('label' => 'Trial', 'class' => 'badge-soft-info');
            }
        }

        if (empty($item['suscripcion_hasta'])) {
            return array('label' => 'Sin suscripcion', 'class' => 'badge-soft-secondary');
        }

        $today = new DateTime('today');
        $until = DateTime::createFromFormat('Y-m-d', $item['suscripcion_hasta']);

        if ($until instanceof DateTime && $until < $today) {
            return array('label' => 'Pago vencido', 'class' => 'badge-soft-warning');
        }

        return array('label' => 'Al dia', 'class' => 'badge-soft-success');
    }
}

if (!function_exists('clientes_role_label')) {
    function clientes_role_label($permiso)
    {
        return app_role_label($permiso);
    }
}

if (!function_exists('clientes_role_options')) {
    function clientes_role_options()
    {
        return array(
            0 => 'Cliente',
            1 => 'Operador',
            2 => 'Administrador',
        );
    }
}

if (!function_exists('clientes_subscription_status_options')) {
    function clientes_subscription_status_options()
    {
        return array(
            'active' => 'Activa',
            'trial' => 'Trial',
            'past_due' => 'Pago pendiente',
            'suspended' => 'Suspendida',
            'cancelled' => 'Cancelada',
        );
    }
}
