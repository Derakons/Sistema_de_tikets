<?php
// Iniciar sesiones solo si no hay una activa
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'municipalidad_canchis_tickets');

// Conexión al servidor MySQL (sin seleccionar base de datos)
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Crear la base de datos si no existe
$conn->query("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db(DB_NAME);

// Crear tabla departamentos si no existe
$conn->query("CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_departamento VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Crear tabla usuarios_admin si no existe
$conn->query("CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_usuario VARCHAR(50) NOT NULL UNIQUE,
    contrasena_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    rol VARCHAR(50) DEFAULT 'administrador',
    activo BOOLEAN DEFAULT TRUE,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// Crear tabla tickets si no existe
$conn->query("CREATE TABLE IF NOT EXISTS tickets (
    id VARCHAR(30) PRIMARY KEY,
    nombre_solicitante VARCHAR(150) NOT NULL,
    dni_solicitante VARCHAR(15) NULL,
    telefono_solicitante VARCHAR(20) NULL,
    email_solicitante VARCHAR(100) NULL,
    id_departamento INT NOT NULL,
    asunto VARCHAR(255) NOT NULL,
    descripcion TEXT NOT NULL,
    archivo_adjunto VARCHAR(255) NULL,
    nombre_archivo_original VARCHAR(255) NULL,
    estado VARCHAR(50) NOT NULL DEFAULT 'Abierto',
    prioridad VARCHAR(50) DEFAULT 'Media',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_admin_asignado INT NULL,
    ip_solicitante VARCHAR(45) NULL,
    notas_internas TEXT NULL,
    fecha_resolucion TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_admin_asignado) REFERENCES usuarios_admin(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

// ALTER TABLE para agregar campos faltantes si no existen
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS detalle_fallo TEXT NULL AFTER descripcion");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS descripcion_breve VARCHAR(255) NULL AFTER detalle_fallo");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS contacto_solicitante VARCHAR(100) NULL AFTER nombre_solicitante");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS departamento_id INT NULL AFTER id");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS identificacion_tipo VARCHAR(50) NULL AFTER prioridad");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS diagnostico TEXT NULL AFTER identificacion_tipo");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS cierre_solucion TEXT NULL AFTER diagnostico");
$conn->query("ALTER TABLE tickets ADD COLUMN IF NOT EXISTS fecha_actualizacion_admin DATETIME NULL AFTER ultima_actualizacion");

// Verificar si la conexión fue exitosa
$conn->query("SET NAMES 'utf8mb4'"); // Establecer el charset a utf8mb4 para soportar caracteres especiales y emojis
$conn->set_charset("utf8mb4");

// Configuración general del sitio
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$site_url_base = rtrim(dirname(dirname($_SERVER['PHP_SELF'])), '/\\') . '/'; // Asume que config.php está en 'includes'
define('SITE_URL', "{$protocol}{$host}{$site_url_base}"); // Cambia esto a la URL de tu proyecto
const SITE_TITLE = 'Sistema de Tickets - Municipalidad de Canchis';

// Configuración de zona horaria
date_default_timezone_set('America/Lima'); // Ajusta a tu zona horaria

// Insertar usuario administrador por defecto si no existe
$admin_user = 'admin';
$admin_pass_plain = 'admin123';
$admin_pass_hash = password_hash($admin_pass_plain, PASSWORD_DEFAULT);
$admin_nombre = 'Administrador Principal';
$admin_email = 'admin@example.com';

$check_admin = $conn->query("SELECT COUNT(*) as count FROM usuarios_admin WHERE nombre_usuario = 'admin'");
$count_admin = $check_admin ? $check_admin->fetch_assoc()['count'] : 0;
if ($count_admin == 0) {
    $stmt_admin = $conn->prepare("INSERT INTO usuarios_admin (nombre_usuario, contrasena_hash, nombre_completo, email) VALUES (?, ?, ?, ?)");
    $stmt_admin->bind_param("ssss", $admin_user, $admin_pass_hash, $admin_nombre, $admin_email);
    $stmt_admin->execute();
    $stmt_admin->close();
}
?>