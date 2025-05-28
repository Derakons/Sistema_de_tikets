<?php
require_once '../core/config.php';
require_once '../core/functions.php';

// Proteger esta página - solo administradores
// proteger_pagina_admin(); // Descomentar cuando el sistema de autenticación esté implementado

$page_title = "Dashboard Administrativo";
require_once '../core/templates/header.php';

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

// Tickets recientes (últimos 5)
$recientes_query = "SELECT t.*, d.nombre_departamento FROM tickets t 
                   LEFT JOIN departamentos d ON t.id_departamento = d.id 
                   ORDER BY t.fecha_creacion DESC LIMIT 5";
$result_recientes = $conn->query($recientes_query);
$tickets_recientes = [];
if ($result_recientes) {
    while ($row = $result_recientes->fetch_assoc()) {
        $tickets_recientes[] = $row;
    }
}

// Tickets pendientes (abiertos y en progreso)
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
}

// Obtener promedio de calificaciones
$rating_query = "SELECT AVG(calificacion_usuario) as promedio, COUNT(calificacion_usuario) as total_calificados 
                FROM tickets WHERE calificacion_usuario IS NOT NULL AND calificacion_usuario > 0";
$result_rating = $conn->query($rating_query);
$rating_data = $result_rating ? $result_rating->fetch_assoc() : ['promedio' => 0, 'total_calificado' => 0];

$conn->close();
?>
    $tickets_pendientes[] = $row;
}

// Feedback reciente
$feedback_query = "SELECT t.id, t.asunto, t.calificacion_usuario, t.comentario_usuario, t.fecha_feedback, d.nombre_departamento 
                  FROM tickets t 
                  LEFT JOIN departamentos d ON t.id_departamento = d.id 
                  WHERE t.calificacion_usuario IS NOT NULL 
                  ORDER BY t.fecha_feedback DESC LIMIT 5";
$result_feedback = $conn->query($feedback_query);
$feedback_reciente = [];
while ($row = $result_feedback->fetch_assoc()) {
    $feedback_reciente[] = $row;
}

// Promedio de calificaciones
$rating_query = "SELECT AVG(calificacion_usuario) as promedio FROM tickets WHERE calificacion_usuario IS NOT NULL";
$result_rating = $conn->query($rating_query);
$promedio_rating = $result_rating->fetch_assoc()['promedio'] ?? 0;

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Administrativo - Sistema de Tickets</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #28a745;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
            --shadow-color: rgba(0,0,0,0.1);
        }

        .dashboard-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f6fa;
            min-height: calc(100vh - 140px);
        }

        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), #0052a3);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .dashboard-title {
            font-size: 2.5em;
            margin: 0;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0,0,0,0.2);
        }

        .dashboard-subtitle {
            font-size: 1.1em;
            margin: 10px 0 0 0;
            opacity: 0.9;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .quick-btn {
            background: rgba(255,255,255,0.2);
            border: 2px solid rgba(255,255,255,0.3);
            color: white;
            padding: 12px 24px;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .quick-btn:hover {
            background: rgba(255,255,255,0.3);
            border-color: rgba(255,255,255,0.5);
            transform: translateY(-2px);
            color: white;
            text-decoration: none;
        }

        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .widget {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px var(--shadow-color);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .widget:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .widget-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border-color);
        }

        .widget-title {
            font-size: 1.3em;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .widget-icon {
            background: linear-gradient(135deg, var(--primary-color), #0052a3);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2em;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: var(--light-color);
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9em;
            color: var(--dark-color);
            font-weight: 500;
        }

        .ticket-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .ticket-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            margin-bottom: 10px;
            background: white;
            transition: all 0.3s ease;
        }

        .ticket-item:hover {
            border-color: var(--primary-color);
            box-shadow: 0 3px 10px rgba(0,102,204,0.1);
        }

        .ticket-info h4 {
            margin: 0 0 5px 0;
            font-size: 1.1em;
            color: var(--dark-color);
        }

        .ticket-meta {
            font-size: 0.85em;
            color: #666;
        }

        .ticket-actions {
            display: flex;
            gap: 8px;
        }

        .btn-mini {
            padding: 6px 12px;
            font-size: 0.8em;
            border-radius: 5px;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary { background: var(--primary-color); color: white; }
        .btn-success { background: var(--secondary-color); color: white; }
        .btn-warning { background: var(--warning-color); color: var(--dark-color); }
        .btn-danger { background: var(--danger-color); color: white; }
        .btn-info { background: var(--info-color); color: white; }

        .btn-mini:hover {
            opacity: 0.8;
            transform: translateY(-1px);
        }

        .estado-badge {
            padding: 4px 8px;
            border-radius: 15px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .estado-abierto { background: #e9ecef; color: #495057; }
        .estado-en-progreso { background: #cce5ff; color: #004085; }
        .estado-esperando { background: #fff3cd; color: #856404; }
        .estado-resuelto { background: #d1e7dd; color: #0f5132; }
        .estado-cerrado { background: #cfe2ff; color: #0a58ca; }

        .prioridad-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 0.75em;
            font-weight: 600;
        }

        .prioridad-muy-grave, .prioridad-alto { background: #f8d7da; color: #721c24; }
        .prioridad-grave, .prioridad-medio { background: #fff3cd; color: #856404; }
        .prioridad-leve, .prioridad-bajo { background: #d1e7dd; color: #0f5132; }

        .feedback-item {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            border-left: 4px solid var(--warning-color);
        }

        .feedback-rating {
            display: flex;
            gap: 3px;
            margin-bottom: 8px;
        }

        .star {
            color: #ffc107;
            font-size: 1.1em;
        }

        .star.empty {
            color: #e0e0e0;
        }

        .rating-summary {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--warning-color), #e0a800);
            color: white;
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .rating-number {
            font-size: 3em;
            font-weight: bold;
            margin-bottom: 10px;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                padding: 15px;
            }
            
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                justify-content: center;
            }
            
            .ticket-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .ticket-actions {
                width: 100%;
                justify-content: flex-end;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Header del Dashboard -->
        <div class="dashboard-header">
            <h1 class="dashboard-title">
                <i class="fas fa-tachometer-alt"></i> Dashboard Administrativo
            </h1>
            <p class="dashboard-subtitle">
                Panel de control centralizado - Bienvenido, <?php echo htmlspecialchars(getAdminFullName()); ?>
            </p>
              <div class="quick-actions">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="quick-btn">
                    <i class="fas fa-list"></i> Panel Completo
                </a>
                <a href="<?php echo BASE_URL; ?>public/index.php" class="quick-btn">
                    <i class="fas fa-plus"></i> Crear Ticket
                </a>
                <a href="<?php echo BASE_URL; ?>public/seguimiento.php" class="quick-btn">
                    <i class="fas fa-search"></i> Seguimiento
                </a>
                <a href="<?php echo BASE_URL; ?>setup.php" class="quick-btn">
                    <i class="fas fa-cog"></i> Configuración
                </a>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="quick-btn">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>

        <!-- Grid Principal del Dashboard -->
        <div class="dashboard-grid">
            <!-- Widget de Estadísticas Generales -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-chart-bar"></i></span>
                        Estadísticas Generales
                    </h3>
                </div>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['total_tickets']; ?></div>
                        <div class="stat-label">Total Tickets</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['por_estado']['Abierto'] ?? 0; ?></div>
                        <div class="stat-label">Abiertos</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['por_estado']['En Progreso'] ?? 0; ?></div>
                        <div class="stat-label">En Progreso</div>
                    </div>
                    <div class="stat-item">
                        <div class="stat-number"><?php echo $stats['por_estado']['Cerrado'] ?? 0; ?></div>
                        <div class="stat-label">Cerrados</div>
                    </div>
                </div>
            </div>

            <!-- Widget de Tickets Pendientes -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-clock"></i></span>
                        Tickets Pendientes
                    </h3>
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn-mini btn-primary">Ver Todos</a>
                </div>
                <div class="ticket-list">
                    <?php if (count($tickets_pendientes) > 0): ?>
                        <?php foreach ($tickets_pendientes as $ticket): ?>
                        <div class="ticket-item">
                            <div class="ticket-info">
                                <h4>#<?php echo htmlspecialchars($ticket['id']); ?> - <?php echo htmlspecialchars($ticket['asunto']); ?></h4>
                                <div class="ticket-meta">
                                    <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                        <?php echo htmlspecialchars($ticket['estado']); ?>
                                    </span>
                                    <?php if ($ticket['prioridad']): ?>
                                    <span class="prioridad-badge prioridad-<?php echo strtolower(str_replace(' ', '-', $ticket['prioridad'])); ?>">
                                        <?php echo htmlspecialchars($ticket['prioridad']); ?>
                                    </span>
                                    <?php endif; ?>
                                    <br>
                                    <small><?php echo htmlspecialchars($ticket['nombre_departamento'] ?? 'Sin departamento'); ?> - 
                                    <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></small>
                                </div>
                            </div>                            <div class="ticket-actions">
                                <a href="<?php echo BASE_URL; ?>admin/index.php?edit_id=<?php echo htmlspecialchars($ticket['id']); ?>" class="btn-mini btn-primary">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">
                            <i class="fas fa-check-circle" style="font-size: 2em; color: var(--secondary-color);"></i><br>
                            ¡Excelente! No hay tickets pendientes.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget de Tickets Recientes -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-history"></i></span>
                        Actividad Reciente
                    </h3>
                </div>
                <div class="ticket-list">
                    <?php foreach ($tickets_recientes as $ticket): ?>
                    <div class="ticket-item">
                        <div class="ticket-info">
                            <h4>#<?php echo htmlspecialchars($ticket['id']); ?> - <?php echo htmlspecialchars($ticket['asunto']); ?></h4>
                            <div class="ticket-meta">
                                <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                    <?php echo htmlspecialchars($ticket['estado']); ?>
                                </span>
                                <br>
                                <small><?php echo htmlspecialchars($ticket['nombre_departamento'] ?? 'Sin departamento'); ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($ticket['fecha_creacion'])); ?></small>
                            </div>
                        </div>                        <div class="ticket-actions">
                            <a href="<?php echo BASE_URL; ?>admin/index.php?edit_id=<?php echo htmlspecialchars($ticket['id']); ?>" class="btn-mini btn-info">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Widget de Feedback y Calificaciones -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-star"></i></span>
                        Feedback del Usuario
                    </h3>
                </div>
                
                <?php if ($promedio_rating > 0): ?>
                <div class="rating-summary">
                    <div class="rating-number"><?php echo number_format($promedio_rating, 1); ?>/5</div>
                    <div>Calificación Promedio</div>
                    <div class="feedback-rating" style="justify-content: center; margin-top: 10px;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo $i <= round($promedio_rating) ? 'star' : 'star empty'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>

                <div style="max-height: 300px; overflow-y: auto;">
                    <?php if (count($feedback_reciente) > 0): ?>
                        <?php foreach ($feedback_reciente as $feedback): ?>
                        <div class="feedback-item">
                            <div class="feedback-rating">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fas fa-star <?php echo $i <= $feedback['calificacion_usuario'] ? 'star' : 'star empty'; ?>"></i>
                                <?php endfor; ?>
                                <span style="margin-left: 8px; font-weight: 600;">(<?php echo $feedback['calificacion_usuario']; ?>/5)</span>
                            </div>
                            <h5 style="margin: 0 0 8px 0; color: var(--dark-color);">
                                #<?php echo htmlspecialchars($feedback['id']); ?> - <?php echo htmlspecialchars($feedback['asunto']); ?>
                            </h5>
                            <?php if ($feedback['comentario_usuario']): ?>
                            <p style="margin: 0 0 8px 0; font-style: italic; color: #666;">
                                "<?php echo htmlspecialchars($feedback['comentario_usuario']); ?>"
                            </p>
                            <?php endif; ?>
                            <small style="color: #888;">
                                <?php echo htmlspecialchars($feedback['nombre_departamento']); ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($feedback['fecha_feedback'])); ?>
                            </small>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="text-align: center; color: #666; padding: 20px;">
                            <i class="fas fa-comment-slash" style="font-size: 2em; margin-bottom: 10px;"></i><br>
                            No hay feedback disponible aún.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Widget de Distribución por Prioridad -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-exclamation-triangle"></i></span>
                        Por Prioridad
                    </h3>
                </div>
                <div class="stats-grid">
                    <?php foreach ($stats['por_prioridad'] as $prioridad => $cantidad): ?>
                        <?php if ($prioridad): ?>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $cantidad; ?></div>
                            <div class="stat-label"><?php echo htmlspecialchars($prioridad); ?></div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Widget de Acciones Rápidas -->
            <div class="widget">
                <div class="widget-header">
                    <h3 class="widget-title">
                        <span class="widget-icon"><i class="fas fa-tools"></i></span>
                        Herramientas Rápidas
                    </h3>
                </div>                <div style="display: grid; gap: 15px;">
                    <a href="<?php echo BASE_URL; ?>public/index.php" class="btn-mini btn-success" style="justify-content: center; padding: 15px;">
                        <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket
                    </a>
                    <a href="<?php echo BASE_URL; ?>public/seguimiento.php" class="btn-mini btn-info" style="justify-content: center; padding: 15px;">
                        <i class="fas fa-search"></i> Consultar Estado de Ticket
                    </a>
                    <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn-mini btn-primary" style="justify-content: center; padding: 15px;">
                        <i class="fas fa-list-alt"></i> Ver Todos los Tickets
                    </a>
                    <a href="<?php echo BASE_URL; ?>setup.php" class="btn-mini btn-warning" style="justify-content: center; padding: 15px;">
                        <i class="fas fa-database"></i> Configuración del Sistema
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-refresh cada 5 minutos
        setTimeout(function() {
            window.location.reload();
        }, 300000);

        // Animaciones suaves al cargar
        document.addEventListener('DOMContentLoaded', function() {
            const widgets = document.querySelectorAll('.widget');
            widgets.forEach((widget, index) => {
                widget.style.opacity = '0';
                widget.style.transform = 'translateY(20px)';
                setTimeout(() => {
                    widget.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
                    widget.style.opacity = '1';
                    widget.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
</html>
