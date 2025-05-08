<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

$ticket_info = null;
$error_message = '';
$numero_ticket_consultado = '';

if (isset($_GET['numero_ticket'])) {
    $numero_ticket_consultado = limpiar_datos($_GET['numero_ticket']);

    if (!empty($numero_ticket_consultado)) {
        $sql = "SELECT t.*, d.nombre_departamento 
                FROM tickets t 
                LEFT JOIN departamentos d ON t.departamento_id = d.id 
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
        // No se muestra error si solo se carga la página sin un número de ticket
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seguimiento de Ticket - Municipalidad de Canchis</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php
    $page_title = "Seguimiento de Ticket";
    require_once 'includes/templates/header.php';
    ?>
    <div class="container">
        <h1>Seguimiento de Ticket</h1>
        <form action="seguimiento.php" method="GET">
            <div class="form-group">
                <label for="numero_ticket">Número de Ticket:</label>
                <input type="text" id="numero_ticket" name="numero_ticket" value="<?php echo htmlspecialchars($numero_ticket_consultado); ?>" required>
            </div>
            <button type="submit" class="btn">Consultar Estado</button>
        </form>

        <?php if (!empty($error_message)): ?>
            <div class="ticket-status" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px; border-radius: 5px; margin-top: 20px;">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <?php if ($ticket_info): ?>
            <div class="ticket-status">
                <h2>Estado del Ticket: <?php echo htmlspecialchars($ticket_info['id']); ?></h2>
                <p><strong>Fecha de Creación:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_creacion']))); ?></p>
                <p><strong>Departamento:</strong> <?php echo htmlspecialchars($ticket_info['nombre_departamento'] ?: 'No especificado'); ?></p>
                <p><strong>Breve Descripción:</strong> <?php echo htmlspecialchars($ticket_info['descripcion_breve']); ?></p>
                <p><strong>Detalle del Fallo:</strong></p>
                <p><pre><?php echo htmlspecialchars($ticket_info['detalle_fallo']); ?></pre></p>
                <p><strong>Estado Actual:</strong> <span style="font-weight:bold; color: <?php 
                    switch($ticket_info['estado']) {
                        case 'Abierto': echo '#007bff'; break;
                        case 'En Progreso': echo '#ffc107'; break;
                        case 'Resuelto': echo '#28a745'; break;
                        case 'Cerrado': echo '#6c757d'; break;
                        case 'Esperando Respuesta': echo '#17a2b8'; break;
                        default: echo '#333';
                    } ?>;"><?php echo htmlspecialchars($ticket_info['estado']); ?></span></p>
                
                <?php if ($ticket_info['fecha_actualizacion_admin']): ?>
                <p><strong>Última Actualización del Administrador:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_actualizacion_admin']))); ?></p>
                <?php endif; ?>

                <?php if ($ticket_info['estado'] == 'Resuelto' || $ticket_info['estado'] == 'Cerrado'): ?>
                    <?php if ($ticket_info['diagnostico']): ?>
                    <p><strong>Diagnóstico del Administrador:</strong></p>
                    <p><pre><?php echo htmlspecialchars($ticket_info['diagnostico']); ?></pre></p>
                    <?php endif; ?>
                    <?php if ($ticket_info['cierre_solucion']): ?>
                    <p><strong>Solución Aplicada:</strong></p>
                    <p><pre><?php echo htmlspecialchars($ticket_info['cierre_solucion']); ?></pre></p>
                    <?php endif; ?>
                     <?php if ($ticket_info['fecha_cierre']): ?>
                    <p><strong>Fecha de Cierre/Resolución:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_cierre']))); ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <br>
        <a href="index.php" class="btn">Crear Nuevo Ticket</a>

    </div>
    <script src="assets/js/script.js"></script>
    <?php require_once 'includes/templates/footer.php'; ?>
</body>
</html>
