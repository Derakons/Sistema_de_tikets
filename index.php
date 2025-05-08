<?php 
$page_title = "Crear Nuevo Ticket"; // Definir título específico para esta página
require_once 'includes/templates/header.php'; 
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
                <option value="Alcaldía">Alcaldía</option>
                <option value="Tesorería">Tesorería</option>
                <option value="Obras Públicas">Obras Públicas</option>
                <option value="Recursos Humanos">Recursos Humanos</option>
                <option value="Informática">Informática</option>
                <option value="Seguridad Ciudadana">Seguridad Ciudadana</option>
                <option value="Desarrollo Social">Desarrollo Social</option>
                <!-- Agrega más departamentos según sea necesario -->
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
