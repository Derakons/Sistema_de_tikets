<?php
// filepath: c:\xampp\htdocs\Sistema de tikets\core\templates\sidebar_public.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<div class="admin-sidebar">
    <div class="sidebar-header">
        <img src="<?php echo BASE_URL; ?>img/logo.png" alt="Logo <?php echo htmlspecialchars(SITE_TITLE); ?>" class="admin-avatar">
        <h2 class="admin-name"><?php echo htmlspecialchars(SITE_TITLE); ?></h2>
    </div>
    <nav class="nav nav-pills flex-column sidebar-nav">
        <?php
        $current_page_script = basename($_SERVER['SCRIPT_NAME']);
        $current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));
        $is_public_index = ($current_page_script == 'index.php' && $current_dir == 'public');
        $is_public_seguimiento = ($current_page_script == 'seguimiento.php' && $current_dir == 'public');
        $is_admin_dashboard = ($current_page_script == 'dashboard.php' && $current_dir == 'admin');
        $is_admin_gestion_tickets = ($current_page_script == 'index.php' && $current_dir == 'admin');
        $is_admin_login = ($current_page_script == 'login.php' && $current_dir == 'admin');
        ?>
        <a href="<?php echo BASE_URL; ?>public/index.php" class="nav-link <?php echo $is_public_index ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i> Crear Ticket
        </a>
        <a href="<?php echo BASE_URL; ?>public/seguimiento.php" class="nav-link <?php echo $is_public_seguimiento ? 'active' : ''; ?>">
            <i class="fas fa-search"></i> Seguimiento
        </a>
        <?php if (isAdminLoggedIn()): ?>
            <hr class="sidebar-divider" style="border-top: 1px solid rgba(255,255,255,0.1); margin: 0.5rem 1rem;">
            <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-link <?php echo $is_admin_dashboard ? 'active' : ''; ?>">
                <i class="fas fa-tachometer-alt"></i> Admin Dashboard
            </a>
            <a href="<?php echo BASE_URL; ?>admin/index.php" class="nav-link <?php echo $is_admin_gestion_tickets ? 'active' : ''; ?>">
                <i class="fas fa-ticket-alt"></i> Gestionar Tickets
            </a>
        <?php endif; ?>
    </nav>
    <div class="sidebar-footer">
        <?php if (isAdminLoggedIn()): ?>
            <a href="<?php echo BASE_URL; ?>admin/logout.php" class="nav-link logout-link">
                <i class="fas fa-sign-out-alt"></i> Cerrar Sesión (Admin)
            </a>
        <?php else: ?>
            <a href="<?php echo BASE_URL; ?>admin/login.php" class="nav-link <?php echo $is_admin_login ? 'active' : ''; ?>">
                <i class="fas fa-sign-in-alt"></i> Admin Login
            </a>
        <?php endif; ?>
    </div>
    <button id="sidebarToggle" class="sidebar-toggle-btn" aria-label="Ocultar menú">
        <i class="fas fa-angle-double-left"></i>
    </button>
</div>
<script>
(function() {
    const sidebar = document.querySelector('.admin-sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
        if (sidebar.classList.contains('collapsed')) {
            toggleBtn.innerHTML = '<i class="fas fa-angle-double-right"></i>';
        } else {
            toggleBtn.innerHTML = '<i class="fas fa-angle-double-left"></i>';
        }
        localStorage.setItem('sidebar-collapsed', sidebar.classList.contains('collapsed'));
    }
    toggleBtn.addEventListener('click', toggleSidebar);
    if (localStorage.getItem('sidebar-collapsed') === 'true') {
        sidebar.classList.add('collapsed');
        toggleBtn.innerHTML = '<i class="fas fa-angle-double-right"></i>';
    }
})();
</script>
