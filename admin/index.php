<?php
require_once '../core/config.php';
require_once '../core/functions.php';
$page_title = "Panel de Administración"; // Definir título específico para esta página
require_once '../core/templates/header.php';


// Proteger esta página
// requireAdminLogin(); // Descomentar cuando el login esté implementado

$error_message = '';
$success_message = '';

// Variables para el formulario de edición
$edit_ticket_id = null;
$current_ticket = null;
$all_tickets = [];

// Manejar la acción de actualizar ticket
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_ticket'])) {
    $ticket_id_to_update = limpiar_datos($_POST['ticket_id']);
    $identificacion_tipo = limpiar_datos($_POST['identificacion_tipo']);
    $prioridad = limpiar_datos($_POST['prioridad']);
    $diagnostico = limpiar_datos($_POST['diagnostico']);
    $cierre_solucion = limpiar_datos($_POST['cierre_solucion']);
    $estado = limpiar_datos($_POST['estado']);
    $fecha_actualizacion_admin = date('Y-m-d H:i:s');
    $fecha_cierre = null;

    if ($estado == 'Resuelto' || $estado == 'Cerrado') {
        $fecha_cierre = $fecha_actualizacion_admin;
    }

    $sql_update = "UPDATE tickets SET 
                    identificacion_tipo = ?,
                    prioridad = ?,
                    diagnostico = ?,
                    cierre_solucion = ?,
                    estado = ?,
                    fecha_actualizacion_admin = ?,
                    fecha_cierre = ?
                  WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    if ($stmt_update) {
        $stmt_update->bind_param("ssssssss", 
            $identificacion_tipo, 
            $prioridad, 
            $diagnostico, 
            $cierre_solucion, 
            $estado, 
            $fecha_actualizacion_admin, 
            $fecha_cierre, 
            $ticket_id_to_update
        );
        if ($stmt_update->execute()) {
            $success_message = "Ticket " . htmlspecialchars($ticket_id_to_update) . " actualizado correctamente.";
        } else {
            $error_message = "Error al actualizar el ticket: " . $stmt_update->error;
        }
        $stmt_update->close();
    } else {
        $error_message = "Error al preparar la actualización: " . $conn->error;
    }
}

// Cargar un ticket para edición si se pasa un ID por GET
if (isset($_GET['edit_id'])) {
    $edit_ticket_id = limpiar_datos($_GET['edit_id']);
    $sql_edit = "SELECT t.*, d.nombre_departamento 
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
            $error_message = "No se encontró el ticket solicitado para editar.";
            $edit_ticket_id = null; // Resetear si no se encuentra
        }
        $stmt_edit->close();
    } else {
        $error_message = "Error al preparar la carga del ticket para edición: " . $conn->error;
    }
}

// Obtener todos los tickets para el listado
$sql_all_tickets = "SELECT t.id, t.fecha_creacion, d.nombre_departamento, t.descripcion_breve AS asunto, t.estado, t.prioridad 
                    FROM tickets t 
                    LEFT JOIN departamentos d ON t.id_departamento = d.id 
                    ORDER BY t.fecha_creacion DESC";
$result_all_tickets = $conn->query($sql_all_tickets);
if ($result_all_tickets) {
    while ($row = $result_all_tickets->fetch_assoc()) {
        $all_tickets[] = $row;
    }
} else {
    $error_message = "Error al obtener el listado de tickets: " . $conn->error;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administrador - Sistema de Tickets</title>
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #28a745;
            --tertiary-color: #6c757d;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #2c3e50;
            --border-color: #e0e0e0;
            --shadow-color: rgba(0,0,0,0.07);
        }
        
        .container-main {
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 6px 18px var(--shadow-color);
        }
        
        .edit-form-container {
            margin-top: 30px;
            padding: 25px;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .info-original { 
            background-color: #f0f7ff;
            padding: 15px; 
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }
        
        .info-original h4 {
            color: var(--primary-color);
            margin-top: 0;
            margin-bottom: 12px;
            font-size: 1.1em;
        }
        
        .info-original p { 
            margin: 8px 0;
            line-height: 1.5;
        }
        
        /* Estilos para la tabla de tickets */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            border-radius: 8px;
            overflow: hidden;
        }
        
        thead {
            background-color: #f8f9fa;
        }
        
        th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
            font-size: 0.95em;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
        }
        
        td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--border-color);
            font-size: 0.95em;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        tr:hover {
            background-color: #f8f9fa;
        }
        
        /* Botones y badges para estados */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 5px;
            padding: 8px 15px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            color: white;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #0052a3;
        }
        
        .btn-success {
            background-color: var(--secondary-color);
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-info {
            background-color: #17a2b8;
        }
        
        .btn-info:hover {
            background-color: #138496;
        }
        
        .btn-warning {
            background-color: var(--warning-color);
            color: #212529;
        }
        
        .btn-warning:hover {
            background-color: #e0a800;
        }
        
        .btn-danger {
            background-color: var(--danger-color);
        }
        
        .btn-danger:hover {
            background-color: #bd2130;
        }
        
        .btn-secondary {
            background-color: var(--tertiary-color);
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-sm {
            padding: 6px 10px;
            font-size: 0.85em;
        }
        
        .estado-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        
        .estado-abierto {
            background-color: #e9ecef;
            color: #495057;
        }
        
        .estado-en-progreso {
            background-color: #cce5ff;
            color: #004085;
        }
        
        .estado-esperando {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .estado-resuelto {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .estado-cerrado {
            background-color: #cfe2ff;
            color: #0a58ca;
        }
        
        .prioridad-alta, .prioridad-muy-grave {
            background-color: #f8d7da;
            color: #721c24;
            font-weight: bold;
        }
        
        .prioridad-media, .prioridad-grave {
            background-color: #fff3cd;
            color: #856404;
        }
        
        .prioridad-baja, .prioridad-leve {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        /* Filtros de búsqueda */
        .filtros-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .filtro-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .filtro-label {
            font-weight: 600;
            font-size: 0.9em;
            color: var(--dark-color);
        }
        
        .filtro-select {
            padding: 6px 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: white;
        }
        
        /* Mensajes de alerta */
        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left-color: #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border-left-color: #f5c6cb;
        }
        
        /* Encabezados de sección */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        
        .section-title {
            font-size: 1.5em;
            color: var(--primary-color);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-title i {
            color: var(--primary-color);
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        /* Responsive */
        @media (max-width: 992px) {
            .container-main {
                padding: 15px;
            }
            
            td, th {
                padding: 10px;
            }
            
            .btn {
                padding: 6px 10px;
            }
        }
        
        @media (max-width: 768px) {
            table {
                display: block;
                overflow-x: auto;
            }
            
            .filtros-container {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .section-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .action-buttons {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <div class="section-header">
            <h1 class="section-title"><i class="fas fa-tachometer-alt"></i> Panel de Administración de Tickets</h1>
            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn btn-primary"><i class="fas fa-home"></i> Inicio</a>
                <a href="<?php echo BASE_URL; ?>admin/logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
            </div>
        </div>

        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <strong>Error:</strong> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>        <?php if ($edit_ticket_id && $current_ticket): ?>
            <div class="edit-form-container">
                <div class="section-header">
                    <h2 class="section-title">
                        <i class="fas fa-edit"></i> Editando Ticket: #<?php echo htmlspecialchars($current_ticket['id']); ?>
                        <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $current_ticket['estado'])); ?>">
                            <?php echo htmlspecialchars($current_ticket['estado']); ?>
                        </span>
                    </h2>
                </div>
                
                <div class="info-original">
                    <h4>Información Original del Ticket</h4>
                    <p><strong>Asunto Original (si aplica):</strong> <?php echo htmlspecialchars($current_ticket['descripcion_breve'] ?? 'No especificado'); ?></p>
                    <p><strong>Descripción Original:</strong> <?php echo nl2br(htmlspecialchars($current_ticket['detalle_fallo'] ?? $current_ticket['descripcion'] ?? 'No especificado')); ?></p>
                </div>

                <form action="<?php echo BASE_URL; ?>admin/index.php?edit_id=<?php echo htmlspecialchars($edit_ticket_id); ?>" method="POST">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($current_ticket['id']); ?>">
                    
                    <div class="form-group">
                        <label for="identificacion_tipo">Tipo de Avería (Admin):</label>
                        <select id="identificacion_tipo" name="identificacion_tipo" required>
                            <option value="" <?php echo empty($current_ticket['identificacion_tipo']) ? 'selected' : ''; ?>>Seleccione un tipo</option>
                            <option value="Software" <?php echo (($current_ticket['identificacion_tipo'] ?? '') == 'Software') ? 'selected' : ''; ?>>Software</option>
                            <option value="Hardware" <?php echo (($current_ticket['identificacion_tipo'] ?? '') == 'Hardware') ? 'selected' : ''; ?>>Hardware</option>
                            <option value="Otro" <?php echo (($current_ticket['identificacion_tipo'] ?? '') == 'Otro') ? 'selected' : ''; ?>>Otro (Especificar en diagnóstico)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="prioridad">Clasificación/Prioridad:</label>
                        <select id="prioridad" name="prioridad">
                            <option value="">Seleccione...</option>
                            <optgroup label="Problema">
                                <option value="Muy Grave" <?php echo ($current_ticket['prioridad'] == 'Muy Grave') ? 'selected' : ''; ?>>Muy Grave</option>
                                <option value="Grave" <?php echo ($current_ticket['prioridad'] == 'Grave') ? 'selected' : ''; ?>>Grave</option>
                                <option value="Leve" <?php echo ($current_ticket['prioridad'] == 'Leve') ? 'selected' : ''; ?>>Leve</option>
                            </optgroup>
                            <optgroup label="Incidente">
                                <option value="Alto" <?php echo ($current_ticket['prioridad'] == 'Alto') ? 'selected' : ''; ?>>Alto</option>
                                <option value="Medio" <?php echo ($current_ticket['prioridad'] == 'Medio') ? 'selected' : ''; ?>>Medio</option>
                                <option value="Bajo" <?php echo ($current_ticket['prioridad'] == 'Bajo') ? 'selected' : ''; ?>>Bajo</option>
                            </optgroup>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="diagnostico">Diagnóstico:</label>
                        <textarea id="diagnostico" name="diagnostico" rows="4"><?php echo htmlspecialchars($current_ticket['diagnostico']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="cierre_solucion">Cierre (Solución Aplicada):</label>
                        <textarea id="cierre_solucion" name="cierre_solucion" rows="4"><?php echo htmlspecialchars($current_ticket['cierre_solucion']); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="estado">Estado del Ticket:</label>
                        <select id="estado" name="estado" required>
                            <option value="Abierto" <?php echo ($current_ticket['estado'] == 'Abierto') ? 'selected' : ''; ?>>Abierto</option>
                            <option value="En Progreso" <?php echo ($current_ticket['estado'] == 'En Progreso') ? 'selected' : ''; ?>>En Progreso</option>
                            <option value="Esperando Respuesta" <?php echo ($current_ticket['estado'] == 'Esperando Respuesta') ? 'selected' : ''; ?>>Esperando Respuesta del Usuario</option>
                            <option value="Resuelto" <?php echo ($current_ticket['estado'] == 'Resuelto') ? 'selected' : ''; ?>>Resuelto</option>
                            <option value="Cerrado" <?php echo ($current_ticket['estado'] == 'Cerrado') ? 'selected' : ''; ?>>Cerrado</option>
                        </select>
                    </div>                    <div class="action-buttons" style="margin-top: 20px; display: flex; gap: 10px;">
                        <button type="submit" name="update_ticket" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Ticket
                        </button>
                        <a href="<?php echo BASE_URL; ?>admin/index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar Edición
                        </a>
                        <?php if ($current_ticket['estado'] == 'Resuelto' || $current_ticket['estado'] == 'Cerrado'): ?>
                        <a href="<?php echo BASE_URL; ?>reports/generar_informe_v2.php?ticket_id=<?php echo htmlspecialchars($current_ticket['id']); ?>" target="_blank" class="btn btn-info">
                            <i class="fas fa-file-alt"></i> Informe Detallado
                        </a>
                        <a href="<?php echo BASE_URL; ?>reports/imprimir_informe.php?ticket_id=<?php echo htmlspecialchars($current_ticket['id']); ?>" target="_blank" class="btn btn-success">
                            <i class="fas fa-print"></i> Imprimir Directo
                        </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        <?php endif; ?>        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-list-alt"></i> Listado de Tickets</h2>
        </div>
        
        <!-- Filtros de búsqueda -->
        <div class="filtros-container">
            <div class="filtro-item">
                <span class="filtro-label">Estado:</span>
                <select class="filtro-select" id="filtro-estado">
                    <option value="">Todos</option>
                    <option value="Abierto">Abierto</option>
                    <option value="En Progreso">En Progreso</option>
                    <option value="Esperando Respuesta">Esperando Respuesta</option>
                    <option value="Resuelto">Resuelto</option>
                    <option value="Cerrado">Cerrado</option>
                </select>
            </div>
            <div class="filtro-item">
                <span class="filtro-label">Prioridad:</span>
                <select class="filtro-select" id="filtro-prioridad">
                    <option value="">Todas</option>
                    <option value="Muy Grave">Muy Grave</option>
                    <option value="Grave">Grave</option>
                    <option value="Leve">Leve</option>
                    <option value="Alto">Alto</option>
                    <option value="Medio">Medio</option>
                    <option value="Bajo">Bajo</option>
                </select>
            </div>
            <div class="filtro-item">
                <button class="btn btn-primary btn-sm" id="btn-aplicar-filtros">
                    <i class="fas fa-filter"></i> Aplicar Filtros
                </button>
                <button class="btn btn-secondary btn-sm" id="btn-limpiar-filtros">
                    <i class="fas fa-undo"></i> Limpiar
                </button>
            </div>
        </div>
        
        <table id="tabla-tickets">
            <thead>
                <tr>
                    <th>N° Ticket</th>
                    <th>Fecha Creación</th>
                    <th>Departamento</th>
                    <th>Asunto</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($all_tickets) > 0): ?>
                    <?php foreach ($all_tickets as $ticket): ?>                    <tr class="fila-ticket" 
                        data-estado="<?php echo htmlspecialchars($ticket['estado']); ?>" 
                        data-prioridad="<?php echo htmlspecialchars($ticket['prioridad']); ?>">
                        <td>#<?php echo htmlspecialchars($ticket['id']); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket['fecha_creacion']))); ?></td>
                        <td><?php echo htmlspecialchars($ticket['nombre_departamento'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['asunto']); ?></td>
                        <td>
                            <span class="estado-badge estado-<?php echo strtolower(str_replace(' ', '-', $ticket['estado'])); ?>">
                                <?php echo htmlspecialchars($ticket['estado']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="estado-badge prioridad-<?php echo strtolower(str_replace(' ', '-', $ticket['prioridad'])); ?>">
                                <?php echo htmlspecialchars($ticket['prioridad'] ?: 'N/A'); ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="<?php echo BASE_URL; ?>admin/index.php?edit_id=<?php echo htmlspecialchars($ticket['id']); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-edit"></i> Editar
                                </a>
                                
                                <?php if ($ticket['estado'] == 'Resuelto' || $ticket['estado'] == 'Cerrado'): ?>
                                <div class="dropdown-action">
                                    <a href="<?php echo BASE_URL; ?>reports/generar_informe_v2.php?ticket_id=<?php echo htmlspecialchars($ticket['id']); ?>" target="_blank" class="btn btn-info btn-sm">
                                        <i class="fas fa-file-alt"></i> Informe
                                    </a>
                                    <a href="<?php echo BASE_URL; ?>reports/imprimir_informe.php?ticket_id=<?php echo htmlspecialchars($ticket['id']); ?>" target="_blank" class="btn btn-success btn-sm">
                                        <i class="fas fa-print"></i>
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align:center;">No hay tickets para mostrar.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>    <script src="assets/js/script.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Funcionalidad para filtrar tickets
            const btnAplicarFiltros = document.getElementById('btn-aplicar-filtros');
            const btnLimpiarFiltros = document.getElementById('btn-limpiar-filtros');
            const filtroEstado = document.getElementById('filtro-estado');
            const filtroPrioridad = document.getElementById('filtro-prioridad');
            const filasTickets = document.querySelectorAll('.fila-ticket');
            
            // Aplicar filtros
            btnAplicarFiltros.addEventListener('click', function() {
                const estadoSeleccionado = filtroEstado.value;
                const prioridadSeleccionada = filtroPrioridad.value;
                
                filasTickets.forEach(function(fila) {
                    const estadoFila = fila.getAttribute('data-estado');
                    const prioridadFila = fila.getAttribute('data-prioridad');
                    let mostrar = true;
                    
                    if (estadoSeleccionado && estadoFila !== estadoSeleccionado) {
                        mostrar = false;
                    }
                    
                    if (prioridadSeleccionada && prioridadFila !== prioridadSeleccionada) {
                        mostrar = false;
                    }
                    
                    fila.style.display = mostrar ? '' : 'none';
                });
            });
            
            // Limpiar filtros
            btnLimpiarFiltros.addEventListener('click', function() {
                filtroEstado.value = '';
                filtroPrioridad.value = '';
                
                filasTickets.forEach(function(fila) {
                    fila.style.display = '';
                });
            });
        });
    </script>
</body>
</html>
