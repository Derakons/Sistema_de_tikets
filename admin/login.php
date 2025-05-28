<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// Si el admin ya está logueado, redirigir a admin.php
if (isAdminLoggedIn()) {
    header("Location: " . RUTA_APP_URL . "admin/index.php");
    exit;
}

$page_title = "Login de Administrador";
$error_message = '';

if (isset($_SESSION['login_error'])) {
    $error_message = $_SESSION['login_error'];
    unset($_SESSION['login_error']); // Limpiar el mensaje de error después de mostrarlo
}

require_once '../core/templates/header.php';
?>

<div class="container login-container">
    <h1>Acceso de Administrador</h1>

    <?php if (!empty($error_message)): ?>
        <p style="color: red; text-align: center;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form action="procesar_login.php" method="POST">
        <div class="form-group">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required autofocus>
        </div>
        <div class="form-group">
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
        </div>        <button type="submit" class="btn btn-primary" style="width: 100%;">Ingresar</button>
    </form>
</div>

<?php require_once '../core/templates/footer.php'; ?>