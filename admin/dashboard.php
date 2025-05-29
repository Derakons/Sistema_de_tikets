<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// Proteger esta página - solo administradores
// proteger_pagina_admin(); // Descomentar cuando el sistema de autenticación esté implementado

$page_title = "Dashboard Administrativo";

// Variables para manejo de acciones
$success_message = '';
$error_message = '';
$warning_message = '';

// Procesar actualizaciones rápidas de tickets
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['quick_update'])) {
    $ticket_id = limpiar_datos($_POST['ticket_id']);
    $new_status = limpiar_datos($_POST['new_status']);
    $update_time = date('Y-m-d H:i:s');
    
    $sql_quick = "UPDATE tickets SET estado = ?, ultima_actualizacion = ? WHERE id = ?";
    $stmt_quick = $conn->prepare($sql_quick);
    if ($stmt_quick) {
        $stmt_quick->bind_param("sss", $new_status, $update_time, $ticket_id);
        if ($stmt_quick->execute()) {
            $success_message = "Estado del ticket #$ticket_id actualizado a '$new_status'.";
        } else {
            $error_message = "Error al actualizar el ticket: " . $stmt_quick->error;
        }
        $stmt_quick->close();
    }
}

// Obtener estadísticas del sistema
$stats = [];

// Total de tickets
$result_total = $conn->query("SELECT COUNT(*) as total FROM tickets");
$stats['total_tickets'] = $result_total ? $result_total->fetch_assoc()['total'] : 0;

// Tickets por estado
$estados_query = "SELECT estado, COUNT(*) as cantidad FROM tickets GROUP BY estado";
$result_estados = $conn->query($estados_query);
$stats['por_estado'] = [];
if ($result_estados) {
    while ($row = $result_estados->fetch_assoc()) {
        $stats['por_estado'][$row['estado']] = $row['cantidad'];
    }
}

// Tickets por prioridad
$prioridad_query = "SELECT prioridad, COUNT(*) as cantidad FROM tickets WHERE prioridad IS NOT NULL AND prioridad != '' GROUP BY prioridad";
$result_prioridad = $conn->query($prioridad_query);
$stats['por_prioridad'] = [];
if ($result_prioridad) {
    while ($row = $result_prioridad->fetch_assoc()) {
        $stats['por_prioridad'][$row['prioridad']] = $row['cantidad'];
    }
}

// Tickets recientes (últimos 5) - Intentando con 'id_departamento'
$recientes_query = "SELECT t.*, d.nombre_departamento FROM tickets t 
                   LEFT JOIN departamentos d ON t.id_departamento = d.id 
                   ORDER BY t.fecha_creacion DESC LIMIT 5";
$result_recientes = $conn->query($recientes_query);
$tickets_recientes = [];
if ($result_recientes) {
    while ($row = $result_recientes->fetch_assoc()) {
        $tickets_recientes[] = $row;
    }
} else {
    $error_message .= " Error al obtener tickets recientes: " . $conn->error;
}

// Tickets pendientes (abiertos y en progreso) - Intentando con 'id_departamento'
$pendientes_query = "SELECT t.*, d.nombre_departamento FROM tickets t 
                    LEFT JOIN departamentos d ON t.id_departamento = d.id 
                    WHERE t.estado IN ('Abierto', 'En Progreso') 
                    ORDER BY t.fecha_creacion ASC LIMIT 8";
$result_pendientes = $conn->query($pendientes_query);
$tickets_pendientes = [];
if ($result_pendientes) {
    while ($row = $result_pendientes->fetch_assoc()) {
        $tickets_pendientes[] = $row;
    }
} else {
    $error_message .= " Error al obtener tickets pendientes: " . $conn->error;
}


// Obtener promedio de calificaciones
$rating_query = "SELECT AVG(calificacion_usuario) as promedio, COUNT(calificacion_usuario) as total_calificados 
                FROM tickets WHERE calificacion_usuario IS NOT NULL AND calificacion_usuario > 0";
$result_rating = $conn->query($rating_query);
$rating_data = $result_rating ? $result_rating->fetch_assoc() : ['promedio' => 0, 'total_calificados' => 0];


$feedback_query = "SELECT t.id, t.asunto, t.calificacion_usuario, t.comentario_usuario, t.fecha_feedback, d.nombre_departamento
                  FROM tickets t
                  LEFT JOIN departamentos d ON t.id_departamento = d.id
                  WHERE t.calificacion_usuario IS NOT NULL
                  ORDER BY t.fecha_feedback DESC LIMIT 5"; // Corregido t.departamento_id a t.id_departamento y eliminadas \
$result_feedback = $conn->query($feedback_query);
$feedback_reciente = [];
if ($result_feedback) { 
    while ($row = $result_feedback->fetch_assoc()) {
        $feedback_reciente[] = $row;
    }
} else {
    $error_message .= " Error al obtener feedback reciente: " . $conn->error;
}

// $conn->close(); // No cerramos la conexión aquí, se cerrará al final del script si es necesario o por PHP.
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
    <!-- Los estilos específicos del dashboard que estaban en <style> ahora estarán en main.css o un dashboard.css dedicado -->
</head>
<body class="has-sidebar"> <?php // Cambiado de dashboard-body a has-sidebar ?>

    <?php include_once __DIR__ . '/../core/templates/sidebar_public.php'; // Incluir sidebar_public ?>

    <div class="dashboard-main-content">
        <header class="dashboard-top-header">
            <div class="header-left">
                <h1 class="dashboard-page-title"><?php echo htmlspecialchars($page_title); ?></h1>
            </div>
            <div class="header-right">
                <div class="user-info">
                    <i class="fas fa-user-circle user-avatar"></i>
                    <span><?php echo htmlspecialchars(getAdminFullName()); ?></span>
                </div>
                <button class="btn btn-outline-light btn-sm" onclick="toggleTheme()">
                    <i class="fas fa-moon"></i>/<i class="fas fa-sun"></i>
                </button>
            </div>
        </header>

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

        <!-- Grid Principal del Dashboard -->
        <div class="row dashboard-grid-cards">
            <!-- Tarjeta de Estadísticas: Total Tickets -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-total h-100">
                    <div class="stat-card-body">
                        <div class="stat-card-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-title">Total Tickets</div>
                            <div class="stat-card-value"><?php echo $stats['total_tickets']; ?></div>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="stat-card-footer">
                        Ver Detalles <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Tarjeta de Estadísticas: Tickets Abiertos -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-open h-100">
                    <div class="stat-card-body">
                        <div class="stat-card-icon">
                            <i class="fas fa-folder-open"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-title">Tickets Abiertos</div>
                            <div class="stat-card-value"><?php echo $stats['por_estado']['Abierto'] ?? 0; ?></div>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/index.php?status=Abierto" class="stat-card-footer">
                        Ver Detalles <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
            
            <!-- Tarjeta de Estadísticas: En Progreso -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-progress h-100">
                    <div class="stat-card-body">
                        <div class="stat-card-icon">
                            <i class="fas fa-tasks"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-title">En Progreso</div>
                            <div class="stat-card-value"><?php echo $stats['por_estado']['En Progreso'] ?? 0; ?></div>
                        </div>
                    </div>
                     <a href="<?php echo BASE_URL; ?>admin/index.php?status=En Progreso" class="stat-card-footer">
                        Ver Detalles <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>

            <!-- Tarjeta de Estadísticas: Tickets Cerrados -->
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="stat-card stat-card-closed h-100">
                    <div class="stat-card-body">
                        <div class="stat-card-icon">
                            <i class="fas fa-check-circle"></i>
                        </div>
                        <div class="stat-card-content">
                            <div class="stat-card-title">Tickets Cerrados</div>
                            <div class="stat-card-value"><?php echo $stats['por_estado']['Cerrado'] ?? 0; ?></div>
                        </div>
                    </div>
                    <a href="<?php echo BASE_URL; ?>admin/index.php?status=Cerrado" class="stat-card-footer">
                        Ver Detalles <i class="fas fa-arrow-circle-right"></i>
                    </a>
                </div>
            </div>
        </div>


        <div class="row">
            <!-- Columna Izquierda: Tickets Recientes y Pendientes -->
            <div class="col-lg-7 mb-4">
                <!-- Widget de Tickets Recientes -->
                <div class="widget modern-widget">
                    <div class="widget-header">
                        <h3 class="widget-title"><i class="fas fa-history"></i> Tickets Recientes</h3>
                        <a href="<?php echo BASE_URL; ?>admin/index.php?filter=recientes" class="btn btn-sm btn-outline-primary">Ver Todos</a>
                    </div>
                    <div class="widget-body no-padding">
                        <?php if (!empty($tickets_recientes)): ?>
                            <ul class="list-group list-group-flush modern-ticket-list">
                                <?php foreach ($tickets_recientes as $ticket): ?>
                                    <li class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><a href="<?php echo BASE_URL; ?>public/seguimiento.php?ticket_id=<?php echo htmlspecialchars($ticket['id']); ?>" target="_blank">#<?php echo htmlspecialchars($ticket['id']); ?> - <?php echo htmlspecialchars(limitar_texto($ticket['asunto'], 40)); ?></a></h5>
                                            <small class="text-muted"><?php echo tiempo_transcurrido($ticket['fecha_creacion']); ?></small>
                                        </div>
                                        <p class="mb-1 text-muted small">
                                            <?php echo htmlspecialchars($ticket['nombre_usuario'] ?? 'Usuario Desconocido'); ?> | <?php echo htmlspecialchars($ticket['nombre_departamento'] ?? 'N/A'); ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="badge rounded-pill bg-<?php echo obtener_clase_estado($ticket['estado']); ?>">
                                                <?php echo htmlspecialchars($ticket['estado']); ?>
                                            </span>
                                            <span class="badge rounded-pill bg-<?php echo obtener_clase_prioridad($ticket['prioridad']); ?> text-dark">
                                                Prioridad: <?php echo htmlspecialchars($ticket['prioridad']); ?>
                                            </span>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-center p-3">No hay tickets recientes.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Widget de Tickets Pendientes -->
                <div class="widget modern-widget mt-4">
                    <div class="widget-header">
                        <h3 class="widget-title"><i class="fas fa-exclamation-circle"></i> Tickets Pendientes (Abiertos/En Progreso)</h3>
                        <a href="<?php echo BASE_URL; ?>admin/index.php?filter=pendientes" class="btn btn-sm btn-outline-warning">Ver Todos</a>
                    </div>
                    <div class="widget-body no-padding">
                        <?php if (!empty($tickets_pendientes)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover modern-table">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Asunto</th>
                                            <th>Usuario</th>
                                            <th>Estado</th>
                                            <th>Prioridad</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets_pendientes as $ticket): ?>
                                            <tr>
                                                <td><a href="<?php echo BASE_URL; ?>public/seguimiento.php?ticket_id=<?php echo htmlspecialchars($ticket['id']); ?>" target="_blank">#<?php echo htmlspecialchars($ticket['id']); ?></a></td>
                                                <td><?php echo htmlspecialchars(limitar_texto($ticket['asunto'], 30)); ?></td>
                                                <td><?php echo htmlspecialchars($ticket['nombre_usuario'] ?? 'Usuario Desconocido'); ?></td>
                                                <td><span class="badge rounded-pill bg-<?php echo obtener_clase_estado($ticket['estado']); ?>"><?php echo htmlspecialchars($ticket['estado']); ?></span></td>
                                                <td><span class="badge rounded-pill bg-<?php echo obtener_clase_prioridad($ticket['prioridad']); ?> text-dark"><?php echo htmlspecialchars($ticket['prioridad']); ?></span></td>
                                                <td>
                                                    <form method="POST" action="" class="d-inline-block me-1">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $ticket['id']; ?>">
                                                        <select name="new_status" class="form-select form-select-sm d-inline-block" style="width: auto; font-size: 0.8rem; padding: 0.2rem 0.5rem;" onchange="this.form.submit()">
                                                            <option value="Abierto" <?php if($ticket['estado'] == 'Abierto') echo 'selected'; ?>>Abierto</option>
                                                            <option value="En Progreso" <?php if($ticket['estado'] == 'En Progreso') echo 'selected'; ?>>En Progreso</option>
                                                            <option value="Resuelto" <?php if($ticket['estado'] == 'Resuelto') echo 'selected'; ?>>Resuelto</option>
                                                            <option value="Cerrado" <?php if($ticket['estado'] == 'Cerrado') echo 'selected'; ?>>Cerrado</option>
                                                            <option value="En Espera" <?php if($ticket['estado'] == 'En Espera') echo 'selected'; ?>>En Espera</option>
                                                        </select>
                                                        <input type="hidden" name="quick_update" value="1">
                                                    </form>
                                                    <a href="<?php echo BASE_URL; ?>admin/index.php?view_ticket=<?php echo $ticket['id']; ?>" class="btn btn-sm btn-info"><i class="fas fa-eye"></i></a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center p-3">No hay tickets pendientes.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Feedback y Prioridades -->
            <div class="col-lg-5 mb-4">
                <!-- Widget de Feedback Reciente -->
                <div class="widget modern-widget">
                    <div class="widget-header">
                        <h3 class="widget-title"><i class="fas fa-comments"></i> Feedback Reciente</h3>
                    </div>
                    <div class="widget-body">
                        <?php if (!empty($feedback_reciente)): ?>
                            <?php foreach ($feedback_reciente as $feedback): ?>
                                <div class="feedback-item-modern mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <strong>Ticket #<?php echo htmlspecialchars($feedback['id']); ?>: <?php echo htmlspecialchars(limitar_texto($feedback['asunto'], 25)); ?></strong>
                                        <div class="rating-stars">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fas fa-star <?php echo ($i <= $feedback['calificacion_usuario']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                    <?php if(!empty($feedback['comentario_usuario'])): ?>
                                    <p class="text-muted small mt-1 mb-0">"<?php echo htmlspecialchars(limitar_texto($feedback['comentario_usuario'], 80)); ?>"</p>
                                    <?php endif; ?>
                                    <small class="text-muted d-block text-end"><?php echo tiempo_transcurrido($feedback['fecha_feedback']); ?></small>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-center">No hay feedback reciente.</p>
                        <?php endif; ?>
                         <div class="mt-3 text-center">
                            Promedio General: 
                            <span class="fw-bold fs-5 <?php echo ($rating_data['promedio'] > 0) ? 'text-warning' : 'text-muted'; ?>">
                                <?php echo number_format($rating_data['promedio'] ?? 0, 1); ?> <i class="fas fa-star"></i>
                            </span>
                            (<?php echo $rating_data['total_calificados'] ?? 0; ?> calificaciones)
                        </div>
                    </div>
                </div>

                <!-- Widget de Tickets por Prioridad -->
                <div class="widget modern-widget mt-4">
                    <div class="widget-header">
                        <h3 class="widget-title"><i class="fas fa-exclamation-triangle"></i> Tickets por Prioridad</h3>
                    </div>
                    <div class="widget-body">
                        <?php if (!empty($stats['por_prioridad'])): ?>
                            <?php 
                            $prioridades_ordenadas = ['Muy Grave', 'Alto', 'Grave', 'Medio', 'Leve', 'Bajo']; // Orden deseado
                            ?>
                            <ul class="list-group list-group-flush">
                            <?php foreach ($prioridades_ordenadas as $prioridad_nombre): ?>
                                <?php if (isset($stats['por_prioridad'][$prioridad_nombre]) && $stats['por_prioridad'][$prioridad_nombre] > 0): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <?php echo htmlspecialchars($prioridad_nombre); ?>
                                    <span class="badge bg-<?php echo obtener_clase_prioridad($prioridad_nombre); ?> rounded-pill text-dark">
                                        <?php echo $stats['por_prioridad'][$prioridad_nombre]; ?>
                                    </span>
                                </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-center">No hay datos de prioridad.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div> <!-- Cierre de dashboard-main-content -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
    <script>
        // Simple theme toggler (ejemplo, podría ser más robusto)
        function toggleTheme() {
            document.body.classList.toggle('dark-theme');
            // Aquí podrías guardar la preferencia en localStorage
        }

        // Activar tooltips si se usan
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
          return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html>
<?php
// Cerrar la conexión a la base de datos al final del script
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
?>
