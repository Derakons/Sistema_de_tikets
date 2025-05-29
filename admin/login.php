<?php
require_once '../core/config.php';
// functions.php ya está incluido por config.php

// Si el admin ya está logueado, redirigir a admin/index.php o dashboard.php
if (isAdminLoggedIn()) {
    header("Location: " . BASE_URL . "admin/dashboard.php"); // Redirigir al dashboard si ya está logueado
    exit;
}

$page_title = "Login de Administrador";
$error_message = '';

if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars((isset($page_title) ? $page_title : SITE_TITLE) . " - " . SITE_TITLE); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
</head>
<body class="has-sidebar"> 

    <?php include_once __DIR__ . '/../core/templates/sidebar_public.php'; ?>

    <div class="dashboard-main-content">
        
        <main class="container login-container page-content">
            <h1>Acceso de Administrador</h1>

            <?php if (!empty($error_message)):
            ?>
                <div class="alert alert-danger" role="alert" style="margin-top: 20px; text-align: center;">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="procesar_login.php" method="POST" class="styled-form">
                <div class="form-group">
                    <label for="username">Usuario:</label>
                    <input type="text" id="username" name="username" class="form-control" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Contraseña:</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Ingresar</button>
            </form>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>