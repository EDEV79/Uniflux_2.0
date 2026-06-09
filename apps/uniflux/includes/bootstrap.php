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
        return env('APP_NAME', 'UniFlux');
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
            $publicRoot = defined('APP_PUBLIC_ROOT') ? realpath(APP_PUBLIC_ROOT) : false;
            $applicationRoot = $publicRoot ?: realpath(APP_ROOT);

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
        return app_role_label(isset($_SESSION['permiso']) ? (int) $_SESSION['permiso'] : 0);
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

if (!function_exists('current_user_id')) {
    function current_user_id()
    {
        return isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    }
}

if (!function_exists('current_user_is_admin')) {
    function current_user_is_admin()
    {
        return isset($_SESSION['permiso']) && (int) $_SESSION['permiso'] >= 2;
    }
}

if (!function_exists('app_role_catalog')) {
    function app_role_catalog()
    {
        return array(
            0 => array(
                'key' => 'cliente',
                'label' => 'Cliente',
            ),
            1 => array(
                'key' => 'operador',
                'label' => 'Operador',
            ),
            2 => array(
                'key' => 'administrador',
                'label' => 'Administrador',
            ),
        );
    }
}

if (!function_exists('app_role_label')) {
    function app_role_label($permiso)
    {
        $catalog = app_role_catalog();
        $permiso = (int) $permiso;

        return isset($catalog[$permiso]) ? $catalog[$permiso]['label'] : 'N/D';
    }
}

if (!function_exists('app_capability_map')) {
    function app_capability_map()
    {
        return array(
            'dashboard.view' => 0,
            'perfil.manage' => 0,
            'servicios.view' => 0,
            'servicios.manage' => 0,
            'comprobantes.manage' => 0,
            'gastos.manage' => 0,
            'solicitudes.manage' => 1,
            'clientes.manage' => 2,
            'rbac.manage' => 2,
        );
    }
}

if (!function_exists('app_capability_labels')) {
    function app_capability_labels()
    {
        return array(
            'dashboard.view' => 'Dashboard',
            'perfil.manage' => 'Perfil',
            'servicios.view' => 'Servicios',
            'servicios.manage' => 'Gestionar servicios',
            'comprobantes.manage' => 'Comprobantes',
            'gastos.manage' => 'Gastos',
            'solicitudes.manage' => 'Solicitudes',
            'clientes.manage' => 'Clientes admin',
            'rbac.manage' => 'RBAC',
        );
    }
}

if (!function_exists('current_user_blocked_capabilities')) {
    function current_user_blocked_capabilities()
    {
        static $blockedCapabilities = null;
        if (is_array($blockedCapabilities)) {
            return $blockedCapabilities;
        }

        $blockedCapabilities = array();
        $userId = current_user_id();
        if ($userId <= 0) {
            return $blockedCapabilities;
        }

        try {
            $pdo = app_pdo();
            if (!app_table_exists($pdo, 'usuario') || !app_column_exists($pdo, 'usuario', 'blocked_capabilities')) {
                return $blockedCapabilities;
            }

            $statement = $pdo->prepare('SELECT blocked_capabilities FROM usuario WHERE id = :id LIMIT 1');
            $statement->execute(array('id' => $userId));
            $rawValue = $statement->fetchColumn();
            if (!is_string($rawValue) || trim($rawValue) === '') {
                return $blockedCapabilities;
            }

            $decoded = json_decode($rawValue, true);
            if (!is_array($decoded)) {
                return $blockedCapabilities;
            }

            $allowedCapabilities = array_keys(app_capability_map());
            foreach ($decoded as $capability) {
                $capability = is_string($capability) ? trim($capability) : '';
                if ($capability !== '' && in_array($capability, $allowedCapabilities, true)) {
                    $blockedCapabilities[] = $capability;
                }
            }
        } catch (Throwable $exception) {
            return array();
        }

        return array_values(array_unique($blockedCapabilities));
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can($capability)
    {
        $capabilityMap = app_capability_map();
        if (!isset($capabilityMap[$capability])) {
            return false;
        }

        $currentPermission = isset($_SESSION['permiso']) ? (int) $_SESSION['permiso'] : 0;
        if ($currentPermission < (int) $capabilityMap[$capability]) {
            return false;
        }

        return !in_array($capability, current_user_blocked_capabilities(), true);
    }
}

if (!function_exists('render_forbidden_page')) {
    function render_forbidden_page($capability = '')
    {
        http_response_code(403);

        $labels = app_capability_labels();
        $capability = is_string($capability) ? $capability : '';
        $blockedPageLabel = isset($labels[$capability]) ? $labels[$capability] : 'esta pagina';

        $pageTitle = 'No autorizado | ' . app_name();
        $pageHeading = 'Acceso restringido';
        $pageDescription = '';
        $currentModule = '';
        $pageActions = array(
            array(
                'label' => 'Volver al dashboard',
                'href' => 'inicio.php',
                'icon' => 'fa-solid fa-house',
                'class' => 'btn-primary',
            ),
        );

        include APP_ROOT . '/includes/header.php';
        ?>
        <section class="surface-card">
            <div class="empty-state py-5">
                <i class="fa-solid fa-shield-halved"></i>
                <h2 class="surface-card__title">No esta autorizado para ver esta pagina</h2>
                <p>Tu cuenta no tiene acceso a <?php echo e($blockedPageLabel); ?>. Contacta a tu administrador para habilitar este modulo.</p>
            </div>
        </section>
        <?php
        include APP_ROOT . '/includes/footer.php';
        exit;
    }
}

if (!function_exists('require_capability')) {
    function require_capability($capability)
    {
        require_login();

        if (current_user_can($capability)) {
            return;
        }

        render_forbidden_page($capability);
    }
}

if (!function_exists('current_user_access_badge')) {
    function current_user_access_badge()
    {
        if (current_user_id() <= 0) {
            return array(
                'label' => 'Invitado',
                'class' => 'badge-soft-secondary',
            );
        }

        if (!current_user_access_valid()) {
            return array(
                'label' => 'Suspendido',
                'class' => 'badge-soft-danger',
            );
        }

        return array(
            'label' => 'Suscripcion activa',
            'class' => 'badge-soft-success',
        );
    }
}

if (!function_exists('current_user_access_valid')) {
    function current_user_access_valid()
    {
        $userId = current_user_id();
        if ($userId <= 0) {
            return true;
        }

        try {
            $pdo = app_pdo();
            if (!app_table_exists($pdo, 'usuario')) {
                return true;
            }

            $hasSubscription = app_column_exists($pdo, 'usuario', 'suscripcion_hasta');
            $hasSubscriptionStatus = app_column_exists($pdo, 'usuario', 'subscription_status');
            $hasTenantId = app_column_exists($pdo, 'usuario', 'tenant_id');
            $sql = 'SELECT status'
                . ($hasSubscription ? ', suscripcion_hasta' : '')
                . ($hasSubscriptionStatus ? ', subscription_status' : '')
                . ($hasTenantId ? ', tenant_id' : '')
                . ' FROM usuario WHERE id = :id LIMIT 1';
            $statement = $pdo->prepare($sql);
            $statement->execute(array('id' => $userId));
            $row = $statement->fetch();

            if (!$row) {
                return false;
            }

            if ((int) $row['status'] !== 1) {
                return false;
            }

            if ($hasSubscriptionStatus) {
                $subscriptionStatus = strtolower(trim((string) $row['subscription_status']));
                if (in_array($subscriptionStatus, array('past_due', 'suspended', 'cancelled'), true)) {
                    return false;
                }
            }

            if ($hasSubscription && !empty($row['suscripcion_hasta'])) {
                $today = new DateTime('today');
                $until = DateTime::createFromFormat('Y-m-d', $row['suscripcion_hasta']);
                if ($until instanceof DateTime && $until < $today) {
                    return false;
                }
            }

            if ($hasTenantId && !empty($row['tenant_id']) && app_table_exists($pdo, 'saas_tenants')) {
                $tenantSql = 'SELECT status, subscription_status, suscripcion_hasta FROM saas_tenants WHERE id = :tenant_id LIMIT 1';
                $tenantStatement = $pdo->prepare($tenantSql);
                $tenantStatement->execute(array('tenant_id' => (int) $row['tenant_id']));
                $tenant = $tenantStatement->fetch();

                if ($tenant) {
                    if (strtolower((string) $tenant['status']) !== 'active') {
                        return false;
                    }

                    $tenantSubscriptionStatus = strtolower((string) $tenant['subscription_status']);
                    if (in_array($tenantSubscriptionStatus, array('past_due', 'suspended', 'cancelled'), true)) {
                        return false;
                    }

                    if (!empty($tenant['suscripcion_hasta'])) {
                        $today = new DateTime('today');
                        $until = DateTime::createFromFormat('Y-m-d', $tenant['suscripcion_hasta']);
                        if ($until instanceof DateTime && $until < $today) {
                            return false;
                        }
                    }
                }
            }
        } catch (Throwable $exception) {
            return true;
        }

        return true;
    }
}

if (!function_exists('require_login')) {
    function require_login()
    {
        if (empty($_SESSION['usuario']) && current_user_id() <= 0) {
            header('Location: ' . app_url('index.php'));
            exit;
        }

        if (!current_user_access_valid()) {
            $_SESSION = array();
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            header('Location: ' . app_url('index.php') . '?blocked=1');
            exit;
        }
    }
}

if (!function_exists('require_admin')) {
    function require_admin()
    {
        require_capability('clientes.manage');
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

if (!function_exists('app_hash_password')) {
    function app_hash_password($plainPassword)
    {
        return password_hash((string) $plainPassword, PASSWORD_DEFAULT);
    }
}

if (!function_exists('app_password_needs_rehash')) {
    function app_password_needs_rehash($storedHash)
    {
        $storedHash = (string) $storedHash;

        if ($storedHash === '') {
            return false;
        }

        if (preg_match('/^\$2y\$/', $storedHash) !== 1) {
            return true;
        }

        return password_needs_rehash($storedHash, PASSWORD_DEFAULT);
    }
}

if (!function_exists('app_verify_password')) {
    function app_verify_password($plainPassword, $storedHash)
    {
        $plainPassword = (string) $plainPassword;
        $storedHash = (string) $storedHash;

        if ($plainPassword === '' || $storedHash === '') {
            return false;
        }

        if (preg_match('/^\$2y\$/', $storedHash) === 1) {
            return password_verify($plainPassword, $storedHash);
        }

        return hash_equals($storedHash, sha1($plainPassword));
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
