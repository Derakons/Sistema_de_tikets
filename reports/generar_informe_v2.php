<?php
/**
 * generar_informe_v2.php - Versión mejorada para generar informes de tickets
 * Permite generar informes detallados en diferentes formatos
 */

$page_title = "Generar Informe - Versión 2";
require_once '../core/templates/header.php'; 
require_once '../core/config.php';
require_once '../core/functions.php';

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

// Función para generar PDF
function generarPDF($ticket_data, $report_content) {
    return [
        'success' => false,
        'message' => "Por favor, use la función de imprimir del navegador para generar un PDF."
    ];
}

// Procesar el ticket
$ticket_id = isset($_GET['ticket_id']) ? limpiar_datos($_GET['ticket_id']) : null;
$ticket_data = null;
$error_message = '';

if ($ticket_id) {
    // Query completa para obtener todos los datos relevantes del ticket
    $sql = "SELECT 
                t.id, t.descripcion_breve AS asunto, t.descripcion, t.cierre_solucion, t.estado, t.prioridad,
                t.identificacion_tipo, /* <--- Añadido este campo */
                t.nombre_solicitante, t.dni_solicitante, t.email_solicitante, t.telefono_solicitante,
                t.fecha_creacion, t.fecha_cierre, t.fecha_resolucion, t.ultima_actualizacion,
                t.notas_internas, t.archivo_adjunto, t.nombre_archivo_original, t.ip_solicitante,
                d.nombre_departamento, d.descripcion as departamento_descripcion, 
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

// Inicializar variables para la plantilla
$report_content = "";
$numero_whatsapp_input = "";
$report_email_content = ""; // Contenido para envío por correo (podría ser diferente al de WhatsApp)
$report_pdf_content = ""; // Contenido para PDF (podría incluir formato especial)

if ($ticket_data) {    // Información del solicitante
    $nombre_solicitante = htmlspecialchars($ticket_data['nombre_solicitante'] ?? 'N/A');
    $dni_solicitante = htmlspecialchars($ticket_data['dni_solicitante'] ?? 'N/A');
    $correo_solicitante = htmlspecialchars($ticket_data['email_solicitante'] ?? 'N/A');
    $telefono_solicitante = htmlspecialchars($ticket_data['telefono_solicitante'] ?? '');

    // Información del ticket
    $id_ticket = htmlspecialchars($ticket_data['id'] ?? 'N/A');
    $asunto_ticket = htmlspecialchars($ticket_data['asunto'] ?? 'N/A'); // Esto es descripcion_breve del usuario
    $tipo_averia_admin = htmlspecialchars($ticket_data['identificacion_tipo'] ?? 'No especificado'); // <-- Nueva variable
    $detalle_reportado = htmlspecialchars($ticket_data['descripcion'] ?? 'N/A'); 
    $solucion_aplicada = htmlspecialchars($ticket_data['cierre_solucion'] ?? 'N/A');
    $prioridad_ticket = htmlspecialchars($ticket_data['prioridad'] ?? 'Media');

    // Información del departamento
    $nombre_departamento = htmlspecialchars($ticket_data['nombre_departamento'] ?? 'N/A');
    $descripcion_departamento = htmlspecialchars($ticket_data['departamento_descripcion'] ?? '');
    
    // Información del estado
    $estado_ticket = htmlspecialchars($ticket_data['estado'] ?? 'N/A');
    
    // Información de fechas
    $fecha_creacion_ticket = isset($ticket_data['fecha_creacion']) ? formatearFecha($ticket_data['fecha_creacion']) : 'N/A';
    $fecha_cierre_ticket = 'N/A';
    if (!empty($ticket_data['fecha_cierre'])) {
        $fecha_cierre_ticket = formatearFecha($ticket_data['fecha_cierre']);
    } elseif (!empty($ticket_data['fecha_resolucion'])) {
        $fecha_cierre_ticket = formatearFecha($ticket_data['fecha_resolucion']);
    }
    $ultima_actualizacion = isset($ticket_data['ultima_actualizacion']) ? formatearFecha($ticket_data['ultima_actualizacion']) : 'N/A';

    // Información del técnico
    $tecnico_asignado = htmlspecialchars($ticket_data['tecnico_asignado'] ?? 'N/A');
    $email_tecnico = htmlspecialchars($ticket_data['email_tecnico'] ?? 'N/A');

    // Información de archivos adjuntos
    $tiene_adjunto = !empty($ticket_data['archivo_adjunto']);
    $ruta_adjunto = $ticket_data['archivo_adjunto'] ?? '';
    $nombre_archivo = htmlspecialchars($ticket_data['nombre_archivo_original'] ?? 'N/A');
    
    // Notas internas (solo mostrar si existen y si el usuario actual es administrador)
    $notas_internas = htmlspecialchars($ticket_data['notas_internas'] ?? '');
    
    // Tiempo de resolución (diferencia entre fecha de creación y cierre)
    $tiempo_resolucion = 'N/A';
    $dias_resolucion = 0;
    $horas_resolucion = 0;
    if (!empty($ticket_data['fecha_creacion']) && (!empty($ticket_data['fecha_cierre']) || !empty($ticket_data['fecha_resolucion']))) {
        try {
            $fecha_inicio = new DateTime($ticket_data['fecha_creacion']);
            $fecha_fin = !empty($ticket_data['fecha_cierre']) ? 
                          new DateTime($ticket_data['fecha_cierre']) : 
                          new DateTime($ticket_data['fecha_resolucion']);
            
            $intervalo = $fecha_inicio->diff($fecha_fin);
            
            // Calcular días y horas totales para análisis posteriores
            $dias_resolucion = $intervalo->days;
            $horas_resolucion = $intervalo->h + ($intervalo->days * 24);
            
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

    // Determinar eficiencia basada en prioridad y tiempo de resolución
    $eficiencia = 'Normal';
    if ($horas_resolucion > 0) {
        switch(strtolower($prioridad_ticket)) {
            case 'alta':
            case 'urgente':
                if ($horas_resolucion <= 4) $eficiencia = 'Excelente';
                else if ($horas_resolucion <= 12) $eficiencia = 'Buena';
                else if ($horas_resolucion <= 24) $eficiencia = 'Normal';
                else $eficiencia = 'Demorada';
                break;
            case 'media':
                if ($horas_resolucion <= 12) $eficiencia = 'Excelente';
                else if ($horas_resolucion <= 24) $eficiencia = 'Buena';
                else if ($horas_resolucion <= 48) $eficiencia = 'Normal';
                else $eficiencia = 'Demorada';
                break;
            case 'baja':
                if ($horas_resolucion <= 24) $eficiencia = 'Excelente';
                else if ($horas_resolucion <= 48) $eficiencia = 'Buena';
                else if ($horas_resolucion <= 72) $eficiencia = 'Normal';
                else $eficiencia = 'Demorada';
                break;
        }
    }

    // Priorizar número de WhatsApp: 1. Del ticket, 2. De GET, 3. Vacío
    $numero_whatsapp_input = $telefono_solicitante;
    if (isset($_GET['numero_whatsapp']) && !empty($_GET['numero_whatsapp'])) {
        $numero_whatsapp_input = htmlspecialchars($_GET['numero_whatsapp']);
    }

    // Formato para fecha actual en el pie de página del informe
    $fecha_actual_formateada = date("d/m/Y");
    $hora_actual_formateada = date("H:i:s");
    
    // Título personalizado según el estado del ticket
    $titulo_informe = "NOTIFICACIÓN DE " . ($estado_ticket == "Resuelto" ? "RESOLUCIÓN" : "CIERRE") . " DE TICKET";
      // --- NUEVO FORMATO DE INFORME PROFESIONAL UNIFICADO ---
    $usuario = $nombre_solicitante;
    $asunto = $asunto_ticket;
    $fecha_creacion_raw = $ticket_data['fecha_creacion'] ?? null;
    $fecha_cierre_raw = $ticket_data['fecha_cierre'] ?? $ticket_data['fecha_resolucion'] ?? null;
    $fecha_creacion = $fecha_creacion_raw ? formatearFecha($fecha_creacion_raw, 'd \d\e F \d\e Y \a \l\a\s H:i:s') : 'N/A';
    $fecha_cierre = $fecha_cierre_raw ? formatearFecha($fecha_cierre_raw, 'd \d\e F \d\e Y \a \l\a\s H:i:s') : 'N/A';
    $tiempo_resolucion = $tiempo_resolucion != 'N/A' ? $tiempo_resolucion : 'N/A';
    $departamento = $nombre_departamento;
    $tipo = $tipo_averia_admin;
    $prioridad = $prioridad_ticket;
    $estado_final = $estado_ticket;
    $solucion = $solucion_aplicada;
    $diagnostico = $ticket_data['diagnostico'] ?? '';
    $fecha_actual = date('d/m/Y H:i:s');

    // Determinar si es Problema o Incidente y su nivel
    $tipo_nivel = '';
    if (stripos($prioridad, 'grave') !== false || stripos($prioridad, 'leve') !== false) {
        $tipo_nivel = 'Problema (' . $prioridad . ')';
    } else if (stripos($prioridad, 'alto') !== false || stripos($prioridad, 'medio') !== false || stripos($prioridad, 'bajo') !== false) {
        $tipo_nivel = 'Incidente (' . $prioridad . ')';
    } else {
        $tipo_nivel = $prioridad;
    }

    $report_content = "Asunto: Notificación de Resolución: Ticket N° $id_ticket - $asunto\n";
    $report_content .= "El presente documento informa sobre la resolución del ticket N° $id_ticket, solicitado por el usuario $usuario y gestionado por el departamento de Soporte Técnico TI.\n";
    $report_content .= "La incidencia reportada por el usuario fue \"$asunto\", la cual fue clasificada como una avería de $tipo_nivel con una prioridad asignada de $prioridad.\n";
    $report_content .= "Para solucionar esta situación, el equipo técnico implementó una serie de medidas:\n";
    $report_content .= ($solucion ? $solucion . "\n" : "");
    $report_content .= "Como resultado de estas acciones, el estado final del ticket es $estado_final.\n";
    $report_content .= "El ticket fue generado el $fecha_creacion y fue cerrado el $fecha_cierre. El tiempo total empleado para la resolución de la incidencia fue de $tiempo_resolucion.\n";
    $report_content .= "Se ha comunicado al usuario $usuario la resolución y el cierre formal de su solicitud mediante correo electrónico. Se le ha informado que, si tiene alguna consulta adicional sobre las medidas implementadas o cualquier otra inquietud, puede responder al mensaje recibido o contactar directamente al área de TI.\n";
    $report_content .= "\nAtentamente,\nEl Equipo de Soporte Técnico\n(Informe generado: $fecha_actual)\n";
    
    
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Informe de Ticket <?php echo htmlspecialchars($ticket_id ?: 'Error'); ?> - Sistema de Tickets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- CSS Unificado del Sistema de Tickets -->
    <link rel="stylesheet" href="../assets/css/main.css">
    <link rel="stylesheet" href="../assets/css/unified/print.css" media="print">
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
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f7f9;
            color: #333;
            margin: 0;
            line-height: 1.6;
        }
        
        .container-main {
            max-width: 950px;
            margin: 0 auto;
            background-color: #fff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 6px 18px var(--shadow-color);
        }
        
        .ticket-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .ticket-id-badge {
            background-color: var(--primary-color);
            color: white;
            padding: 5px 15px;
            border-radius: 30px;
            font-weight: bold;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .ticket-states {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .state-item {
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.9em;
            font-weight: 500;
        }
        
        .state-prioridad-alta, .state-prioridad-urgente {
            background-color: #fff5f5;
            color: #cc0000;
        }
        
        .state-prioridad-media {
            background-color: #fff9e6;
            color: #856404;
        }
        
        .state-prioridad-baja {
            background-color: #f0f9f0;
            color: #0f5132;
        }
        
        .state-eficiencia-excelente {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .state-eficiencia-buena {
            background-color: #d1ecf1;
            color: #055160;
        }
        
        .state-eficiencia-normal {
            background-color: #fff3cd;
            color: #664d03;
        }
        
        .state-eficiencia-demorada {
            background-color: #f8d7da;
            color: #842029;
        }
        
        .report-content-card {
            background: #fff;
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 25px;
        }
        
        .report-sections {
            display: grid;
            grid-template-columns: 7fr 3fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .report-main {
            border-right: 1px solid var(--border-color);
            padding-right: 20px;
        }
        
        .report-sidebar {
            padding-left: 10px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-section-title {
            font-size: 1.1em;
            margin-bottom: 8px;
            padding-bottom: 5px;
            border-bottom: 1px solid var(--border-color);
            color: var(--dark-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-section-title i {
            color: var(--primary-color);
        }
        
        .info-field {
            margin-bottom: 12px;
        }
        
        .info-label {
            font-weight: 600;
            color: var(--tertiary-color);
            margin-right: 10px;
            font-size: 0.9em;
        }
        
        .info-value {
            font-weight: 500;
        }
        
        #notificacion-editable {
            width: 100%;
            min-height: 550px;
            font-family: 'Cascadia Code', 'SF Mono', 'Consolas', 'Menlo', monospace;
            font-size: 0.95em;
            color: var(--dark-color);
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 15px;
            resize: vertical;
            background-color: #fdfdfd;
            line-height: 1.65;
            white-space: pre-wrap;
            word-break: break-word;
            box-sizing: border-box;
            margin-bottom: 15px;
        }
        
        #notificacion-editable:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(0,102,204,.25);
            outline: none;
        }
        
        .action-buttons-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 20px;
            justify-content: space-between;
        }
        
        .action-buttons-left, .action-buttons-right {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .btn {
            padding: 10px 18px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 0.95em;
            font-weight: 500;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #0052a3;
        }
        
        .btn-success {
            background-color: var(--secondary-color);
            color: white;
        }
        
        .btn-success:hover {
            background-color: #218838;
        }
        
        .btn-secondary {
            background-color: var(--tertiary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .btn-outline {
            background-color: transparent;
            border: 1px solid var(--border-color);
            color: var(--dark-color);
        }
        
        .btn-outline:hover {
            background-color: #f8f9fa;
        }
        
        .form-control-wa, .form-control-email {
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-family: 'Cascadia Code', 'SF Mono', 'Consolas', 'Menlo', monospace;
            width: 160px;
        }
        
        .tech-signature {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px dashed var(--border-color);
        }
        
        .tech-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .tech-avatar {
            width: 60px;
            height: 60px;
            background-color: #e9ecef;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--tertiary-color);
            font-size: 1.8em;
        }
        
        .tech-details {
            flex: 1;
        }
        
        .tech-name {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 3px;
        }
        
        .tech-contact {
            color: var(--tertiary-color);
            font-size: 0.9em;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 25px;
        }
        
        .page-header h2 {
            color: var(--primary-color);
            font-weight: 600;
        }
        
        .error-message {
            color: #d9534f;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 15px;
            border-radius: 5px;
            text-align: center;
        }
        
        .attachment-badge {
            background-color: #e9ecef;
            display: inline-flex;
            padding: 5px 10px;
            border-radius: 3px;
            align-items: center;
            gap: 8px;
            font-size: 0.85em;
            color: var(--dark-color);
            margin-top: 8px;
        }
        
        .attachment-badge i {
            color: var(--tertiary-color);
        }
        
        .file-preview {
            margin-top: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            padding: 10px;
            background-color: #f8f9fa;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .file-preview-icon {
            font-size: 2em;
            color: var(--primary-color);
        }
        
        .file-preview-info {
            flex: 1;
        }
        
        .file-preview-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        
        .file-preview-size {
            font-size: 0.85em;
            color: var(--tertiary-color);
        }
        
        .page-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid var(--border-color);
            color: var(--tertiary-color);
            font-size: 0.9em;
        }
        
        .estado-badge {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        
        .estado-resuelto {
            background-color: #d1e7dd;
            color: #0f5132;
        }
        
        .estado-cerrado {
            background-color: #cfe2ff;
            color: #0a58ca;
        }        /* Estilos de Impresión - Formato Simple */        @media print {
            @page {
                size: A4;
                margin: 10mm;
                marks: none;
                /* Eliminar cabeceras y pies de página automaticos del navegador */
                margin-header: 0;
                margin-footer: 0;
                padding: 0;
            }
            
            /* Resetear estilos básicos para limpieza */
            body, html {
                margin: 0 !important; 
                padding: 0 !important;
                background-color: #fff !important;
                font-size: 11pt !important;
                font-family: 'Courier New', monospace !important;
                color: #000 !important;
                line-height: 1.5 !important;
            }
            
            /* Ocultar absolutamente todo excepto el texto del informe */
            .container-main, .report-content-card, .report-sections, 
            .report-main, .info-section, .tech-signature, 
            .report-sidebar, .ticket-header, .ticket-states {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Ocultar todos los elementos que no deben imprimirse */
            .no-print, 
            .action-buttons-container, 
            .page-header,
            .btn,
            .ticket-actions,
            .page-footer,
            button,
            .info-section-title,
            .file-preview,
            .tech-info,
            form > *:not(#notificacion-editable),
            .estado-badge {
                display: none !important;
                visibility: hidden !important;
            }
            
            /* Mostrar SOLO el textarea con el contenido */
            #notificacion-editable {
                display: block !important;
                visibility: visible !important;
                position: absolute !important;
                top: 0 !important;
                left: 0 !important;
                width: 100% !important;
                height: auto !important;
                overflow: visible !important;
                border: none !important;
                background: none !important;
                box-shadow: none !important;
                resize: none !important;
                outline: none !important;
                font-family: 'Courier New', monospace !important;
                font-size: 11pt !important;
                color: #000 !important;
                white-space: pre-wrap !important;
                padding: 0 !important;
                margin: 0 !important;
                page-break-inside: auto !important;
                line-height: 1.35 !important;
            }
              /* Control de página */
            @page {
                size: A4;
                margin: 15mm;
            }
            
            /* Ocultar el container principal pero mostrar su contenido */
            .container-main {
                background: none !important;
                box-shadow: none !important;
                border: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            
            /* Forzar que solo se vea el textarea */
            body > *:not(.container-main),
            .container-main > *:not(form),
            form > *:not(#notificacion-editable) {
                display: none !important;
                visibility: hidden !important;
                height: 0 !important;
                position: absolute !important;
                overflow: hidden !important;
                z-index: -9999 !important;
            }
            
            /* Asegurar que el texto impreso sea realmente simple */
            .texto-para-imprimir {
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
                max-width: 100% !important;
                width: 100% !important;
                height: auto !important;
            }
            
            /* Restablecer los atributos de impresión en caso de que otra regla los sobrescriba */
            * {
                print-color-adjust: exact !important;
                -webkit-print-color-adjust: exact !important;                color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="container-main">
        <?php if ($ticket_data && !$error_message): ?>
            <div class="page-header no-print">
                <h2><i class="fas fa-file-alt"></i> Informe Detallado del Ticket</h2>
            </div>

            <div class="report-content-card">
                <div class="ticket-header">
                    <div class="ticket-id-badge">
                        <i class="fas fa-ticket-alt"></i> Ticket #<?php echo $id_ticket; ?>
                    </div>
                    
                    <span class="estado-badge estado-<?php echo strtolower($estado_ticket); ?>">
                        <i class="fas fa-<?php echo ($estado_ticket == 'Resuelto' ? 'check-circle' : 'check-double'); ?>"></i>
                        <?php echo $estado_ticket; ?>
                    </span>
                </div>
                
                <div class="ticket-states no-print">
                    <div class="state-item state-prioridad-<?php echo strtolower($prioridad_ticket); ?>">
                        <i class="fas fa-<?php echo strtolower($prioridad_ticket) == 'alta' || strtolower($prioridad_ticket) == 'urgente' ? 'exclamation-triangle' : (strtolower($prioridad_ticket) == 'media' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                        Prioridad: <?php echo $prioridad_ticket; ?>
                    </div>
                    
                    <div class="state-item state-eficiencia-<?php echo strtolower($eficiencia); ?>">
                        <i class="fas fa-<?php echo $eficiencia == 'Excelente' ? 'bolt' : ($eficiencia == 'Buena' ? 'thumbs-up' : ($eficiencia == 'Normal' ? 'check' : 'clock')); ?>"></i>
                        Resolución: <?php echo $eficiencia; ?>
                    </div>
                    
                    <div class="state-item">
                        <i class="fas fa-calendar-check"></i>
                        <?php echo $tiempo_resolucion; ?>
                    </div>
                </div>

                <div class="report-sections">
                    <div class="report-main">
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-info-circle"></i> Información del Ticket
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Departamento:</span>
                                <span class="info-value"><?php echo $nombre_departamento; ?></span>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Asunto:</span>
                                <span class="info-value"><?php echo $asunto_ticket; ?></span>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Creado:</span>
                                <span class="info-value"><?php echo $fecha_creacion_ticket; ?></span>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Cerrado:</span>
                                <span class="info-value"><?php echo $fecha_cierre_ticket; ?></span>
                            </div>
                        </div>
                        
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-user"></i> Información del Solicitante
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Nombre:</span>
                                <span class="info-value"><?php echo $nombre_solicitante; ?></span>
                            </div>
                              <?php if ($dni_solicitante != 'N/A'): ?>
                            <div class="info-field">
                                <span class="info-label">DNI:</span>
                                <span class="info-value"><?php echo $dni_solicitante; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($correo_solicitante != 'N/A'): ?>
                            <div class="info-field">
                                <span class="info-label">Correo:</span>
                                <span class="info-value"><?php echo $correo_solicitante; ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (!empty($telefono_solicitante) && $telefono_solicitante != 'N/A'): ?>
                            <div class="info-field">
                                <span class="info-label">Teléfono:</span>
                                <span class="info-value"><?php echo $telefono_solicitante; ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($tiene_adjunto): ?>
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-paperclip"></i> Archivos Adjuntos
                            </div>
                            
                            <div class="file-preview">
                                <div class="file-preview-icon">
                                    <i class="far fa-file"></i>
                                </div>
                                <div class="file-preview-info">
                                    <div class="file-preview-name"><?php echo $nombre_archivo; ?></div>
                                    <div class="file-preview-size">Adjuntado por el usuario al crear el ticket</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                          <?php if ($tecnico_asignado != 'N/A'): ?>
                        <div class="info-section tech-signature no-print">
                            <div class="info-section-title">
                                <i class="fas fa-user-cog"></i> Técnico Asignado
                            </div>
                            
                            <div class="tech-info">
                                <div class="tech-avatar">
                                    <i class="fas fa-user-circle"></i>
                                </div>
                                <div class="tech-details">
                                    <div class="tech-name"><?php echo $tecnico_asignado; ?></div>
                                    <?php if ($email_tecnico != 'N/A'): ?>
                                    <div class="tech-contact">
                                        <i class="fas fa-envelope"></i> <?php echo $email_tecnico; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="report-sidebar no-print">
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-chart-pie"></i> Estadísticas
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Días:</span>
                                <span class="info-value"><?php echo $dias_resolucion; ?> día(s)</span>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Horas totales:</span>
                                <span class="info-value"><?php echo $horas_resolucion; ?> hora(s)</span>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Eficiencia:</span>
                                <span class="info-value"><?php echo $eficiencia; ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($notas_internas)): ?>
                        <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-sticky-note"></i> Notas Internas
                            </div>
                            
                            <p><?php echo nl2br($notas_internas); ?></p>
                        </div>
                        <?php endif; ?>
                          <div class="info-section">
                            <div class="info-section-title">
                                <i class="fas fa-cog"></i> Detalles Técnicos
                            </div>
                            </div>
                            
                            <div class="info-field">
                                <span class="info-label">Últ. Actualización:</span>
                                <span class="info-value"><?php echo $ultima_actualizacion; ?></span>
                            </div>
                        </div>
                    </div>
                </div>                  <form id="form-editar-notificacion" class="formulario-notificacion" method="post" style="clear:both; margin-bottom:0; position: relative;">
                    <button type="button" id="btn-imprimir-texto" class="btn btn-info no-print" style="position: absolute; right: 0; top: -50px;">
                        <i class="fas fa-print"></i> Imprimir Texto Simple
                    </button>
                    <textarea id="notificacion-editable" name="notificacion_editable" wrap="hard" class="texto-para-imprimir"><?php echo $report_content; ?></textarea>
                    
                    <div class="action-buttons-container no-print">
                        <div class="action-buttons-left">
                            <input type="text" name="numero_whatsapp" id="numero_whatsapp" 
                                   value="<?php echo $numero_whatsapp_input; ?>" 
                                   pattern="[0-9]{7,15}" maxlength="15" class="form-control-wa" 
                                   placeholder="N° WhatsApp" required>
                                   
                            <button type="button" class="btn btn-success" id="btn-enviar-wa">
                                <i class="fab fa-whatsapp"></i> Enviar por WhatsApp
                            </button>
                              <button type="button" class="btn btn-secondary" onclick="copiarNotificacion()">
                                <i class="far fa-copy"></i> Copiar Texto
                            </button>

                            <button type="button" class="btn btn-primary" id="btn-enviar-email">
                                <i class="fas fa-envelope"></i> Enviar por Email
                            </button>

                            <button type="button" class="btn btn-secondary" id="btn-generar-pdf">
                                <i class="fas fa-file-pdf"></i> Generar PDF
                            </button>
                        </div>
                        
                        <div class="action-buttons-right">
                            <button type="button" class="btn btn-outline" onclick="location.href='<?php echo BASE_URL; ?>admin/index.php'">
                                <i class="fas fa-arrow-left"></i> Volver al Panel
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="page-footer no-print">
                <p>Sistema de Tickets - Municipalidad Provincial</p>
                <p>Fecha de generación: <?php echo $fecha_actual_formateada; ?> a las <?php echo $hora_actual_formateada; ?></p>
            </div>

        <?php else: ?>
            <div class="page-header">
                <h2>Error al Generar Informe</h2>
            </div>
            <div class="error-message">
                <p><?php echo !empty($error_message) ? $error_message : "No se pudo cargar la información del ticket para el informe."; ?></p>
                <p style="margin-top:15px;"><a href="<?php echo BASE_URL; ?>admin/index.php" class="btn btn-primary">Volver al Panel</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
    function copiarNotificacion() {
        var textarea = document.getElementById('notificacion-editable');
        if (!textarea) return;

        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textarea.value)
                .then(() => {
                    alert('Texto copiado al portapapeles.');
                })
                .catch(err => {
                    console.error('Error al copiar con API Clipboard: ', err);
                    try { 
                        textarea.select();
                        document.execCommand('copy');
                        alert('Texto copiado (método alternativo).');
                    } catch (e) {
                        alert('Error al copiar el texto. Por favor, cópielo manualmente.');
                        console.error('Error con execCommand: ', e);
                    }
                });
        } else { 
            try {
                textarea.select();
                document.execCommand('copy');
                alert('Texto copiado (método alternativo).');
            } catch (e) {
                alert('Error al copiar el texto. Por favor, cópielo manualmente.');
                console.error('Error con execCommand: ', e);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        var btnEnviarWA = document.getElementById('btn-enviar-wa');
        if (btnEnviarWA) {
            btnEnviarWA.onclick = function() {
                var textarea = document.getElementById('notificacion-editable');
                var texto = textarea.value; 
                
                var numeroInput = document.getElementById('numero_whatsapp');
                var numero = numeroInput.value.replace(/[^0-9]/g, ''); 
                var pais = '51'; // Código para Perú
                
                if (!numero) {
                    alert('Por favor, ingrese un número de WhatsApp.');
                    numeroInput.focus();
                    return;
                }
                
                if (numero.length < 7 || numero.length > 15) { 
                    alert('El número de WhatsApp no parece válido. Verifique la longitud (ej. 9 dígitos para Perú).');
                    numeroInput.focus();
                    return;
                }

                // Si el número ya incluye el código de país, no añadirlo
                var numeroCompleto = numero;
                if (!(numero.startsWith(pais) && numero.length > 9)) {
                     numeroCompleto = pais + numero;
                }
                  var url = 'https://wa.me/' + numeroCompleto + '?text=' + encodeURIComponent(texto);
                window.open(url, '_blank');
            };
        }
        
        // Funcionalidad para enviar por email
        var btnEnviarEmail = document.getElementById('btn-enviar-email');
        if (btnEnviarEmail) {
            btnEnviarEmail.addEventListener('click', function() {
                var textarea = document.getElementById('notificacion-editable');
                var texto = textarea.value;
                var asunto = "Informe de Ticket #<?php echo $id_ticket; ?>";
                var destinatario = "<?php echo $correo_solicitante; ?>";
                
                if (!destinatario || !destinatario.includes('@')) {
                    var email = prompt('Ingrese el correo del destinatario:', '<?php echo $correo_solicitante; ?>');
                    if (!email) return;
                    destinatario = email;
                }
                
                var mailtoUrl = 'mailto:' + encodeURIComponent(destinatario) + 
                              '?subject=' + encodeURIComponent(asunto) + 
                              '&body=' + encodeURIComponent(texto);
                
                window.location.href = mailtoUrl;
            });        }
        
        // Funcionalidad para generar PDF con formato limpio
        var btnGenerarPDF = document.getElementById('btn-generar-pdf');
        if (btnGenerarPDF) {
            btnGenerarPDF.addEventListener('click', function() {
                // Abrir la versión de impresión en una nueva ventana
                window.open('<?php echo BASE_URL; ?>reports/imprimir_informe.php?ticket_id=<?php echo $id_ticket; ?>&format=pdf', '_blank');
            });
        }
          
        // Optimizar impresión cuando se usa el botón de imprimir texto simple        // Mejorar la funcionalidad del botón "Imprimir Texto Simple"
        var btnImprimirTexto = document.getElementById('btn-imprimir-texto');
        if (btnImprimirTexto) {
            
            // Agregar el nuevo evento click
            btnImprimirTexto.addEventListener('click', function(e) {
                e.preventDefault(); // Prevenir comportamiento por defecto
                
                // Crear un documento de impresión temporal y limpio
                var printContent = document.getElementById('notificacion-editable').value;
                var printWin = window.open('', '_blank', 'width=800,height=600');
                      printWin.document.write('<html><head><title>Informe Ticket #<?php echo $id_ticket; ?></title>');
            printWin.document.write('<style>');
            printWin.document.write(`
                @media print {
                    body { 
                        font-family: "Courier New", monospace; 
                        font-size: 12pt; 
                        line-height: 1.4; 
                        margin: 15mm; 
                        white-space: pre-wrap;
                    }
                    button { display: none; }
                    @page {
                        size: A4;
                        margin: 15mm;
                    }
                }
                body { 
                    font-family: "Courier New", monospace; 
                    font-size: 12pt; 
                    line-height: 1.4; 
                    margin: 15mm; 
                    white-space: pre-wrap;
                    background-color: #f9f9f9;
                }
                .print-header {
                    text-align: center;
                    margin-bottom: 20px;
                    font-weight: bold;
                }
                .print-button {
                    padding: 8px 15px;
                    background: #007bff;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 20px auto;
                    display: block;
                }
                .close-button {
                    padding: 8px 15px;
                    background: #6c757d;
                    color: white;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 10px auto;
                    display: block;
                }
            `);
            printWin.document.write('</style></head><body>');
            printWin.document.write('<div class="print-header">INFORME TICKET #<?php echo $id_ticket; ?></div>');
            printWin.document.write(printContent.replace(/</g, '&lt;').replace(/>/g, '&gt;'));
            printWin.document.write('<button onclick="window.print();" class="print-button">Imprimir</button>');
            printWin.document.write('<button onclick="window.close();" class="close-button">Cerrar ventana</button>');
            printWin.document.write('</body></html>');
            
            printWin.document.close();
                      // Esperar a que se cargue el contenido para imprimir
            setTimeout(function() {
                printWin.focus();
            }, 250);
            });
        }
    });
    </script>    <?php require_once '../core/templates/footer.php'; ?>
</body>
</html>
