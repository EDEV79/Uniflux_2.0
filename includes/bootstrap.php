<?php

if (!defined('APP_ROOT')) {
    define('APP_ROOT', dirname(__DIR__));
}

require_once APP_ROOT . '/config/env.php';
require_once APP_ROOT . '/config/database.php';

if (file_exists(APP_ROOT . '/connection/funciones.php')) {
    require_once APP_ROOT . '/connection/funciones.php';
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('app_name')) {
    function app_name()
    {
        return env('APP_NAME', 'Admin He_System');
    }
}

if (!function_exists('app_url')) {
    function app_url($path = '')
    {
        if ($path !== '' && preg_match('/^(https?:)?\/\//i', $path)) {
            return $path;
        }

        if ($path !== '' && ($path[0] === '#' || strpos($path, 'mailto:') === 0)) {
            return $path;
        }

        static $basePath = null;

        if ($basePath === null) {
            $basePath = '';
            $documentRoot = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
            $applicationRoot = realpath(APP_ROOT);

            if ($documentRoot && $applicationRoot && strpos($applicationRoot, $documentRoot) === 0) {
                $relativePath = trim(str_replace('\\', '/', substr($applicationRoot, strlen($documentRoot))), '/');
                $basePath = $relativePath !== '' ? '/' . $relativePath : '';
            } else {
                $configuredBasePath = trim(env('APP_BASE_PATH', ''), '/');
                $basePath = $configuredBasePath !== '' ? '/' . $configuredBasePath : '';
            }
        }

        $cleanPath = ltrim($path, '/');

        if ($cleanPath === '') {
            return $basePath !== '' ? $basePath . '/' : '/';
        }

        return ($basePath !== '' ? $basePath : '') . '/' . $cleanPath;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path)
    {
        return app_url($path);
    }
}

if (!function_exists('current_user_name')) {
    function current_user_name()
    {
        return isset($_SESSION['MM_NombApe']) ? $_SESSION['MM_NombApe'] : 'Usuario';
    }
}

if (!function_exists('current_user_username')) {
    function current_user_username()
    {
        return isset($_SESSION['usuario']) ? $_SESSION['usuario'] : 'invitado';
    }
}

if (!function_exists('current_user_role_label')) {
    function current_user_role_label()
    {
        if (!isset($_SESSION['permiso'])) {
            return 'Operador';
        }

        return (int) $_SESSION['permiso'] >= 2 ? 'Administrador' : 'Operador';
    }
}

if (!function_exists('current_user_initials')) {
    function current_user_initials()
    {
        $name = trim(current_user_name());
        if ($name === '') {
            return 'UH';
        }

        $parts = preg_split('/\s+/', $name);
        $initials = '';

        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $initials .= strtoupper(substr($part, 0, 1));

            if (strlen($initials) >= 2) {
                break;
            }
        }

        return $initials !== '' ? $initials : 'UH';
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: index.php');
            exit;
        }
    }
}

if (!function_exists('flash')) {
    function flash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = array();
        }

        $_SESSION['flash_messages'][] = array(
            'type' => $type,
            'message' => $message,
        );
    }
}

if (!function_exists('consume_flashes')) {
    function consume_flashes()
    {
        $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : array();
        unset($_SESSION['flash_messages']);

        return $messages;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token()
    {
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(16));
        }

        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('verify_csrf_token')) {
    function verify_csrf_token($submittedToken)
    {
        if (empty($_SESSION['_csrf_token']) || empty($submittedToken)) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $submittedToken);
    }
}

if (!function_exists('e')) {
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('format_currency')) {
    function format_currency($amount)
    {
        return '$' . number_format((float) $amount, 2);
    }
}

if (!function_exists('build_pagination')) {
    function build_pagination($totalItems, $perPage, $currentPage)
    {
        $perPage = max(1, (int) $perPage);
        $currentPage = max(1, (int) $currentPage);
        $totalPages = (int) ceil($totalItems / $perPage);

        return array(
            'total_items' => (int) $totalItems,
            'per_page' => $perPage,
            'current_page' => $currentPage,
            'total_pages' => max(1, $totalPages),
            'offset' => ($currentPage - 1) * $perPage,
        );
    }
}

if (!function_exists('query_string_with')) {
    function query_string_with(array $overrides = array())
    {
        $parameters = $_GET;
        foreach ($overrides as $key => $value) {
            if ($value === null) {
                unset($parameters[$key]);
                continue;
            }

            $parameters[$key] = $value;
        }

        return http_build_query($parameters);
    }
}
