<?php
session_status() === PHP_SESSION_NONE && session_start();

$Usuarioactivo = isset($_SESSION['MM_NombApe']) ? $_SESSION['MM_NombApe'] : null;

$appRoot = dirname(__DIR__);
if (file_exists($appRoot . '/config/env.php')) {
	require_once $appRoot . '/config/env.php';
}

$hostname = function_exists('env') ? env('DB_HOST', 'localhost') : 'localhost';
$basededatos = function_exists('env') ? env('DB_NAME', 'adminhinvest') : 'adminhinvest';
$usuariodb = function_exists('env') ? env('DB_USER', 'root') : 'root';
$clavedb = function_exists('env') ? env('DB_PASSWORD', '') : '';

$conexion = mysqli_connect($hostname, $usuariodb, $clavedb, $basededatos);

if (!$conexion) {
	echo 'Lo sentimos, el sistema esta presentando problemas de conexion.';
	exit();
}

mysqli_set_charset($conexion, 'utf8');
