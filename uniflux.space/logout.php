<?php

define('APP_PUBLIC_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__) . '/apps/uniflux');

require_once APP_ROOT . '/includes/bootstrap.php';

$_SESSION = array();

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ' . app_url('index.php'));
exit;
