<?php
$page_title = "Resumen de Notificación";
require_once 'includes/templates/header.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

$ticket_id = isset($_GET['ticket_id']) ? limpiar_datos($_GET['ticket_id']) : null;
$ticket_data = null;
$error_message = '';

if ($ticket_id) {
    $sql = "SELECT t.*, d.nombre_departamento, ua.nombre_completo as tecnico_asignado
            FROM tickets t
            LEFT JOIN departamentos d ON t.id_departamento = d.id
            LEFT JOIN usuarios_admin ua ON t.id_admin_asignado = ua.id
            WHERE t.id = ? AND (t.estado = 'Resuelto' OR t.estado = 'Cerrado')";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $ticket_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $ticket_data = $result->fetch_assoc();
        } else {
            $error_message = "El ticket no se encontró o no está en estado Resuelto/Cerrado.";
        }
        $stmt->close();
    } else {
        $error_message = "Error al preparar la consulta para generar el informe: " . $conn->error;
    }
} else {
    $error_message = "No se proporcionó un ID de ticket válido.";
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen de Notificación - <?php echo htmlspecialchars($ticket_id ?: ''); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { background: #fff; color: #222; }
        .notificacion-resumida {
            max-width: 600px;
            margin: 40px auto;
            background: #f8f9fa;
            border: 1px solid #ccc;
            border-radius: 10px;
            padding: 32px 24px;
            font-size: 1.08em;
            font-family: 'Roboto', Arial, sans-serif;
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
        }
        .notificacion-resumida pre {
            background: none;
            border: none;
            padding: 0;
            font-size: 1em;
            white-space: pre-wrap;
            word-break: break-word;
        }
        .notificacion-resumida h2 {
            font-family: 'Orbitron', Arial, sans-serif;
            color: #e4342c;
            font-size: 1.3em;
            margin-bottom: 18px;
        }
    </style>
</head>
<body>
    <div class="notificacion-resumida">
        <?php if ($ticket_data): ?>
            <h2>Resumen para Notificación por Correo</h2>
            <pre><?php echo htmlspecialchars(generar_informe_notificacion($ticket_data)); ?></pre>
        <?php else: ?>
            <p style="color:red; text-align:center;"><?php echo $error_message ?: "No se pudo cargar la información del ticket."; ?></p>
        <?php endif; ?>
    </div>
    <?php require_once 'includes/templates/footer.php'; ?>
</body>
</html>
