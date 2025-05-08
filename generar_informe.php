<?php
$page_title = "Generar Informe";
require_once 'includes/templates/header.php';
require_once 'includes/config.php';
require_once 'includes/functions.php';

// requireAdminLogin(); // Asegúrate de que solo los admins puedan acceder

$ticket_id = isset($_GET['ticket_id']) ? limpiar_datos($_GET['ticket_id']) : null;
$ticket_data = null;
$error_message = '';

if ($ticket_id) {
    $sql = "SELECT t.*, d.nombre_departamento, ua.nombre_completo as tecnico_asignado
            FROM tickets t
            LEFT JOIN departamentos d ON t.departamento_id = d.id
            LEFT JOIN usuarios_admin ua ON t.admin_id_asignado = ua.id
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
    <title>Informe de Ticket - <?php echo htmlspecialchars($ticket_id ?: ''); ?></title>
    <link rel="stylesheet" href="assets/css/style.css"> <!-- Asumiendo que generar_informe.php está en la raíz -->
    <style>
        body {
            background-color: #fff; /* Fondo blanco para impresión */
            color: #000;
        }
        .container {
            width: 90%; /* Más ancho para el informe */
            margin: 20px auto;
            box-shadow: none; /* Sin sombra para impresión */
            border: 1px solid #ccc; /* Un borde ligero opcional */
        }
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .report-header img {
            max-width: 150px; /* Ajusta según el logo de la municipalidad */
            margin-bottom: 10px;
        }
        .report-section {
            margin-bottom: 20px;
        }
        .report-section h3 {
            background-color: #f2f2f2;
            padding: 8px;
            margin-top: 0;
            border-bottom: 1px solid #ddd;
        }
        .report-section p {
            margin: 5px 0 10px 0;
            padding-left: 10px;
        }
        .report-section p strong {
            display: inline-block;
            width: 200px; /* Ajusta para alinear etiquetas */
            color: #333;
        }
        pre {
            background-color: #f9f9f9;
            padding: 10px;
            border: 1px dashed #ccc;
            white-space: pre-wrap; /* Para que el texto largo se ajuste */
            word-wrap: break-word;
        }
        .print-button {
            display: block;
            width: fit-content;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        @media print {
            body {
                font-size: 12pt;
            }
            .container {
                width: 100%;
                margin: 0;
                border: none;
            }
            .print-button, .no-print {
                display: none;
            }
            .report-header {
                border-bottom: 2px solid #000;
            }
            .report-section h3 {
                 background-color: #eee !important; /* Asegurar fondo en impresión */
                -webkit-print-color-adjust: exact; /* Para Chrome/Safari */
                print-color-adjust: exact; /* Estándar */
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if ($ticket_data): ?>
            <div class="report-header">
                <!-- <img src="assets/images/logo_municipalidad.png" alt="Logo Municipalidad"> Reemplazar con el logo real -->
                <h2>Informe de Resolución de Ticket TI</h2>
                <h3>Municipalidad Provincial de Canchis</h3>
            </div>

            <button class="print-button" onclick="window.print();">Imprimir Informe</button>

            <div class="report-section">
                <h3>Datos Generales del Ticket</h3>
                <p><strong>Número de Ticket:</strong> <?php echo htmlspecialchars($ticket_data['id']); ?></p>
                <p><strong>Fecha y Hora de Generación del Informe:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i:s")); ?></p>
            </div>

            <div class="report-section">
                <h3>Datos del Reporte Original</h3>
                <p><strong>Fecha y Hora de Creación del Ticket:</strong> <?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_data['fecha_creacion']))); ?></p>
                <p><strong>Departamento Solicitante:</strong> <?php echo htmlspecialchars($ticket_data['nombre_departamento'] ?: 'N/A'); ?></p>
                <p><strong>Nombre del Solicitante:</strong> <?php echo htmlspecialchars($ticket_data['nombre_solicitante'] ?: 'N/A'); ?></p>
                <p><strong>Contacto del Solicitante:</strong> <?php echo htmlspecialchars($ticket_data['contacto_solicitante'] ?: 'N/A'); ?></p>
                <p><strong>Breve Descripción (Asunto):</strong> <?php echo htmlspecialchars($ticket_data['descripcion_breve']); ?></p>
                <p><strong>Detalle del Fallo (Reportado por Usuario):</strong></p>
                <pre><?php echo htmlspecialchars($ticket_data['detalle_fallo']); ?></pre>
            </div>

            <div class="report-section">
                <h3>Datos de Gestión del Administrador</h3>
                <p><strong>Identificación:</strong> <?php echo htmlspecialchars($ticket_data['identificacion_tipo'] ?: 'No especificado'); ?></p>
                <p><strong>Clasificación/Prioridad:</strong> <?php echo htmlspecialchars($ticket_data['prioridad'] ?: 'No especificado'); ?></p>
                <p><strong>Diagnóstico Detallado:</strong></p>
                <pre><?php echo htmlspecialchars($ticket_data['diagnostico'] ?: 'No detallado'); ?></pre>
                <p><strong>Cierre (Solución Aplicada):</strong></p>
                <pre><?php echo htmlspecialchars($ticket_data['cierre_solucion'] ?: 'No detallada'); ?></pre>
                <p><strong>Estado Final del Ticket:</strong> <?php echo htmlspecialchars($ticket_data['estado']); ?></p>
                <p><strong>Fecha y Hora de Resolución/Cierre:</strong> <?php echo $ticket_data['fecha_cierre'] ? htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_data['fecha_cierre']))) : 'N/A'; ?></p>
                <p><strong>Técnico Asignado:</strong> <?php echo htmlspecialchars($ticket_data['tecnico_asignado'] ?: 'No asignado / Sistema'); // Asumiendo que tienes una tabla usuarios_admin y un campo nombre_completo ?>
                </p>
            </div>
            
            <div style="text-align: center; margin-top: 40px; font-size: 0.9em; color: #555;">
                <p>Informe generado automáticamente por el Sistema de Tickets de TI.</p>
            </div>

        <?php else: ?>
            <div class="report-header">
                <h2>Error al Generar Informe</h2>
            </div>
            <p style="text-align:center; color:red;"><?php echo $error_message ?: "No se pudo cargar la información del ticket para el informe."; ?></p>
            <p style="text-align:center;"><a href="admin.php" class="btn no-print">Volver al Panel de Administración</a></p>
        <?php endif; ?>
    </div>
    <?php require_once 'includes/templates/footer.php'; ?>
</body>
</html>
