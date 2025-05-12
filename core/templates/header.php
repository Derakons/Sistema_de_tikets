<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start(); // Asegurarse de que la sesión esté iniciada
}
require_once __DIR__ . '/../config.php'; // Se espera que BASE_URL y SITE_TITLE se definan aquí.
require_once __DIR__ . '/../functions.php'; // Ajustar ruta para functions.php

// La definición de BASE_URL debe estar en core/config.php para una gestión centralizada.
// Ejemplo para core/config.php: define('BASE_URL', 'http://localhost/Sistema%20de%20tikets/');
// Ejemplo para core/config.php: define('SITE_TITLE', 'Sistema de Tickets de Soporte TI');

// Comprobación de emergencia: si BASE_URL no está definida, se registra un error y se usa un fallback.
if (!defined('BASE_URL')) {
    error_log('Error Crítico: BASE_URL no está definida en core/config.php. Los enlaces del sitio podrían no funcionar. Usando fallback de emergencia.');
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    // Esta es una suposición para un entorno XAMPP y DEBE ser configurada correctamente en core/config.php
    // rawurlencode es importante si el nombre de la carpeta del proyecto tiene espacios.
    define('BASE_URL', $protocol . $host . '/' . rawurlencode('Sistema de tikets') . '/'); 
}

// Comprobación similar para SITE_TITLE.
if (!defined('SITE_TITLE')) {
    error_log('Advertencia: SITE_TITLE no está definida en core/config.php. Usando fallback.');
    define('SITE_TITLE', 'Sistema de Tickets de Soporte TI'); // Fallback básico para SITE_TITLE
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : htmlspecialchars(SITE_TITLE); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;700&family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>public/assets/css/progress-fix.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/logo.png" type="image/png"> <!-- Favicon -->
    <style>
        :root {
            --color1: #0a192f;
            --color2:rgb(52, 129, 244);
            --color3: #612d2a;
            --color4: #5d9299;
            --color5: #b8bbbd;
        }
        body {
            background: var(--color5);
        }
        .navbar {
            background: linear-gradient(90deg, var(--color1) 0%, var(--color2) 100%);
            box-shadow: 0 4px 16px rgba(97,45,42,0.10);
        }
        .navbar-brand, .navbar-nav .nav-link, .navbar-toggler {
            color: #fff !important;
            font-family: 'Orbitron', 'Roboto', sans-serif;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .navbar-nav .nav-link.active, .navbar-nav .nav-link:focus, .navbar-nav .nav-link:hover {
            color: var(--color4) !important;
            background: rgba(255,255,255,0.08);
            border-radius: 6px;
        }
        .navbar .logout-link {
            background: var(--color3);
            color: #fff !important;
            margin-left: 10px;
            border-radius: 6px;
            padding: 0.5rem 1rem; /* Ajustado para consistencia */
            transition: background 0.2s;
        }
        .navbar .logout-link:hover {
            background: #3d1816;
            color: #fff !important; /* Asegurar color de texto en hover */
        }
        .navbar .admin-link {
            background: var(--color4);
            color: #fff !important;
            margin-left: 10px;
            border-radius: 6px;
            padding: 0.5rem 1rem; /* Ajustado para consistencia */
            transition: background 0.2s;
        }
        .navbar .admin-link:hover {
            background: #417177;
            color: #fff !important; /* Asegurar color de texto en hover */
        }
        .navbar-logo {
            height: 48px;
            width: 48px;
            object-fit: contain;
            border-radius: 8px;
            margin-right: 12px;
        }
        .site-title {
            font-family: 'Orbitron', 'Roboto', sans-serif;
            font-size: 1.4em;
            font-weight: 700;
            color: #fff;
            margin-bottom: 0;
        }
        .site-subtitle {
            font-family: 'Roboto', sans-serif;
            font-size: 1em;
            color: #f8d7da; /* Considerar hacerlo más genérico o parte de las variables CSS si es necesario */
            margin-top: -2px;
        }
        @media (max-width: 600px) {
            .site-title { font-size: 1.1em; }
            .navbar-logo { height: 36px; width: 36px; }
        }
    </style>
</head>
<body>
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
                        <li class="nav-item">
                            <a class="nav-link admin-link" href="<?php echo BASE_URL; ?>admin/index.php">Panel Admin</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link logout-link" href="<?php echo BASE_URL; ?>admin/logout.php">Cerrar Sesión</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link admin-link" href="<?php echo BASE_URL; ?>admin/login.php">Admin Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <main class="main-content container py-4">
        <!-- El contenido de cada página irá aquí -->