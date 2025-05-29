<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// Proteger esta página - solo administradores
// proteger_pagina_admin(); // Descomentar cuando el sistema de autenticación esté implementado

$page_title = "Gestión de Tickets"; // Título específico para esta página

// Variables para el formulario de edición
$edit_ticket_id = null;
$current_ticket = null;
$all_tickets = [];

// Manejar la acción de actualizar ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ticket'])) {
    $ticket_id_to_update = limpiar_datos($_POST['ticket_id']);
    $tipo_averia_form = limpiar_datos($_POST['tipo_averia']);
    $prioridad = limpiar_datos($_POST['prioridad']);
    $diagnostico = limpiar_datos($_POST['diagnostico_admin']); 
    $cierre_solucion = limpiar_datos($_POST['solucion_admin']); 
    $estado = limpiar_datos($_POST['estado']);
    $fecha_actualizacion = date('Y-m-d H:i:s');
    $fecha_cierre = null;

    if ($estado == 'Resuelto' || $estado == 'Cerrado') {
        $fecha_cierre = $fecha_actualizacion;
    }

    $sql_update = "UPDATE tickets SET
                    tipo_averia = ?, 
                    prioridad = ?,
                    diagnostico_admin = ?,
                    solucion_admin = ?,
                    estado = ?,
                    ultima_actualizacion = ?, 
                    fecha_cierre = ?
                  WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        $stmt_update->bind_param("ssssssss", 
            $tipo_averia_form, 
            $prioridad,
            $diagnostico,
            $cierre_solucion,
            $estado,
            $fecha_actualizacion, 
            $fecha_cierre,
            $ticket_id_to_update
        );
        if ($stmt_update->execute()) {
            if ($stmt_update->affected_rows > 0) {
                $success_message = "Ticket " . htmlspecialchars($ticket_id_to_update) . " actualizado correctamente.";
            } else if ($stmt_update->affected_rows == 0) {
                if (empty($stmt_update->error)) {
                     $warning_message = "Se procesó la solicitud para el ticket " . htmlspecialchars($ticket_id_to_update) . ", pero no se realizaron cambios (los datos pueden ser los mismos que los existentes o el ticket no requería actualización).";
                } else {
                    $error_message = "Error después de ejecutar la actualización (affected_rows = 0): " . $stmt_update->error;
                }
            } else {
                $error_message = "Error al actualizar el ticket (affected_rows < 0): " . $stmt_update->error;
            }
        } else {
            $error_message = "Error al ejecutar la actualización: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $error_message = "Error al preparar la actualización: " . $conn->error;
    }
}

// Cargar un ticket para edición si se pasa un ID por GET
if (isset($_GET['edit_id'])) {
    $edit_ticket_id = limpiar_datos($_GET['edit_id']);
    $sql_edit = "SELECT t.*, d.nombre_departamento,
                 t.nombre_solicitante AS solicitante_nombre,
                 t.email_solicitante AS email_solicitante, 
                 t.asunto AS ticket_asunto, 
                 t.descripcion AS ticket_descripcion,
                 t.archivo_adjunto AS ticket_archivo_adjunto,
                 t.comentario_usuario, t.calificacion_usuario, t.fecha_feedback,
                 t.tipo_averia, t.diagnostico_admin, t.solucion_admin
                 FROM tickets t
                 LEFT JOIN departamentos d ON t.id_departamento = d.id
                 WHERE t.id = ?";
    $stmt_edit = $conn->prepare($sql_edit);
    if ($stmt_edit) {
        $stmt_edit->bind_param("s", $edit_ticket_id);
        $stmt_edit->execute();
        $result_edit = $stmt_edit->get_result();
        if ($result_edit->num_rows > 0) {
            $current_ticket = $result_edit->fetch_assoc();
        } else {
            $error_message = "No se encontró el ticket con ID " . htmlspecialchars($edit_ticket_id);
            $edit_ticket_id = null; 
        }
        $stmt_edit->close();
    } else {
        $error_message = "Error al preparar la consulta de edición: " . $conn->error;
    }
}

// Obtener todos los tickets para el listado
// Usar d.nombre_departamento consistentemente.
$sql_all_tickets = "SELECT t.id, t.fecha_creacion, d.nombre_departamento, t.asunto, t.estado, t.prioridad, 
                    t.calificacion_usuario, t.comentario_usuario 
                    FROM tickets t 
                    LEFT JOIN departamentos d ON t.id_departamento = d.id 
                    ORDER BY t.fecha_creacion DESC";
$result_all_tickets = $conn->query($sql_all_tickets);
if ($result_all_tickets) {
    while ($row = $result_all_tickets->fetch_assoc()) {
        $all_tickets[] = $row;
    }
} else {
    // Asegurarse de que $error_message no se sobrescriba si ya hay un error de edición.
    $error_message = (isset($error_message) ? $error_message . "<br>" : "") . "Error al obtener el listado de tickets: " . $conn->error;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(SITE_TITLE); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="icon" href="<?php echo BASE_URL; ?>img/logo.png" type="image/png">
</head>
<body class="has-sidebar"> <?php // Cambio de clase ?>

    <?php include_once __DIR__ . '/../core/templates/sidebar_public.php'; // Sidebar unificado ?>

    <div class="dashboard-main-content page-content"> <?php // Contenedor principal con clase page-content ?>
        
        <div class="section-header mb-4"> <?php // Encabezado de página estándar ?>
            <h1 class="section-title"><i class="fas fa-tasks"></i> <?php echo htmlspecialchars($page_title); ?></h1>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        <?php if (!empty($warning_message)): ?>
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> <?php echo $warning_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($edit_ticket_id && $current_ticket): ?>
            <div class="widget modern-widget mb-4">
                <div class="widget-header">
                     <h3 class="widget-title"><i class="fas fa-edit"></i> Editar Ticket: #<?php echo htmlspecialchars($current_ticket['id']); ?></h3>
                </div>
                <div class="widget-body">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?edit_id=' . htmlspecialchars($edit_ticket_id); ?>">
                        <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($current_ticket['id']); ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3 p-3 bg-light border rounded">
                                    <h5><i class="fas fa-user-circle"></i> Solicitante</h5>
                                    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($current_ticket['solicitante_nombre']); ?></p>
                                    <p><strong>Email:</strong> <?php echo htmlspecialchars($current_ticket['email_solicitante'] ?? 'N/A'); ?></p>
                                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($current_ticket['nombre_departamento'] ?? 'N/A'); ?></p>
                                    <p><strong>Fecha Creación:</strong> <?php echo htmlspecialchars(formatear_fecha_hora($current_ticket['fecha_creacion'])); ?></p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3 p-3 bg-light border rounded">
                                    <h5><i class="fas fa-file-alt"></i> Detalles del Ticket</h5>
                                    <p><strong>Asunto:</strong> <?php echo htmlspecialchars($current_ticket['ticket_asunto']); ?></p>
                                    <p><strong>Descripción:</strong><br><?php echo nl2br(htmlspecialchars($current_ticket['ticket_descripcion'])); ?></p>
                                    <?php if (!empty($current_ticket['ticket_archivo_adjunto'])): ?>
                                        <p><strong>Adjunto:</strong> <a href="<?php echo BASE_URL . 'uploads/' . htmlspecialchars(basename($current_ticket['ticket_archivo_adjunto'])); ?>" target="_blank"><?php echo htmlspecialchars(basename($current_ticket['ticket_archivo_adjunto'])); ?></a></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="tipo_averia" class="form-label">Tipo de Incidencia/Avería:</label>
                                <select class="form-select" id="tipo_averia" name="tipo_averia">
                                    <option value="Hardware" <?php echo ($current_ticket['tipo_averia'] == 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                                    <option value="Software" <?php echo ($current_ticket['tipo_averia'] == 'Software') ? 'selected' : ''; ?>>Software</option>
                                    <option value="Red" <?php echo ($current_ticket['tipo_averia'] == 'Red') ? 'selected' : ''; ?>>Red</option>
                                    <option value="Impresora" <?php echo ($current_ticket['tipo_averia'] == 'Impresora') ? 'selected' : ''; ?>>Impresora</option>
                                    <option value="Usuario" <?php echo ($current_ticket['tipo_averia'] == 'Usuario') ? 'selected' : ''; ?>>Usuario</option>
                                    <option value="Otro" <?php echo ($current_ticket['tipo_averia'] == 'Otro') ? 'selected' : ''; ?>>Otro</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="prioridad" class="form-label">Prioridad:</label>
                                <select class="form-select" id="prioridad" name="prioridad">
                                    <option value="Bajo" <?php echo ($current_ticket['prioridad'] == 'Bajo') ? 'selected' : ''; ?>>Bajo</option>
                                    <option value="Medio" <?php echo ($current_ticket['prioridad'] == 'Medio') ? 'selected' : ''; ?>>Medio</option>
                                    <option value="Alto" <?php echo ($current_ticket['prioridad'] == 'Alto') ? 'selected' : ''; ?>>Alto</option>
                                    <option value="Grave" <?php echo ($current_ticket['prioridad'] == 'Grave') ? 'selected' : ''; ?>>Grave</option>
                                    <option value="Muy Grave" <?php echo ($current_ticket['prioridad'] == 'Muy Grave') ? 'selected' : ''; ?>>Muy Grave</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="estado" class="form-label">Estado:</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="Abierto" <?php echo ($current_ticket['estado'] == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                                    <option value="En Progreso" <?php echo ($current_ticket['estado'] == 'En Progreso') ? 'selected' : ''; ?>>En Progreso</option>
                                    <option value="En Espera" <?php echo ($current_ticket['estado'] == 'En Espera') ? 'selected' : ''; ?>>En Espera</option>
                                    <option value="Resuelto" <?php echo ($current_ticket['estado'] == 'Resuelto') ? 'selected' : ''; ?>>Resuelto</option>
                                    <option value="Cerrado" <?php echo ($current_ticket['estado'] == 'Cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="diagnostico_admin" class="form-label">Diagnóstico Técnico:</label>
                            <textarea class="form-control" id="diagnostico_admin" name="diagnostico_admin" rows="3"><?php echo htmlspecialchars($current_ticket['diagnostico_admin'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="solucion_admin" class="form-label">Solución Aplicada y Cierre:</label>
                            <textarea class="form-control" id="solucion_admin" name="solucion_admin" rows="3"><?php echo htmlspecialchars($current_ticket['solucion_admin'] ?? ''); ?></textarea>
                        </div>
                        
                        <?php if(isset($current_ticket['comentario_usuario']) && !empty($current_ticket['comentario_usuario'])): ?>
                        <div class="mb-3 p-3 bg-light border rounded">
                            <h5><i class="fas fa-comment-dots"></i> Feedback del Usuario</h5>
                            <p><strong>Calificación:</strong> 
                                <?php if(isset($current_ticket['calificacion_usuario']) && $current_ticket['calificacion_usuario'] > 0): ?>
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo ($i <= $current_ticket['calificacion_usuario']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                    (<?php echo $current_ticket['calificacion_usuario']; ?>/5)
                                <?php else: ?>
                                    No calificado
                                <?php endif; ?>
                            </p>
                            <p><strong>Comentario:</strong><br><?php echo nl2br(htmlspecialchars($current_ticket['comentario_usuario'])); ?></p>
                            <p><small>Fecha Feedback: <?php echo htmlspecialchars(formatear_fecha_hora($current_ticket['fecha_feedback'])); ?></small></p>
                        </div>
                        <?php endif; ?>

                        <div class="d-flex justify-content-end gap-2">
                            <button type="submit" name="update_ticket" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar Ticket</button>
                            <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn btn-secondary"><i class="fas fa-times"></i> Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="widget modern-widget">
            <div class="widget-header">
                <h3 class="widget-title"><i class="fas fa-list-ul"></i> Listado de Tickets</h3>
            </div>
            <div class="widget-body no-padding">
                <?php if (!empty($all_tickets)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover modern-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Fecha Creación</th>
                                    <th>Departamento</th>
                                    <th>Asunto</th>
                                    <th>Estado</th>
                                    <th>Prioridad</th>
                                    <th>Feedback</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_tickets as $ticket): ?>
                                    <tr>
                                        <td><a href="<?php echo BASE_URL; ?>public/seguimiento.php?numero_ticket=<?php echo htmlspecialchars($ticket['id']); ?>" target="_blank">#<?php echo htmlspecialchars($ticket['id']); ?></a></td>
                                        <td><?php echo htmlspecialchars(formatear_fecha_hora($ticket['fecha_creacion'])); ?></td>
                                        <td><?php echo htmlspecialchars($ticket['nombre_departamento'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars(limitar_texto($ticket['asunto'], 40)); ?></td>
                                        <td><span class="badge rounded-pill bg-<?php echo obtener_clase_estado($ticket['estado']); ?>"><?php echo htmlspecialchars($ticket['estado']); ?></span></td>
                                        <td><span class="badge rounded-pill bg-<?php echo obtener_clase_prioridad($ticket['prioridad']); ?> text-dark"><?php echo htmlspecialchars($ticket['prioridad'] ?? 'N/A'); ?></span></td>
                                        <td>
                                            <?php if (!empty($ticket['calificacion_usuario'])): ?>
                                                <?php for($i = 1; $i <= 5; $i++): ?>
                                                    <i class="fas fa-star fa-xs <?php echo ($i <= $ticket['calificacion_usuario']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                                <?php endfor; ?>
                                            <?php else: ?>
                                                <span class="text-muted small">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']) . '?edit_id=' . htmlspecialchars($ticket['id']); ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i> Editar</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center p-3">No hay tickets registrados.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        // Activar tooltips si se usan
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
<?php
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
