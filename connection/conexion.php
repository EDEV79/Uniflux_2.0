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
$debugMode = function_exists('env')
	? (strtolower((string) env('APP_ENV', 'local')) !== 'production' || strtolower((string) env('APP_DEBUG', 'false')) === 'true')
	: true;

$conexion = mysqli_connect($hostname, $usuariodb, $clavedb, $basededatos);

if (!$conexion) {
	$message = 'Lo sentimos, el sistema esta presentando problemas de conexion.';
	if ($debugMode) {
		$message .= ' ' . mysqli_connect_error();
	}
	echo $message;
	exit();
}

mysqli_set_charset($conexion, 'utf8mb4');
mysqli_query($conexion, "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
