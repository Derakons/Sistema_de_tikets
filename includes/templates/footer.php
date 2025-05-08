<?php
require_once __DIR__ . '/../config.php'; // Ajustar ruta
?>
    </main> <!-- Cierra el main-content abierto en header.php -->
    <footer class="site-footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Municipalidad Provincial de Canchis - Sistema de Tickets de TI.</p>
            <p>Desarrollado con fines demostrativos.</p>
            <div class="footer-buttons">
                <a href="index.php" class="btn btn-secondary">Inicio</a>
                <a href="seguimiento.php" class="btn btn-secondary">Seguimiento</a>
                <a href="login_admin.php" class="btn btn-info">Admin</a>
            </div>
        </div>
    </footer>
    <script src="<?php echo BASE_URL; ?>assets/js/script.js"></script>
</body>
</html>