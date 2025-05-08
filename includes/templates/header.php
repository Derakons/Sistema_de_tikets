<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Asegurarse de que la sesión esté iniciada
}
require_once __DIR__ . '/../config.php'; // Ajustar ruta para config.php
require_once __DIR__ . '/../functions.php'; // Ajustar ruta para functions.php

// Determinar la ruta base correcta para los assets y enlaces
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_name = $_SERVER['SCRIPT_NAME'];

// Asumimos que la carpeta del proyecto es "Sistema de tikets"
// Si está en la raíz de htdocs, $project_folder sería ''
// Si está en una subcarpeta, por ejemplo /proyectos/Sistema de tikets, $project_folder sería '/proyectos'
$project_base_path = ''; 
if (strpos($script_name, '/Sistema%20de%20tikets/') !== false) {
    $project_base_path = substr($script_name, 0, strpos($script_name, '/Sistema%20de%20tikets/') + strlen('/Sistema%20de%20tikets/'));
} elseif (strpos($script_name, '/Sistema de tikets/') !== false) { // En caso de que el espacio no esté codificado
     $project_base_path = substr($script_name, 0, strpos($script_name, '/Sistema de tikets/') + strlen('/Sistema de tikets/'));
} else {
    // Intento de detección si está en una subcarpeta directa de htdocs
    $path_parts = explode('/', dirname($script_name));
    if (count($path_parts) > 1 && $path_parts[1] === 'Sistema de tikets') {
        $project_base_path = '/' . $path_parts[1] . '/';
    } else {
         // Si no se puede determinar, se asume que está en la raíz del servidor o se necesita configuración manual.
         // Para XAMPP, si "Sistema de tikets" es la carpeta raíz del proyecto en htdocs:
        $project_base_path = '/Sistema%20de%20tikets/'; // Ajustar si es necesario
    }
}
// Limpiar múltiples barras //
$project_base_path = rtrim(str_replace('//', '/', $project_base_path), '/') . '/';


define('BASE_URL', $protocol . $host . $project_base_path);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : SITE_TITLE; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/logo.png" type="image/png"> <!-- Favicon -->
</head>
<body>
    <header class="site-header">
        <div class="container header-flex">
            <div class="logo-title-group">
                <a href="<?php echo BASE_URL; ?>index.php" class="logo-link">
                    <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo Municipalidad de Canchis" class="logo">
                </a>
                <div class="title-group">
                    <a href="<?php echo BASE_URL; ?>index.php" class="site-title-link">
                        <h1>Municipalidad Provincial de Canchis</h1>
                        <span class="sub-title">Sistema de Tickets de Soporte TI</span>
                    </a>
                </div>
            </div>
            <nav class="main-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>index.php">Crear Ticket</a></li>
                    <li><a href="<?php echo BASE_URL; ?>seguimiento.php">Seguimiento</a></li>
                    <?php if (isAdminLoggedIn()): ?>
                        <li><a href="<?php echo BASE_URL; ?>admin.php">Panel Admin</a></li>
                        <li><a href="<?php echo BASE_URL; ?>logout_admin.php">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo BASE_URL; ?>login_admin.php">Admin Login</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    <main class="main-content">
        <!-- El contenido de cada página irá aquí -->