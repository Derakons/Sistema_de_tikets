<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../core/config.php'; // Para BASE_URL, $conn y functions.php

$page_title = "Crear Nuevo Ticket";
$departamentos = obtener_departamentos($conn); // Restaurado

$success_message = '';
if (isset($_SESSION['ticket_success'])) {
    $success_message = $_SESSION['ticket_success'];
    unset($_SESSION['ticket_success']);
}

$error_message = '';
if (isset($_SESSION['ticket_error'])) {
    $error_message = $_SESSION['ticket_error'];
    unset($_SESSION['ticket_error']);
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
        <?php // Eliminamos: include_once __DIR__ . '/../core/templates/header.php'; ?>

        <main class="container page-container page-content form-container-specific">
            <h1><?php echo htmlspecialchars($page_title); ?></h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($error_message): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <form action="guardar_ticket.php" method="POST" enctype="multipart/form-data" class="styled-form needs-validation" novalidate>
                <div class="form-group">
                    <label for="nombre_completo">Nombre Completo:</label>
                    <input type="text" id="nombre_completo" name="nombre_completo" class="form-control" required>
                    <div class="invalid-feedback">Por favor, ingrese su nombre completo.</div>
                </div> <!-- cierre del form-group de Nombre Completo -->

                <div class="form-group">
                    <label for="id_departamento">Departamento:</label>
                    <select id="id_departamento" name="id_departamento" class="form-control" required>
                        <option value="">Seleccione un departamento</option>
                        <?php foreach ($departamentos as $depto): ?>
                            <option value="<?php echo htmlspecialchars($depto['id']); ?>">
                                <?php echo htmlspecialchars($depto['nombre']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="invalid-feedback">Por favor, seleccione un departamento.</div>
                </div>

                <div class="form-group">
                    <label for="asunto">Asunto:</label>
                    <input type="text" id="asunto" name="asunto" class="form-control" required>
                    <div class="invalid-feedback">Por favor, ingrese el asunto del ticket.</div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción del Problema:</label>
                    <textarea id="descripcion" name="descripcion" rows="6" class="form-control" required></textarea>
                    <div class="invalid-feedback">Por favor, describa el problema.</div>
                </div>

                <button type="submit" class="btn btn-primary">Enviar Ticket</button>
            </form>
        </main>

        <?php // Eliminamos: include_once __DIR__ . '/../core/templates/footer.php'; ?>
    </div>

    <!-- Bootstrap 5.3.3 JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
    // Script para validación de Bootstrap (debería ser compatible con BS5)
    (function() {
      'use strict';
      window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
          form.addEventListener('submit', function(event) {
            if (form.checkValidity() === false) {
              event.preventDefault();
              event.stopPropagation();
            }
            form.classList.add('was-validated');
          }, false);
        });
      }, false);
    })();
    </script>
</body>
</html>
