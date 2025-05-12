<?php
/**
 * imprimir_informe.php - Versión especializada para imprimir informes de tickets
 * 
 * Esta versión está diseñada específicamente para generar una impresión limpia
 * sin elementos visuales innecesarios, optimizada para impresión en papel o PDF
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

// Verificar si el usuario administrador está logueado
if (function_exists('proteger_pagina_admin')) {
    proteger_pagina_admin();
}

// Definición de emergencia para formatearFecha si no está en functions.php
if (!function_exists('formatearFecha')) {
    function formatearFecha($fechaOriginal, $formato = 'd/m/Y H:i:s') {
        if (empty($fechaOriginal)) {
            return 'N/A';
        }
        try {
            $fechaObj = new DateTime($fechaOriginal);
            return $fechaObj->format($formato);
        } catch (Exception $e) {
            return $fechaOriginal; 
        }
    }
}

// Procesar el ticket
$ticket_id = isset($_GET['ticket_id']) ? limpiar_datos($_GET['ticket_id']) : null;
$ticket_data = null;
$error_message = '';

if ($ticket_id) {
    // Query para obtener datos del ticket
    $sql = "SELECT 
                t.id, t.asunto, t.descripcion, t.cierre_solucion, t.estado, t.prioridad,
                t.nombre_solicitante, t.dni_solicitante, t.email_solicitante, t.telefono_solicitante,
                t.fecha_creacion, t.fecha_cierre, t.fecha_resolucion, t.ultima_actualizacion,
                t.notas_internas, t.archivo_adjunto, t.nombre_archivo_original, t.ip_solicitante,
                d.nombre_departamento, 
                ua.nombre_completo as tecnico_asignado, ua.email as email_tecnico
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

// Inicializar variables para el informe
$report_content = "";

if ($ticket_data) {
    // Información del solicitante
    $nombre_solicitante = $ticket_data['nombre_solicitante'] ?? 'N/A';
    $dni_solicitante = $ticket_data['dni_solicitante'] ?? 'N/A';
    $correo_solicitante = $ticket_data['email_solicitante'] ?? 'N/A';
    $telefono_solicitante = $ticket_data['telefono_solicitante'] ?? '';

    // Información del ticket
    $id_ticket = $ticket_data['id'] ?? 'N/A';
    $asunto_ticket = $ticket_data['asunto'] ?? 'N/A';
    $detalle_reportado = $ticket_data['descripcion'] ?? 'N/A'; 
    $solucion_aplicada = $ticket_data['cierre_solucion'] ?? 'N/A';
    $prioridad_ticket = $ticket_data['prioridad'] ?? 'Media';

    // Información del departamento
    $nombre_departamento = $ticket_data['nombre_departamento'] ?? 'N/A';
    
    // Información del estado
    $estado_ticket = $ticket_data['estado'] ?? 'N/A';
    
    // Información de fechas
    $fecha_creacion_ticket = isset($ticket_data['fecha_creacion']) ? formatearFecha($ticket_data['fecha_creacion']) : 'N/A';
    $fecha_cierre_ticket = 'N/A';
    if (!empty($ticket_data['fecha_cierre'])) {
        $fecha_cierre_ticket = formatearFecha($ticket_data['fecha_cierre']);
    } elseif (!empty($ticket_data['fecha_resolucion'])) {
        $fecha_cierre_ticket = formatearFecha($ticket_data['fecha_resolucion']);
    }

    // Información del técnico
    $tecnico_asignado = $ticket_data['tecnico_asignado'] ?? 'N/A';
    $email_tecnico = $ticket_data['email_tecnico'] ?? 'N/A';

    // Información de archivos adjuntos
    $tiene_adjunto = !empty($ticket_data['archivo_adjunto']);
    $nombre_archivo = $ticket_data['nombre_archivo_original'] ?? 'N/A';
    
    // Cálculo del tiempo de resolución
    $tiempo_resolucion = 'N/A';
    if (!empty($ticket_data['fecha_creacion']) && (!empty($ticket_data['fecha_cierre']) || !empty($ticket_data['fecha_resolucion']))) {
        try {
            $fecha_inicio = new DateTime($ticket_data['fecha_creacion']);
            $fecha_fin = !empty($ticket_data['fecha_cierre']) ? 
                          new DateTime($ticket_data['fecha_cierre']) : 
                          new DateTime($ticket_data['fecha_resolucion']);
            
            $intervalo = $fecha_inicio->diff($fecha_fin);
            
            // Formatear para presentación
            if ($intervalo->y > 0) {
                $tiempo_resolucion = $intervalo->format('%y año(s), %m mes(es), %d día(s)');
            } elseif ($intervalo->m > 0) {
                $tiempo_resolucion = $intervalo->format('%m mes(es), %d día(s)');
            } elseif ($intervalo->d > 0) {
                $tiempo_resolucion = $intervalo->format('%d día(s), %h hora(s)');
            } else {
                $tiempo_resolucion = $intervalo->format('%h hora(s), %i minuto(s)');
            }
        } catch (Exception $e) {
            $tiempo_resolucion = 'Error de cálculo';
        }
    }

    // Formato para fecha actual en el pie de página del informe
    $fecha_actual_formateada = date("d/m/Y");
    $hora_actual_formateada = date("H:i:s");
    
    // Título personalizado según el estado del ticket
    $titulo_informe = "NOTIFICACIÓN DE " . ($estado_ticket == "Resuelto" ? "RESOLUCIÓN" : "CIERRE") . " DE TICKET";
    
    // Construir el contenido del informe 
    $report_content = $titulo_informe . "\n\n";
    $report_content .= "Estimado(a) " . $nombre_solicitante . ",\n\n";
    $report_content .= "Le informamos que su ticket N° " . $id_ticket . " ha sido " . strtolower($estado_ticket) . " en nuestro sistema.\n\n";    $report_content .= "A continuación, el detalle del servicio:\n\n";
    $report_content .= "N° Ticket:         " . $id_ticket . "\n";
    $report_content .= "Solicitante:       " . $nombre_solicitante . " (DNI: " . $dni_solicitante . ")\n";
    $report_content .= "Departamento:      " . $nombre_departamento . "\n";
    $report_content .= "Asunto:            " . $asunto_ticket . "\n";
    $report_content .= "Prioridad:         " . $prioridad_ticket . "\n\n";    $report_content .= "DETALLE REPORTADO:\n" . $detalle_reportado . "\n\n";
    $report_content .= "SOLUCIÓN APLICADA:\n" . $solucion_aplicada . "\n\n";
    $report_content .= "Estado Final:      " . $estado_ticket . "\n";
    $report_content .= "Fecha de Creación: " . $fecha_creacion_ticket . "\n";
    $report_content .= "Fecha de Cierre:   " . $fecha_cierre_ticket . "\n";
    $report_content .= "Tiempo Resolución: " . $tiempo_resolucion . "\n";
    $report_content .= "Técnico Asignado:  " . $tecnico_asignado . "\n";
    $report_content .= "Correo Registrado: " . $correo_solicitante . "\n";
    if (!empty($telefono_solicitante)) {
        $report_content .= "Teléfono:          " . $telefono_solicitante . "\n";
    }    if ($tiene_adjunto) {
        $report_content .= "Archivo Adjunto:   " . $nombre_archivo . " (Adjuntado al crear el ticket)\n";
    }
    
    $report_content .= "\nAgradecemos su confianza en nuestro servicio.\n\n";
    $report_content .= "Si tiene alguna consulta adicional, no dude en contactarnos respondiendo a este mensaje o comunicándose con el área de TI al correo: " . $email_tecnico . "\n\n";    $report_content .= "Atentamente,\n";
    $report_content .= $tecnico_asignado . "\n";
    $report_content .= "Equipo de Soporte Técnico";
}

$conn->close();
?>
<?php 
// Verificar si se solicita el formato PDF
$formato_pdf = isset($_GET['format']) && $_GET['format'] === 'pdf';

// Establecer título según el formato solicitado - sin fecha ni hora
$titulo_pagina = $formato_pdf ? 
    "Informe PDF - Ticket #" . htmlspecialchars($ticket_id ?: 'Error') : 
    "Informe de Ticket #" . htmlspecialchars($ticket_id ?: 'Error');
?>
<!DOCTYPE html>
<html lang="es" class="print-clean">
<head>    
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="date" content="">
    <!-- Eliminar metadatos que pueden generar encabezados -->
    <meta name="creator" content="">
    <meta name="author" content="">
    <title><?php echo $titulo_pagina; ?></title>
    <link rel="stylesheet" href="assets/css/print-styles.css">
    <style>
        @page {
            size: A4;
            margin: 10mm;
            marks: none;
            margin-header: 0;
            margin-footer: 0;
            padding: 0;
        }
          body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
            margin: 0;
            padding: 10mm;
            white-space: pre-wrap;
            word-break: keep-all;
        }

        .error-message {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
            text-align: center;
        }

        .back-link {
            display: block;
            margin: 20px 0;
            text-align: center;
            color: #007bff;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }
        
        .pdf-instructions {
            background-color: #e9f5ff;
            border: 1px solid #b8daff;
            color: #004085;
            padding: 10px 15px;
            margin: 15px 0;
            border-radius: 4px;
            text-align: center;
            font-family: Arial, sans-serif;
            font-size: 14px;
        }
        
        .btn-print {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            display: inline-block;
        }
        
        .btn-print:hover {
            background-color: #0069d9;
        }

        @media print {
            .no-print {
                display: none !important;
            }
            
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
<?php if ($ticket_data && !$error_message): ?>
    <?php if ($formato_pdf): ?>
    <div class="pdf-instructions no-print">
        <h3>Generación de PDF para Ticket #<?php echo htmlspecialchars($ticket_id); ?></h3>
        <p>Para guardar este documento como PDF:</p>
        <ol>
            <li>Haga clic en "Imprimir" debajo o use Ctrl+P (Cmd+P en Mac)</li>
            <li>En el diálogo de impresión, seleccione "Guardar como PDF" como destino</li>
            <li>Haga clic en "Guardar" y seleccione la ubicación donde desea guardar el archivo</li>
        </ol>
        <button class="btn-print" onclick="window.print()">Imprimir / Guardar como PDF</button>
    </div>
    <?php endif; ?>    <div class="ticket-report">
        <?php echo $report_content; ?>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <a href="generar_informe.php?ticket_id=<?php echo htmlspecialchars($ticket_id); ?>" class="back-link">Volver a la versión completa</a>
    </div>
<?php else: ?>
    <div class="error-message">
        <?php echo !empty($error_message) ? htmlspecialchars($error_message) : "No se pudo cargar la información del ticket para el informe."; ?>
    </div>
    <a href="generar_informe.php" class="back-link no-print">Volver a la generación de informes</a>
<?php endif; ?>

<script>
    // Auto-imprimir cuando carga la página (solo si no es formato PDF)
    window.addEventListener('load', function() {
        <?php if (!$formato_pdf): ?>
        // Pequeño retraso para asegurar que todo cargue correctamente
        setTimeout(function() {
            window.print();
        }, 500);
        <?php endif; ?>
    });
</script>
</body>
</html>
