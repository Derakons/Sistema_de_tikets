<?php
$page_title = "Guardar Ticket";
require_once '../core/templates/header.php';
require_once '../core/config.php';
require_once '../core/functions.php';

$detalle_fallo = '';
$descripcion_breve = '';
$departamento_id = '';
$nombre_solicitante = '';
$contacto_solicitante = '';
$ticket_id_generado = null;
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y obtener datos del formulario
    $detalle_fallo = limpiar_datos($_POST['detalle_fallo']);
    $descripcion_breve = limpiar_datos($_POST['descripcion_breve']);
    $departamento_id = limpiar_datos($_POST['departamento']); // Ahora es el ID
    $nombre_solicitante = isset($_POST['nombre_solicitante']) ? limpiar_datos($_POST['nombre_solicitante']) : null;
    $contacto_solicitante = isset($_POST['contacto_solicitante']) ? limpiar_datos($_POST['contacto_solicitante']) : null;

    // Validaciones básicas (puedes agregar más)
    if (empty($detalle_fallo) || empty($descripcion_breve) || empty($departamento_id)) {
        $error_message = "Por favor, complete todos los campos obligatorios.";
    } else {
        $ticket_id_generado = generar_numero_ticket($conn); // Usar la función para el ID
        $fecha_creacion = date('Y-m-d H:i:s');
        $estado_inicial = 'Abierto';

        $sql = "INSERT INTO tickets (id, fecha_creacion, detalle_fallo, descripcion_breve, id_departamento, nombre_solicitante, contacto_solicitante, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ssssisss", $ticket_id_generado, $fecha_creacion, $detalle_fallo, $descripcion_breve, $departamento_id, $nombre_solicitante, $contacto_solicitante, $estado_inicial);
            
            if ($stmt->execute()) {
                $success_message = "Ticket creado exitosamente. Su número de ticket es: <strong>" . htmlspecialchars($ticket_id_generado) . "</strong>. Por favor, guárdelo para futuras consultas.";
            } else {
                $error_message = "Error al crear el ticket: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resultado del Envío - Sistema de Tickets</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Resultado del Envío de Ticket</h1>
        <?php if (!empty($success_message)): ?>
            <div class="ticket-status" style="background-color: #d4edda; color: #155724; border-color: #c3e6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="ticket-status" style="background-color: #f8d7da; color: #721c24; border-color: #f5c6cb; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <p><strong>Error:</strong> <?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <p><a href="index.php" class="btn">Crear Otro Ticket</a></p>
        <p><a href="seguimiento.php<?php echo $ticket_id_generado ? '?numero_ticket=' . htmlspecialchars($ticket_id_generado) : ''; ?>" class="btn">Hacer Seguimiento de este Ticket</a></p>
    </div>
    <?php require_once '../core/templates/footer.php'; ?>
</body>
</html>
