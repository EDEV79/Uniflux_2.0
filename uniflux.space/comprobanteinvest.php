<?php

define('APP_PUBLIC_ROOT', __DIR__);
define('APP_ROOT', dirname(__DIR__) . '/apps/uniflux');

require_once APP_ROOT . '/includes/bootstrap.php';

header('Location: ' . app_url('comprobantes.php'), true, 301);
exit;
