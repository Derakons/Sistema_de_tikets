<?php
require_once 'includes/config.php'; // Ajuste de ruta
require_once 'includes/functions.php'; // Ajuste de ruta
$page_title = "Crear Nuevo Ticket"; // Definir título específico para esta página
require_once 'includes/templates/header.php'; 


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
                 LEFT JOIN departamentos d ON t.departamento_id = d.id 
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
$sql_all_tickets = "SELECT t.id, t.fecha_creacion, d.nombre_departamento, t.asunto, t.estado, t.prioridad 
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
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Ruta correcta si admin.php está en la raíz -->
    <style>
        .edit-form-container {
            margin-top: 30px;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .info-original { background-color: #eef; padding: 10px; border-radius: 4px; margin-bottom:15px;}
        .info-original p { margin: 5px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Panel de Administración de Tickets</h1>
        <!-- <p><a href="logout_admin.php">Cerrar Sesión</a></p> Descomentar cuando el login esté implementado -->

        <?php if (!empty($success_message)): ?>
            <div class="ticket-status" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb; padding: 10px; margin-bottom:15px; border-radius: 5px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="ticket-status" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 10px; margin-bottom:15px; border-radius: 5px;">
                <p><strong>Error:</strong> <?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($edit_ticket_id && $current_ticket): ?>
            <div class="edit-form-container">
                <h2>Editando Ticket: <?php echo htmlspecialchars($current_ticket['id']); ?></h2>
                
                <div class="info-original">
                    <h4>Información Original del Usuario:</h4>
                    <p><strong>Fecha Creación:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($current_ticket['fecha_creacion']))); ?></p>
                    <p><strong>Departamento:</strong> <?php echo htmlspecialchars($current_ticket['nombre_departamento']); ?></p>
                    <p><strong>Solicitante:</strong> <?php echo htmlspecialchars($current_ticket['nombre_solicitante'] ?: 'N/A'); ?></p>
                    <p><strong>Contacto:</strong> <?php echo htmlspecialchars($current_ticket['contacto_solicitante'] ?: 'N/A'); ?></p>
                    <p><strong>Breve Descripción:</strong> <?php echo htmlspecialchars($current_ticket['descripcion_breve']); ?></p>
                    <p><strong>Detalle del Fallo:</strong><br><pre><?php echo htmlspecialchars($current_ticket['detalle_fallo']); ?></pre></p>
                </div>

                <form action="admin.php?edit_id=<?php echo htmlspecialchars($edit_ticket_id); ?>" method="POST">
                    <input type="hidden" name="ticket_id" value="<?php echo htmlspecialchars($current_ticket['id']); ?>">
                    
                    <div class="form-group">
                        <label for="identificacion_tipo">Identificación:</label>
                        <select id="identificacion_tipo" name="identificacion_tipo">
                            <option value="">Seleccione...</option>
                            <option value="Problema" <?php echo ($current_ticket['identificacion_tipo'] == 'Problema') ? 'selected' : ''; ?>>Problema</option>
                            <option value="Incidente" <?php echo ($current_ticket['identificacion_tipo'] == 'Incidente') ? 'selected' : ''; ?>>Incidente</option>
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
                    </div>

                    <button type="submit" name="update_ticket" class="btn">Actualizar Ticket</button>
                    <a href="admin.php" class="btn" style="background-color: #6c757d;">Cancelar Edición</a>
                    <?php if ($current_ticket['estado'] == 'Resuelto' || $current_ticket['estado'] == 'Cerrado'): ?>
                        <a href="generar_informe.php?ticket_id=<?php echo htmlspecialchars($current_ticket['id']); ?>" target="_blank" class="btn" style="background-color: #17a2b8;">Generar Informe</a>
                    <?php endif; ?>
                </form>
            </div>
        <?php endif; ?>

        <h2>Listado de Tickets</h2>
        <!-- Aquí podrías agregar filtros si lo deseas -->
        <table>
            <thead>
                <tr>
                    <th>N° Ticket</th>
                    <th>Fecha Creación</th>
                    <th>Departamento</th>
                    <th>Breve Descripción</th>
                    <th>Estado</th>
                    <th>Prioridad</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($all_tickets) > 0): ?>
                    <?php foreach ($all_tickets as $ticket): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($ticket['id']); ?></td>
                        <td><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket['fecha_creacion']))); ?></td>
                        <td><?php echo htmlspecialchars($ticket['nombre_departamento'] ?: 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($ticket['asunto']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['estado']); ?></td>
                        <td><?php echo htmlspecialchars($ticket['prioridad'] ?: 'N/A'); ?></td>
                        <td>
                            <a href="admin.php?edit_id=<?php echo htmlspecialchars($ticket['id']); ?>" class="btn" style="font-size:0.9em; padding:5px 10px;">Ver/Editar</a>
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
    </div>
    <script src="assets/js/script.js"></script> <!-- Ruta correcta si admin.php está en la raíz -->
</body>
</html>
