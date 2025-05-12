<?php
require_once '../core/config.php';
require_once '../core/functions.php';

$ticket_info = null;
$error_message = '';
$numero_ticket_consultado = '';

if (isset($_GET['numero_ticket'])) {
    $numero_ticket_consultado = limpiar_datos($_GET['numero_ticket']);

    if (!empty($numero_ticket_consultado)) {
        $sql = "SELECT t.*, d.nombre_departamento 
                FROM tickets t 
                LEFT JOIN departamentos d ON t.id_departamento = d.id 
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
<?php
$page_title = "Seguimiento de Ticket";
require_once '../core/templates/header.php';
?>
<div class="container-main">
    <div class="section-header">
        <h1 class="section-title"><i class="fas fa-search-location"></i> Seguimiento de Ticket</h1>
    </div>

    <form action="<?php echo BASE_URL; ?>public/seguimiento.php" method="GET" class="search-form">
        <div class="form-group">
            <input type="text" id="numero_ticket" name="numero_ticket" placeholder="Ingrese su número de ticket" value="<?php echo htmlspecialchars($numero_ticket_consultado); ?>" required>
        </div>
        <button type="submit" class="btn-primary">
            <i class="fas fa-search"></i> Consultar Estado
        </button>
    </form>

    <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($ticket_info): ?>
        <div class="ticket-card">
            <div class="ticket-header">
                <div class="ticket-id">
                    <i class="fas fa-ticket-alt"></i> Ticket #<?php echo htmlspecialchars($ticket_info['id']); ?>
                </div>
                <span class="ticket-estado estado-<?php echo strtolower(str_replace(' ', '-', $ticket_info['estado'])); ?>">
                    <i class="fas fa-<?php 
                        switch($ticket_info['estado']) {
                            case 'Abierto': echo 'folder-open'; break;
                            case 'En Progreso': echo 'cog fa-spin'; break;
                            case 'Resuelto': echo 'check-circle'; break;
                            case 'Cerrado': echo 'lock'; break;
                            case 'Esperando Respuesta': echo 'clock'; break;
                            default: echo 'info-circle';
                        }
                    ?>"></i>
                    <?php echo htmlspecialchars($ticket_info['estado']); ?>
                </span>
            </div>
            
            <div class="ticket-body">
                <div class="info-section">
                    <h3 class="info-section-title">
                        <i class="fas fa-info-circle"></i> Información General
                    </h3>
                    <div class="info-row">
                        <div class="info-label">Fecha de Creación:</div>
                        <div class="info-value"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_creacion']))); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Departamento:</div>
                        <div class="info-value"><?php echo htmlspecialchars($ticket_info['nombre_departamento'] ?: 'No especificado'); ?></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Asunto:</div>
                        <div class="info-value"><?php echo htmlspecialchars($ticket_info['descripcion_breve']); ?></div>
                    </div>
                    <?php if ($ticket_info['fecha_actualizacion_admin']): ?>
                    <div class="info-row">
                        <div class="info-label">Última Actualización:</div>
                        <div class="info-value"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_actualizacion_admin']))); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="info-section">
                    <h3 class="info-section-title">
                        <i class="fas fa-clipboard-list"></i> Detalle del Ticket
                    </h3>
                    <div class="detalle-expandible activo">
                        <div class="detalle-header">
                            <h4>Descripción del Problema</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="detalle-content">
                            <pre><?php echo htmlspecialchars($ticket_info['detalle_fallo']); ?></pre>
                        </div>
                    </div>
                </div>
                
                <div class="progreso-ticket">
                    <h3 class="info-section-title">
                        <i class="fas fa-tasks"></i> Progreso del Ticket
                    </h3>
                    <div class="barra-progreso">
                        <?php
                        // Definir las etapas y sus valores numéricos para comparación
                        $etapas = [
                            'Abierto' => 1,
                            'En Progreso' => 2,
                            'Esperando Respuesta' => 3,
                            'Resuelto' => 4,
                            'Cerrado' => 5
                        ];
                        
                        // Obtener el valor numérico del estado actual
                        $estadoActual = $etapas[$ticket_info['estado']] ?? 0;
                        
                        // Calcular el ancho de la barra de progreso (20% por etapa completada)
                        $progresoAncho = ($estadoActual / 5) * 100;
                        ?>
                        
                        <div class="linea-progreso" style="width: <?php echo $progresoAncho; ?>%"></div>
                        
                        <?php foreach($etapas as $etapa => $valor): ?>
                            <div class="etapa <?php echo $estadoActual > $valor ? 'completada' : ($estadoActual == $valor ? 'actual' : ''); ?>">
                                <div class="etapa-icono">
                                    <i class="fas fa-<?php 
                                        switch($etapa) {
                                            case 'Abierto': echo 'folder-open'; break;
                                            case 'En Progreso': echo 'cog'; break;
                                            case 'Esperando Respuesta': echo 'clock'; break;
                                            case 'Resuelto': echo 'check-circle'; break;
                                            case 'Cerrado': echo 'lock'; break;
                                            default: echo 'circle';
                                        }
                                    ?>"></i>
                                </div>
                                <div class="etapa-texto"><?php echo $etapa; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            
                <?php if ($ticket_info['estado'] == 'Resuelto' || $ticket_info['estado'] == 'Cerrado'): ?>
                <div class="info-section">
                    <h3 class="info-section-title">
                        <i class="fas fa-clipboard-check"></i> Resolución del Ticket
                    </h3>
                    
                    <?php if ($ticket_info['diagnostico']): ?>
                    <div class="detalle-expandible">
                        <div class="detalle-header">
                            <h4>Diagnóstico del Administrador</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="detalle-content">
                            <pre><?php echo htmlspecialchars($ticket_info['diagnostico']); ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket_info['cierre_solucion']): ?>
                    <div class="detalle-expandible">
                        <div class="detalle-header">
                            <h4>Solución Aplicada</h4>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        <div class="detalle-content">
                            <pre><?php echo htmlspecialchars($ticket_info['cierre_solucion']); ?></pre>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($ticket_info['fecha_cierre']): ?>
                    <div class="info-row">
                        <div class="info-label">Fecha de Cierre/Resolución:</div>
                        <div class="info-value"><?php echo htmlspecialchars(date("d/m/Y H:i", strtotime($ticket_info['fecha_cierre']))); ?></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            
                <div class="action-buttons">
                    <a href="javascript:window.print();" class="btn btn-primary">
                        <i class="fas fa-print"></i> Imprimir Seguimiento
                    </a>
                    <?php if ($ticket_info['estado'] == 'Resuelto' || $ticket_info['estado'] == 'Cerrado'): ?>
                    <a href="<?php echo BASE_URL; ?>reports/imprimir_informe.php?ticket_id=<?php echo htmlspecialchars($ticket_info['id']); ?>" target="_blank" class="btn btn-success">
                        <i class="fas fa-file-alt"></i> Ver Informe Completo
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo BASE_URL; ?>public/index.php" class="btn btn-outline no-print">
                        <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!$ticket_info && empty($error_message)): ?>
        <div class="info-section" style="text-align: center; padding: 30px 20px;">
            <i class="fas fa-search" style="font-size: 3em; color: #ccc; margin-bottom: 15px;"></i>
            <h3 style="color: var(--tertiary-color); font-weight: normal;">Ingrese un número de ticket para consultar su estado</h3>
            <p style="color: var(--tertiary-color);">Podrá ver el estado actual, detalles y la resolución si está disponible.</p>
            <div class="action-buttons">
                <a href="<?php echo BASE_URL; ?>public/index.php" class="btn btn-outline">
                    <i class="fas fa-plus-circle"></i> Crear Nuevo Ticket
                </a>
            </div>
        </div>
    <?php endif; ?>

</div>
<?php require_once '../core/templates/footer.php'; ?>
