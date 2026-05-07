<?php

require_once __DIR__ . '/env.php';

if (!function_exists('app_pdo')) {
    function app_pdo()
    {
        static $pdo = null;

        if ($pdo instanceof PDO) {
            return $pdo;
        }

        $host = env('DB_HOST', 'localhost');
        $port = env('DB_PORT', '3306');
        $database = env('DB_NAME', 'uniflux_db');
        $username = env('DB_USER', 'root');
        $password = env('DB_PASSWORD', '');
        $charset = env('DB_CHARSET', 'utf8mb4');

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $database, $charset);
        $debugMode = strtolower((string) env('APP_ENV', 'local')) !== 'production'
            || strtolower((string) env('APP_DEBUG', 'false')) === 'true';

        try {
            $pdo = new PDO(
                $dsn,
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                )
            );
        } catch (PDOException $exception) {
            $message = 'No se pudo conectar a la base de datos.';
            if ($debugMode) {
                $message .= ' ' . $exception->getMessage();
            }

            throw new RuntimeException($message, 0, $exception);
        }

        return $pdo;
    }
}

if (!function_exists('app_table_exists')) {
    function app_table_exists(PDO $pdo, $tableName)
    {
        static $cache = array();
        if (isset($cache[$tableName])) {
            return $cache[$tableName];
        }

        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table_name'
        );
        $statement->execute(array('table_name' => $tableName));
        $cache[$tableName] = (bool) $statement->fetchColumn();

        return $cache[$tableName];
    }
}

if (!function_exists('app_column_exists')) {
    function app_column_exists(PDO $pdo, $tableName, $columnName)
    {
        static $cache = array();
        $cacheKey = $tableName . '.' . $columnName;

        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $statement = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = :table_name AND column_name = :column_name'
        );
        $statement->execute(array(
            'table_name' => $tableName,
            'column_name' => $columnName,
        ));

        $cache[$cacheKey] = (bool) $statement->fetchColumn();

        return $cache[$cacheKey];
    }
}

