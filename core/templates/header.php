<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); 
}
require_once __DIR__ . '/../config.php'; 
require_once __DIR__ . '/../functions.php'; 

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : htmlspecialchars(SITE_TITLE); ?></title>
    
    <!-- Bootstrap CSS (antes del CSS unificado para que tus estilos puedan sobreescribir) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- CSS Unificado del Sistema de Tickets -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/logo.png" type="image/png"> <!-- Favicon -->
</head>
<body>
    <header class="header">
        <nav class="navbar navbar-expand-lg">
            <div class="container-fluid">
                <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>index.php">
                    <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo Municipalidad de Canchis" class="navbar-logo">
                    <div class="ms-2">
                        <span class="site-title">Municipalidad Provincial de Canchis</span><br>
                        <span class="site-subtitle"><?php echo htmlspecialchars(SITE_TITLE); ?></span>
                    </div>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="mainNavbar">
                    <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>index.php">Crear Ticket</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo BASE_URL; ?>public/seguimiento.php">Seguimiento</a>
                        </li>
                        <?php if (isAdminLoggedIn()): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle admin-link" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user-shield"></i> Admin
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                    <li><a class="dropdown-item admin-link" href="<?php echo BASE_URL; ?>admin/dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                                    <li><a class="dropdown-item admin-link" href="<?php echo BASE_URL; ?>admin/index.php"><i class="fas fa-cogs"></i> Panel Admin</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item logout-link" href="<?php echo BASE_URL; ?>admin/logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a></li>
                                </ul>
                            </li>
                        <?php else: ?>
                            <li class="nav-item">
                                <a class="nav-link admin-link" href="<?php echo BASE_URL; ?>admin/login.php"><i class="fas fa-sign-in-alt"></i> Admin Login</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </nav>
    </header>
    
    <main class="main-content container py-4">
        <!-- El contenido de cada página irá aquí -->
