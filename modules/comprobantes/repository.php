<?php

require_once dirname(__DIR__, 2) . '/includes/bootstrap.php';

if (!function_exists('comprobantes_default_form_data')) {
    function comprobantes_default_form_data()
    {
        return array(
            'nombre' => '',
            'cedula' => '',
            'celular' => '',
            'email' => '',
            'fecha' => '',
            'hora' => '',
            'lugar' => '',
            'precio_show' => '',
            'comentarios' => '',
        );
    }
}

if (!function_exists('comprobantes_request_data')) {
    function comprobantes_request_data(array $source)
    {
        return array(
            'nombre' => isset($source['nombre']) ? trim($source['nombre']) : '',
            'cedula' => isset($source['cedula']) ? trim($source['cedula']) : '',
            'celular' => isset($source['celular']) ? trim($source['celular']) : '',
            'email' => isset($source['email']) ? trim($source['email']) : '',
            'fecha' => isset($source['fecha']) ? trim($source['fecha']) : '',
            'hora' => isset($source['hora']) ? trim($source['hora']) : '',
            'lugar' => isset($source['lugar']) ? trim($source['lugar']) : '',
            'precio_show' => isset($source['precio_show']) ? trim($source['precio_show']) : '',
            'comentarios' => isset($source['comentarios']) ? trim($source['comentarios']) : '',
        );
    }
}

if (!function_exists('comprobantes_display_date')) {
    function comprobantes_display_date($databaseDate)
    {
        return TraeFechaExplode($databaseDate);
    }
}

if (!function_exists('comprobantes_database_date')) {
    function comprobantes_database_date($displayDate)
    {
        if (empty($displayDate)) {
            return null;
        }

        $result = ExplodeFecha($displayDate);
        return $result === '0000-00-00' ? null : $result;
    }
}

if (!function_exists('comprobantes_database_time')) {
    function comprobantes_database_time($displayTime)
    {
        if ($displayTime === '') {
            return null;
        }

        $formats = array('g:i A', 'g:i a', 'H:i', 'H:i:s');
        foreach ($formats as $format) {
            $dateTime = DateTime::createFromFormat($format, $displayTime);
            if ($dateTime instanceof DateTime) {
                return $dateTime->format('H:i:s');
            }
        }

        return null;
    }
}

if (!function_exists('comprobantes_display_time')) {
    function comprobantes_display_time($databaseTime)
    {
        if (empty($databaseTime)) {
            return '';
        }

        $dateTime = DateTime::createFromFormat('H:i:s', $databaseTime);
        if (!$dateTime instanceof DateTime) {
            return $databaseTime;
        }

        return $dateTime->format('h:i A');
    }
}

if (!function_exists('comprobantes_validate')) {
    function comprobantes_validate(array $data)
    {
        $errors = array();

        if ($data['nombre'] === '') {
            $errors['nombre'] = 'El nombre es obligatorio.';
        }

        if ($data['cedula'] === '') {
            $errors['cedula'] = 'La cedula es obligatoria.';
        }

        if ($data['celular'] === '') {
            $errors['celular'] = 'El celular es obligatorio.';
        }

        if ($data['email'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Ingresa un email valido.';
        }

        if (comprobantes_database_date($data['fecha']) === null) {
            $errors['fecha'] = 'Usa el formato de fecha correcto.';
        }

        if (comprobantes_database_time($data['hora']) === null) {
            $errors['hora'] = 'Usa una hora valida.';
        }

        if ($data['lugar'] === '') {
            $errors['lugar'] = 'El lugar es obligatorio.';
        }

        if ($data['precio_show'] === '' || !is_numeric($data['precio_show'])) {
            $errors['precio_show'] = 'El precio debe ser numerico.';
        }

        if ($data['comentarios'] === '') {
            $errors['comentarios'] = 'Los comentarios son obligatorios.';
        }

        return $errors;
    }
}

if (!function_exists('comprobantes_find')) {
    function comprobantes_find(PDO $pdo, $id)
    {
        $statement = $pdo->prepare('SELECT id, nombre, cedula, celular, email, lugar, fecha, hora, comentarios, precio_show, creador FROM eventos WHERE id = :id LIMIT 1');
        $statement->execute(array('id' => (int) $id));
        $record = $statement->fetch();

        if (!$record) {
            return null;
        }

        $record['fecha'] = comprobantes_display_date($record['fecha']);
        $record['hora'] = comprobantes_display_time($record['hora']);

        return $record;
    }
}

if (!function_exists('comprobantes_count')) {
    function comprobantes_count(PDO $pdo, $searchTerm)
    {
        $sql = 'SELECT COUNT(*) FROM eventos';
        $parameters = array();

        if ($searchTerm !== '') {
            $sql .= ' WHERE nombre LIKE :search OR cedula LIKE :search OR email LIKE :search';
            $parameters['search'] = '%' . $searchTerm . '%';
        }

        $statement = $pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn();
    }
}

if (!function_exists('comprobantes_paginated')) {
    function comprobantes_paginated(PDO $pdo, $searchTerm, $limit, $offset, $sortColumn = 'id', $sortDirection = 'desc')
    {
        $allowedSortColumns = array('id', 'nombre', 'email', 'fecha', 'precio_show');
        if (!in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'id';
        }

        $sortDirection = strtolower($sortDirection) === 'asc' ? 'ASC' : 'DESC';

        $sql = 'SELECT id, nombre, cedula, celular, email, lugar, fecha, hora, comentarios, precio_show, creador FROM eventos';
        $parameters = array();

        if ($searchTerm !== '') {
            $sql .= ' WHERE nombre LIKE :search OR cedula LIKE :search OR email LIKE :search';
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

if (!function_exists('comprobantes_create')) {
    function comprobantes_create(PDO $pdo, array $data, $creator)
    {
        $statement = $pdo->prepare(
            'INSERT INTO eventos (nombre, cedula, celular, lugar, fecha, hora, email, precio_show, comentarios, creador)
             VALUES (:nombre, :cedula, :celular, :lugar, :fecha, :hora, :email, :precio_show, :comentarios, :creador)'
        );

        $statement->execute(array(
            'nombre' => $data['nombre'],
            'cedula' => $data['cedula'],
            'celular' => $data['celular'],
            'lugar' => $data['lugar'],
            'fecha' => comprobantes_database_date($data['fecha']),
            'hora' => comprobantes_database_time($data['hora']),
            'email' => $data['email'],
            'precio_show' => $data['precio_show'],
            'comentarios' => $data['comentarios'],
            'creador' => $creator,
        ));
    }
}

if (!function_exists('comprobantes_update')) {
    function comprobantes_update(PDO $pdo, $id, array $data)
    {
        $statement = $pdo->prepare(
            'UPDATE eventos
             SET nombre = :nombre, cedula = :cedula, celular = :celular, lugar = :lugar, fecha = :fecha, hora = :hora,
                 email = :email, precio_show = :precio_show, comentarios = :comentarios
             WHERE id = :id'
        );

        $statement->execute(array(
            'id' => (int) $id,
            'nombre' => $data['nombre'],
            'cedula' => $data['cedula'],
            'celular' => $data['celular'],
            'lugar' => $data['lugar'],
            'fecha' => comprobantes_database_date($data['fecha']),
            'hora' => comprobantes_database_time($data['hora']),
            'email' => $data['email'],
            'precio_show' => $data['precio_show'],
            'comentarios' => $data['comentarios'],
        ));
    }
}

if (!function_exists('comprobantes_delete')) {
    function comprobantes_delete(PDO $pdo, $id)
    {
        $statement = $pdo->prepare('DELETE FROM eventos WHERE id = :id');
        $statement->execute(array('id' => (int) $id));
    }
}

if (!function_exists('comprobantes_status')) {
    function comprobantes_status(array $item)
    {
        $comments = isset($item['comentarios']) ? strtolower($item['comentarios']) : '';
        if (strpos($comments, 'pagado') !== false || strpos($comments, 'pago completado') !== false) {
            return array('label' => 'Pagado', 'class' => 'badge-soft-success');
        }

        $eventDate = !empty($item['fecha']) ? DateTime::createFromFormat('Y-m-d', $item['fecha']) : null;
        $today = new DateTime('today');

        if ($eventDate instanceof DateTime && $eventDate < $today) {
            return array('label' => 'Atrasado', 'class' => 'badge-soft-danger');
        }

        return array('label' => 'Pendiente', 'class' => 'badge-soft-warning');
    }
}
