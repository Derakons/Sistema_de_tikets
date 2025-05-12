<?php
require_once 'core/config.php'; // Para la conexión $conn y session_start()

// Desactivar temporalmente errores fatales si las tablas ya existen y se intenta recrear sin IF NOT EXISTS
// error_reporting(E_ALL ^ E_WARNING ^ E_NOTICE);

$page_title = "Configuración del Sistema de Tickets";
require_once 'core/templates/header.php';

echo "<div class='container page-container'>";
echo "<h1><i class='fas fa-cogs'></i> Configuración Inicial del Sistema de Tickets</h1>";

// --- 1. CREAR TABLA DEPARTAMENTOS ---
echo "<h2><i class='fas fa-building'></i> Tabla de Departamentos</h2>";
$sql_departamentos = "CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_departamento VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_departamentos) === TRUE) {
    echo "<p class='success-message'>Tabla 'departamentos' creada exitosamente o ya existente.</p>";

    // Insertar departamentos iniciales si la tabla está vacía
    $check_dept = $conn->query("SELECT COUNT(*) as count FROM departamentos");
    $count_dept = $check_dept->fetch_assoc()['count'];
    if ($count_dept == 0) {
        echo "<p>Insertando departamentos iniciales...</p>";
        $departamentos_iniciales = [
            ['nombre_departamento' => 'Mesa de Partes', 'descripcion' => 'Recepción y derivación de documentos.'],
            ['nombre_departamento' => 'Soporte Técnico TI', 'descripcion' => 'Asistencia con problemas de hardware y software.'],
            ['nombre_departamento' => 'Rentas', 'descripcion' => 'Consultas sobre tributos y pagos municipales.'],
            ['nombre_departamento' => 'Desarrollo Urbano y Rural', 'descripcion' => 'Trámites de licencias de construcción, catastro, etc.'],
            ['nombre_departamento' => 'Servicios Públicos', 'descripcion' => 'Gestión de limpieza, parques y jardines, alumbrado.'],
            ['nombre_departamento' => 'Alcaldía', 'descripcion' => 'Asuntos directos con la alcaldía.'],
            ['nombre_departamento' => 'Secretaría General', 'descripcion' => 'Gestión documentaria y administrativa general.']
        ];
        $stmt_dept = $conn->prepare("INSERT INTO departamentos (nombre_departamento, descripcion) VALUES (?, ?)");
        foreach ($departamentos_iniciales as $dept) {
            $stmt_dept->bind_param("ss", $dept['nombre_departamento'], $dept['descripcion']);
            if ($stmt_dept->execute()) {
                echo "<p class='success-message'>Departamento '" . htmlspecialchars($dept['nombre_departamento']) . "' insertado.</p>";
            } else {
                echo "<p class='error-message'>Error insertando departamento '" . htmlspecialchars($dept['nombre_departamento']) . "': " . $stmt_dept->error . "</p>";
            }
        }
        $stmt_dept->close();
    } else {
        echo "<p>La tabla 'departamentos' ya contiene datos.</p>";
    }
} else {
    echo "<p class='error-message'>Error creando tabla 'departamentos': " . $conn->error . "</p>";
}

// --- 2. CREAR TABLA USUARIOS_ADMIN ---
echo "<hr><h2><i class='fas fa-users-cog'></i> Tabla de Usuarios Administradores</h2>";
$sql_usuarios_admin = "CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    rol VARCHAR(50) DEFAULT 'administrador',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_usuarios_admin) === TRUE) {
    echo "<p class='success-message'>Tabla 'usuarios_admin' creada exitosamente o ya existente.</p>";

    // Insertar usuario administrador por defecto si la tabla está vacía
    $check_admin = $conn->query("SELECT COUNT(*) as count FROM usuarios_admin");
    $count_admin = $check_admin->fetch_assoc()['count'];
    if ($count_admin == 0) {
        echo "<p>Insertando usuario administrador por defecto...</p>";
        $admin_user = 'admin';
        $admin_pass_plain = 'admin123'; // ¡CAMBIAR ESTA CONTRASEÑA INMEDIATAMENTE!
        $admin_pass_hash = password_hash($admin_pass_plain, PASSWORD_DEFAULT);
        $admin_nombre = 'Administrador Principal';
        $admin_email = 'admin@example.com';

        $stmt_admin = $conn->prepare("INSERT INTO usuarios_admin (nombre_usuario, contrasena_hash, nombre_completo, email) VALUES (?, ?, ?, ?)");
        $stmt_admin->bind_param("ssss", $admin_user, $admin_pass_hash, $admin_nombre, $admin_email);
        if ($stmt_admin->execute()) {
            echo "<p class='success-message'>Usuario administrador por defecto ('" . htmlspecialchars($admin_user) . "') creado.</p>";
            echo "<p class='warning-message'><strong>IMPORTANTE:</strong> La contraseña por defecto para el usuario '" . htmlspecialchars($admin_user) . "' es '" . htmlspecialchars($admin_pass_plain) . "'. ¡DEBES CAMBIARLA INMEDIATAMENTE por seguridad!</p>";
        } else {
            echo "<p class='error-message'>Error creando usuario administrador por defecto: " . $stmt_admin->error . "</p>";
        }
        $stmt_admin->close();
    } else {
        echo "<p>La tabla 'usuarios_admin' ya contiene datos.</p>";
    }

} else {
    echo "<p class='error-message'>Error creando tabla 'usuarios_admin': " . $conn->error . "</p>";
}

// --- 3. CREAR TABLA TICKETS ---
echo "<hr><h2><i class='fas fa-ticket-alt'></i> Tabla de Tickets</h2>";
$sql_tickets = "CREATE TABLE IF NOT EXISTS tickets (
    id VARCHAR(30) PRIMARY KEY, -- Formato TICKET-YYYYMMDD-XXXX o similar
    nombre_solicitante VARCHAR(150) NOT NULL,
    dni_solicitante VARCHAR(15) NOT NULL,
    telefono_solicitante VARCHAR(20) NULL,
    email_solicitante VARCHAR(100) NULL,
    id_departamento INT NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    archivo_adjunto VARCHAR(255) NULL, -- Ruta al archivo
    nombre_archivo_original VARCHAR(255) NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Abierto', -- Ej: Abierto, En Proceso, Resuelto, Cerrado, Pendiente de Información
    prioridad VARCHAR(50) DEFAULT 'Media', -- Ej: Baja, Media, Alta, Urgente
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_admin_asignado INT NULL,
    ip_solicitante VARCHAR(45) NULL,
    notas_internas TEXT NULL, -- Notas solo visibles para administradores
    fecha_resolucion TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_admin_asignado) REFERENCES usuarios_admin(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_tickets) === TRUE) {
    echo "<p class='success-message'>Tabla 'tickets' creada exitosamente o ya existente.</p>";
} else {
    echo "<p class='error-message'>Error creando tabla 'tickets': " . $conn->error . "</p>";
}

echo "<hr><p><strong>Proceso de configuración completado.</strong></p>";
echo "<p>Si es la primera vez que ejecutas esto, revisa los mensajes anteriores para confirmar que todo se creó correctamente.</p>";
echo "<p><a href='index.php' class='btn btn-primary'><i class='fas fa-home'></i> Ir a la página principal</a> ";
echo "<a href='admin/login.php' class='btn btn-secondary'><i class='fas fa-user-shield'></i> Ir al Login de Admin</a></p>";

echo "</div>"; // Fin de .container

$conn->close();
require_once 'core/templates/footer.php';
?>

<style>
    .success-message {
        color: #155724;
        background-color: #d4edda;
        border-color: #c3e6cb;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: .25rem;
    }
    .error-message {
        color: #721c24;
        background-color: #f8d7da;
        border-color: #f5c6cb;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: .25rem;
    }
    .warning-message {
        color: #856404;
        background-color: #fff3cd;
        border-color: #ffeeba;
        padding: .75rem 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid transparent;
        border-radius: .25rem;
    }
    h1, h2 {
        color: var(--primary-color);
        margin-top: 20px;
        margin-bottom: 10px;
    }
    h1 i, h2 i {
        margin-right: 10px;
    }
</style>
