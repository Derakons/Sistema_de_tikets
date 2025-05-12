<?php 
$page_title = "Crear Nuevo Ticket"; // Definir título específico para esta página
require_once 'includes/templates/header.php'; 
require_once 'includes/functions.php';
$departamentos = obtener_departamentos($conn);
?>
<!-- Contenido específico de index.php -->
<div class="container page-container form-container-specific">
    <h1>Crear Nuevo Ticket de TI</h1>
    <form action="guardar_ticket.php" method="POST">
        <div class="form-group">
            <label for="descripcion_breve"> Reporte de averia:</label>
            <input type="text" id="descripcion_breve" name="descripcion_breve" required>
        </div>
        <div class="form-group">
            <label for="detalle_fallo">Detalles de averia:</label>
            <textarea id="detalle_fallo" name="detalle_fallo" rows="1" required></textarea>
        </div>
        <div class="form-group">
            <label for="departamento">Departamento de Origen:</label>
            <select id="departamento" name="departamento" required>
                <option value="">Seleccione un departamento</option>
                <?php foreach ($departamentos as $dep): ?>
                    <option value="<?php echo $dep['id']; ?>"><?php echo htmlspecialchars($dep['nombre_departamento']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="nombre_solicitante">Nombre del Solicitante (Opcional):</label>
            <input type="text" id="nombre_solicitante" name="nombre_solicitante">
        </div>
        <button type="submit" class="btn btn-primary">Enviar Ticket</button>
    </form>
    <!-- El enlace de seguimiento ahora está en el header principal -->
</div>
<?php require_once 'includes/templates/footer.php'; ?>
