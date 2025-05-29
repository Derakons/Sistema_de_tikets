<?php
// session_start(); // config.php ya lo maneja
require_once '../core/config.php'; // Carga config y functions

$page_title = "Seguimiento de Ticket";
// $is_admin = isAdminLoggedIn(); // Ya no se usa directamente en esta vista, sidebar_public lo maneja

$ticket_info = null;
$error_message = '';
$numero_ticket_consultado = '';

if (isset($_GET['numero_ticket'])) {
    $numero_ticket_consultado = limpiar_datos($_GET['numero_ticket']);

    if (!empty($numero_ticket_consultado)) {
        if ($conn) { // $conn viene de config.php
            // Corregido: d.nombre a d.nombre_departamento
            $sql = "SELECT t.*, 
                        t.nombre_solicitante AS nombre_completo, 
                        t.email_solicitante AS email, 
                        t.telefono_solicitante AS telefono, 
                        t.ultima_actualizacion AS fecha_actualizacion, 
                        d.nombre_departamento AS nombre_departamento 
                    FROM tickets t 
                    LEFT JOIN departamentos d ON t.id_departamento = d.id 
                    WHERE t.id = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                $stmt->bind_param("s", $numero_ticket_consultado);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $ticket_info = $result->fetch_assoc();
                } else {
                    $error_message = "No se encontró ningún ticket con el número proporcionado.";
                }
                $stmt->close();
            } else {
                $error_message = "Error al preparar la consulta de seguimiento: " . $conn->error;
            }
        } else {
            $error_message = "Error de conexión a la base de datos. Por favor, contacte al administrador.";
        }
    }
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
        <main class="container-main seguimiento-modern page-content">
            <div class="section-header">
                <h1 class="section-title"><i class="fas fa-search-location"></i> <?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            <form action="<?php echo htmlspecialchars(BASE_URL . 'public/seguimiento.php'); ?>" method="GET" class="search-form modern-search-form needs-validation" novalidate>
                <div class="form-group mb-0">
                    <input type="text" id="numero_ticket" name="numero_ticket" class="form-control" placeholder="Ingrese su número de ticket" value="<?php echo htmlspecialchars($numero_ticket_consultado); ?>" required autofocus>
                    <div class="invalid-feedback">Por favor, ingrese un número de ticket.</div>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Consultar Estado
                </button>
            </form>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger modern-alert mt-3">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            <?php if ($ticket_info): ?>
                <div class="ticket-card modern-ticket-card">
                    <div class="ticket-header modern-ticket-header">
                        <div class="ticket-id modern-ticket-id">
                            <i class="fas fa-ticket-alt"></i> Ticket #<?php echo htmlspecialchars($ticket_info['id']); ?>
                        </div>
                        <span class="ticket-estado estado-<?php echo strtolower(str_replace(' ', '-', htmlspecialchars($ticket_info['estado']))); ?>">
                            <i class="fas fa-<?php 
                                switch($ticket_info['estado']) {
                                    case 'Abierto': echo 'folder-open'; break;
                                    case 'En Progreso': echo 'cog fa-spin'; break;
                                    case 'Resuelto': echo 'check-circle'; break;
                                    case 'Cerrado': echo 'lock'; break;
                                    case 'Esperando Respuesta': echo 'clock'; break;
                                    default: echo 'info-circle';
                                }
                            ?>"></i>
                            <?php echo htmlspecialchars($ticket_info['estado']); ?>
                        </span>
                    </div>
                    <div class="ticket-body modern-ticket-body">
                        <div class="info-section modern-info-section">
                            <p><strong><i class="fas fa-user"></i> Solicitante:</strong> <?php echo htmlspecialchars($ticket_info['nombre_completo']); ?></p>
                            <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($ticket_info['email']); ?></p>
                            <?php if (!empty($ticket_info['telefono'])): ?>
                                <p><strong><i class="fas fa-phone"></i> Teléfono:</strong> <?php echo htmlspecialchars($ticket_info['telefono']); ?></p>
                            <?php endif; ?>
                        </div>
                        <hr>
                        <div class="info-section modern-info-section">
                            <p><strong><i class="fas fa-building"></i> Departamento:</strong> <?php echo htmlspecialchars($ticket_info['nombre_departamento']); ?></p>
                            <p><strong><i class="fas fa-calendar-alt"></i> Fecha de Creación:</strong> <?php echo htmlspecialchars(formatear_fecha_hora($ticket_info['fecha_creacion'])); ?></p>
                            <p><strong><i class="fas fa-calendar-check"></i> Última Actualización:</strong> <?php echo htmlspecialchars(formatear_fecha_hora($ticket_info['fecha_actualizacion'])); ?></p>
                        </div>
                        <hr>
                        <div class="info-section modern-info-section">
                            <p><strong><i class="fas fa-info-circle"></i> Asunto:</strong> <?php echo htmlspecialchars($ticket_info['asunto']); ?></p>
                            <p><strong><i class="fas fa-align-left"></i> Descripción:</strong></p>
                            <div class="descripcion-contenido">
                                <?php echo nl2br(htmlspecialchars($ticket_info['descripcion'])); ?>
                            </div>
                        </div>
                        <?php if (!empty($ticket_info['archivo_adjunto_path'])): ?>
                            <hr>
                            <div class="info-section modern-info-section">
                                <p><strong><i class="fas fa-paperclip"></i> Archivo Adjunto:</strong> 
                                    <a href="<?php echo BASE_URL . 'uploads/' . basename($ticket_info['archivo_adjunto_path']); ?>" target="_blank">
                                        <?php echo htmlspecialchars(basename($ticket_info['archivo_adjunto_path'])); ?>
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="ticket-footer modern-ticket-footer">
                        <p>Si tiene alguna consulta adicional, por favor contacte con el administrador.</p>
                    </div>
                </div>
            <?php elseif (empty($error_message) && !empty($numero_ticket_consultado)): ?>
            <?php elseif (empty($numero_ticket_consultado) && empty($error_message)): ?>
                <div class="info-section modern-info-section text-center" style="padding: 30px 20px;">
                    <i class="fas fa-info-circle fa-3x text-muted mb-3"></i>
                    <p>Ingrese su número de ticket para ver su estado y detalles.</p>
                </div>
            <?php endif; ?>
        </main>
        <?php // Eliminamos: include_once __DIR__ . '/../core/templates/footer.php'; ?>
    </div>

    <!-- Bootstrap 5.3.3 JS Bundle (incluye Popper) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <?php // No necesitamos seguimiento.js si toda la lógica está en main.js o es simple HTML/PHP ?>
    <?php // <script src="<?php echo BASE_URL; ? >assets/js/seguimiento.js"></script> ?>
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
