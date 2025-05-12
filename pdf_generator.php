<?php
/**
 * pdf_generator.php - Generador de PDF para informes de tickets
 * 
 * Este script recibe los datos del ticket vía POST y genera un PDF del informe
 * Requiere la librería TCPDF para funcionar correctamente
 */

// Asegurar que la petición sea vía POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit(json_encode(['success' => false, 'error' => 'Método no permitido']));
}

// Verificar que tengamos los datos necesarios
if (empty($_POST['ticket_id']) || empty($_POST['content'])) {
    header('HTTP/1.1 400 Bad Request');
    exit(json_encode(['success' => false, 'error' => 'Faltan datos requeridos']));
}

// Incluir dependencias
require_once 'includes/config.php';
require_once 'includes/functions.php';

// Si está disponible, usar TCPDF para generar el PDF
if (file_exists('vendor/tcpdf/tcpdf.php')) {
    require_once 'vendor/tcpdf/tcpdf.php';
    
    try {
        // Datos del ticket
        $ticket_id = limpiar_datos($_POST['ticket_id']);
        $content = $_POST['content'];
        
        // Crear nueva instancia de TCPDF
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        
        // Configuración del documento
        $pdf->SetCreator('Sistema de Tickets');
        $pdf->SetAuthor('Municipalidad Provincial');
        $pdf->SetTitle('Informe de Ticket #' . $ticket_id);
        $pdf->SetSubject('Informe de Ticket');
        $pdf->SetKeywords('ticket, informe, soporte técnico');
        
        // Eliminar encabezado y pie de página predeterminados
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        
        // Configurar márgenes
        $pdf->SetMargins(15, 15, 15);
        
        // Añadir página
        $pdf->AddPage();
        
        // Fuente principal
        $pdf->SetFont('helvetica', '', 11);
        
        // Logo y título
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'INFORME DE TICKET #' . $ticket_id, 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 11);
        
        $pdf->Ln(5);
        
        // Contenido principal (texto plano del textarea)
        $contentFormatted = str_replace("\n", "<br />", $content);
        
        // Estilo para el contenido principal
        $html = '<div style="line-height: 1.4; font-family: courier;">' . $contentFormatted . '</div>';
        
        // Escribir contenido HTML
        $pdf->writeHTML($html, true, false, true, false, '');
        
        // Añadir línea final
        $pdf->Ln(5);
        $pdf->Cell(0, 0, '-- Fin del Informe --', 0, 1, 'C');
        
        // Nombre del archivo
        $filename = 'ticket_' . $ticket_id . '_' . date('Ymd_His') . '.pdf';
        $filepath = 'uploads/pdf/' . $filename;
        
        // Crear directorio si no existe
        if (!file_exists('uploads/pdf/')) {
            mkdir('uploads/pdf/', 0755, true);
        }
        
        // Guardar el PDF en el servidor
        $pdf->Output($filepath, 'F');
        
        // Devolver resultado exitoso
        echo json_encode([
            'success' => true,
            'file' => $filepath,
            'filename' => $filename
        ]);
        
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        exit(json_encode([
            'success' => false,
            'error' => 'Error al generar PDF: ' . $e->getMessage()
        ]));
    }
} else {
    // Si TCPDF no está disponible, devolver un mensaje de error
    header('HTTP/1.1 501 Not Implemented');
    exit(json_encode([
        'success' => false,
        'error' => 'La biblioteca TCPDF no está disponible. Por favor, instálela primero o utilice la función de imprimir.',
        'installation_guide' => 'Para instalar TCPDF, ejecute "composer require tecnickcom/tcpdf" en la línea de comandos.'
    ]));
}
