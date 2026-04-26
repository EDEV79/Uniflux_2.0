<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!function_exists('gastos_ensure_schema')) {
    function gastos_ensure_schema(PDO $pdo)
    {
        if (!app_table_exists($pdo, 'gastos')) {
            return;
        }

        if (!app_column_exists($pdo, 'gastos', 'user_id')) {
            $pdo->exec("ALTER TABLE gastos ADD COLUMN user_id INT NULL AFTER printdate");
        }

        if (!app_column_exists($pdo, 'gastos', 'tenant_id')) {
            $pdo->exec("ALTER TABLE gastos ADD COLUMN tenant_id INT NULL AFTER user_id");
        }
    }
}

if (!function_exists('gastos_user_scope')) {
    function gastos_user_scope()
    {
        $isPrivileged = current_user_can('solicitudes.manage') || current_user_can('clientes.manage');

        return array(
            'restricted' => !$isPrivileged,
            'user_id' => current_user_id(),
            'tenant_id' => isset($_SESSION['tenant_id']) ? (int) $_SESSION['tenant_id'] : 0,
        );
    }
}

if (!function_exists('gastos_default_form_data')) {
    function gastos_default_form_data()
    {
        return array(
            'proveedor' => '',
            'nfactura' => '',
            'fecha' => '',
            'subtotal' => '',
            'itbms' => '0.00',
            'total' => '',
            'documento' => '',
            'vendedor' => '',
            'printdate' => '',
        );
    }
}

if (!function_exists('gastos_request_data')) {
    function gastos_request_data(array $source)
    {
        return array(
            'proveedor' => isset($source['proveedor']) ? trim($source['proveedor']) : '',
            'nfactura' => isset($source['nfactura']) ? trim($source['nfactura']) : '',
            'fecha' => isset($source['fecha']) ? trim($source['fecha']) : '',
            'subtotal' => isset($source['subtotal']) ? trim($source['subtotal']) : '',
            'itbms' => isset($source['itbms']) ? trim($source['itbms']) : '0.00',
            'total' => isset($source['total']) ? trim($source['total']) : '',
            'documento' => isset($source['documento']) ? trim($source['documento']) : '',
            'vendedor' => isset($source['vendedor']) ? trim($source['vendedor']) : '',
            'printdate' => isset($source['printdate']) ? trim($source['printdate']) : '',
        );
    }
}

if (!function_exists('gastos_validate_date')) {
    function gastos_validate_date($date)
    {
        if ($date === '') {
            return false;
        }

        $dateTime = DateTime::createFromFormat('Y-m-d', $date);
        return $dateTime instanceof DateTime && $dateTime->format('Y-m-d') === $date;
    }
}

if (!function_exists('gastos_validate_datetime')) {
    function gastos_validate_datetime($dateTime)
    {
        if ($dateTime === '') {
            return false;
        }

        $parsed = DateTime::createFromFormat('Y-m-d H:i:s', $dateTime);
        return $parsed instanceof DateTime && $parsed->format('Y-m-d H:i:s') === $dateTime;
    }
}

if (!function_exists('gastos_validate')) {
    function gastos_validate(array $data)
    {
        $errors = array();

        if ($data['proveedor'] === '') {
            $errors['proveedor'] = 'El proveedor es obligatorio.';
        }

        if ($data['nfactura'] === '') {
            $errors['nfactura'] = 'La factura es obligatoria.';
        }

        if (!gastos_validate_date($data['fecha'])) {
            $errors['fecha'] = 'La fecha debe tener formato YYYY-MM-DD.';
        }

        if ($data['subtotal'] === '' || !is_numeric($data['subtotal'])) {
            $errors['subtotal'] = 'El subtotal debe ser numerico.';
        }

        if ($data['itbms'] !== '' && !is_numeric($data['itbms'])) {
            $errors['itbms'] = 'El ITBMS debe ser numerico.';
        }

        if ($data['total'] === '' || !is_numeric($data['total'])) {
            $errors['total'] = 'El total debe ser numerico.';
        }

        if ($data['vendedor'] === '') {
            $errors['vendedor'] = 'El vendedor es obligatorio.';
        }

        if ($data['printdate'] !== '' && !gastos_validate_datetime($data['printdate'])) {
            $errors['printdate'] = 'El campo printdate debe tener formato YYYY-MM-DD HH:MM:SS.';
        }

        return $errors;
    }
}

if (!function_exists('gastos_find')) {
    function gastos_find(PDO $pdo, $id)
    {
        $scope = gastos_user_scope();
        $statement = $pdo->prepare(
            'SELECT idgastos, proveedor, nfactura, fecha, subtotal, itbms, total, documento, vendedor, printdate
             FROM gastos
             WHERE idgastos = :id' . ($scope['restricted'] ? ' AND user_id = :user_id' : '') . '
             LIMIT 1'
        );
        $parameters = array('id' => (int) $id);
        if ($scope['restricted']) {
            $parameters['user_id'] = $scope['user_id'];
        }
        $statement->execute($parameters);

        $record = $statement->fetch();
        return $record ? $record : null;
    }
}

if (!function_exists('gastos_count')) {
    function gastos_count(PDO $pdo, $searchTerm)
    {
        $scope = gastos_user_scope();
        $sql = 'SELECT COUNT(*) FROM gastos';
        $parameters = array();
        $conditions = array();

        if ($scope['restricted']) {
            $conditions[] = 'user_id = :user_id';
            $parameters['user_id'] = $scope['user_id'];
        }

        if ($searchTerm !== '') {
            $conditions[] = '(proveedor LIKE :search OR nfactura LIKE :search OR documento LIKE :search OR vendedor LIKE :search)';
            $parameters['search'] = '%' . $searchTerm . '%';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }
}

if (!function_exists('gastos_paginated')) {
    function gastos_paginated(PDO $pdo, $searchTerm, $limit, $offset, $sortColumn = 'idgastos', $sortDirection = 'desc')
    {
        $scope = gastos_user_scope();
        $allowedSortColumns = array('idgastos', 'proveedor', 'nfactura', 'fecha', 'total');
        if (!in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'idgastos';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'ASC' : 'DESC';

        $sql = 'SELECT idgastos, proveedor, nfactura, fecha, subtotal, itbms, total, documento, vendedor, printdate FROM gastos';
        $parameters = array();
        $conditions = array();

        if ($scope['restricted']) {
            $conditions[] = 'user_id = :user_id';
            $parameters['user_id'] = $scope['user_id'];
        }

        if ($searchTerm !== '') {
            $conditions[] = '(proveedor LIKE :search OR nfactura LIKE :search OR documento LIKE :search OR vendedor LIKE :search)';
            $parameters['search'] = '%' . $searchTerm . '%';
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= sprintf(' ORDER BY %s %s LIMIT :limit OFFSET :offset', $sortColumn, $sortDirection);

        $statement = $pdo->prepare($sql);
        if (isset($parameters['user_id'])) {
            $statement->bindValue(':user_id', (int) $parameters['user_id'], PDO::PARAM_INT);
        }
        if (isset($parameters['search'])) {
            $statement->bindValue(':search', $parameters['search'], PDO::PARAM_STR);
        }
        $statement->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $statement->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll();
    }
}

if (!function_exists('gastos_create')) {
    function gastos_create(PDO $pdo, array $data)
    {
        $scope = gastos_user_scope();
        $statement = $pdo->prepare(
            'INSERT INTO gastos (proveedor, nfactura, fecha, subtotal, itbms, total, documento, vendedor, printdate, user_id, tenant_id)
             VALUES (:proveedor, :nfactura, :fecha, :subtotal, :itbms, :total, :documento, :vendedor, :printdate, :user_id, :tenant_id)'
        );

        $statement->execute(array(
            'proveedor' => $data['proveedor'],
            'nfactura' => $data['nfactura'],
            'fecha' => $data['fecha'],
            'subtotal' => (float) $data['subtotal'],
            'itbms' => $data['itbms'] === '' ? 0 : (float) $data['itbms'],
            'total' => (float) $data['total'],
            'documento' => $data['documento'],
            'vendedor' => $data['vendedor'],
            'printdate' => $data['printdate'] !== '' ? $data['printdate'] : date('Y-m-d H:i:s'),
            'user_id' => current_user_id(),
            'tenant_id' => $scope['tenant_id'] > 0 ? $scope['tenant_id'] : null,
        ));
    }
}

if (!function_exists('gastos_update')) {
    function gastos_update(PDO $pdo, $id, array $data)
    {
        $scope = gastos_user_scope();
        $statement = $pdo->prepare(
            'UPDATE gastos
             SET proveedor = :proveedor,
                 nfactura = :nfactura,
                 fecha = :fecha,
                 subtotal = :subtotal,
                 itbms = :itbms,
                 total = :total,
                 documento = :documento,
                 vendedor = :vendedor,
                 printdate = :printdate
             WHERE idgastos = :id' . ($scope['restricted'] ? ' AND user_id = :user_id' : '')
        );

        $parameters = array(
            'id' => (int) $id,
            'proveedor' => $data['proveedor'],
            'nfactura' => $data['nfactura'],
            'fecha' => $data['fecha'],
            'subtotal' => (float) $data['subtotal'],
            'itbms' => $data['itbms'] === '' ? 0 : (float) $data['itbms'],
            'total' => (float) $data['total'],
            'documento' => $data['documento'],
            'vendedor' => $data['vendedor'],
            'printdate' => $data['printdate'] !== '' ? $data['printdate'] : date('Y-m-d H:i:s'),
        );

        if ($scope['restricted']) {
            $parameters['user_id'] = $scope['user_id'];
        }

        $statement->execute($parameters);
    }
}

if (!function_exists('gastos_delete')) {
    function gastos_delete(PDO $pdo, $id)
    {
        $scope = gastos_user_scope();
        $sql = 'DELETE FROM gastos WHERE idgastos = :id';
        $parameters = array('id' => (int) $id);

        if ($scope['restricted']) {
            $sql .= ' AND user_id = :user_id';
            $parameters['user_id'] = $scope['user_id'];
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);
    }
}
