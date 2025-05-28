<?php
require_once 'core/config.php'; // Para la conexión $conn y session_start()

// Intentar crear la base de datos si no existe (solo si estamos en setup.php)
if ($conn->connect_error && basename($_SERVER['PHP_SELF']) == 'setup.php') {
    // Esto podría ocurrir si config.php intentó conectar a una BD que no existe y murió antes,
    // pero la conexión al *servidor* MySQL debería estar viva si config.php pasó ese punto.
    // Re-establecer conexión solo al servidor para crear la BD.
    $conn_temp = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD);
    if ($conn_temp->connect_error) {
        die("Error fatal: No se pudo conectar al servidor MySQL para crear la base de datos. Verifique sus credenciales en config.php. Error: " . $conn_temp->connect_error);
    }
    $conn = $conn_temp; // Usar esta conexión temporal para crear la BD
    $db_created_in_setup = true;
} else if ($conn->connect_error) {
    // Si hay error de conexión y no estamos en setup.php, es un error fatal.
    die("Error fatal: Problema de conexión con la base de datos. Error: " . $conn->connect_error);
}

// Verificar si la base de datos existe y crearla si no
$db_check_query = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");

if ($db_check_query && $db_check_query->num_rows == 0) {
    echo "<p>La base de datos '" . DB_NAME . "' no existe. Intentando crearla...</p>";
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    if ($conn->query($sql_create_db) === TRUE) {
        echo "<p class='success-message'>Base de datos '" . DB_NAME . "' creada exitosamente.</p>";
        if (!$conn->select_db(DB_NAME)) {
            die("<p class='error-message'>Error fatal: No se pudo seleccionar la base de datos '" . DB_NAME . "' después de crearla. " . $conn->error . "</p>");
        }
        if (!$conn->set_charset("utf8mb4")) {
            echo "<p class='warning-message'>Advertencia: No se pudo establecer el charset utf8mb4 para la nueva base de datos.</p>";
        }
    } else {
        die("<p class='error-message'>Error fatal: No se pudo crear la base de datos '" . DB_NAME . "'. Verifique los permisos del usuario MySQL. Error: " . $conn->error . "</p>");
    }
} else if (!$db_check_query) {
    die("<p class='error-message'>Error fatal: No se pudo verificar la existencia de la base de datos '" . DB_NAME . "'. Error: " . $conn->error . "</p>");
} else {
    // La base de datos ya existe, asegurarse de que esté seleccionada.
    // Esto es importante si config.php no pudo seleccionarla porque aún no existía cuando se ejecutó por primera vez.
    if (!$conn->select_db(DB_NAME)) {
         die("<p class='error-message'>Error fatal: La base de datos '" . DB_NAME . "' existe pero no se pudo seleccionar. " . $conn->error . "</p>");
    }
    // Si la conexión ya tiene la BD seleccionada desde config.php, este select_db no hará daño.
    echo "<p class='success-message'>Base de datos '" . DB_NAME . "' ya existe y está seleccionada.</p>";
}

$page_title = "Configuración del Sistema de Tickets";
// No incluir el header.php estándar aquí para tener control total del HTML de configuración.
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #0066cc;
            --secondary-color: #28a745;
            --tertiary-color: #6c757d;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --success-bg: #d4edda;
            --success-text: #155724;
            --success-border: #c3e6cb;
            --error-bg: #f8d7da;
            --error-text: #721c24;
            --error-border: #f5c6cb;
            --warning-bg: #fff3cd;
            --warning-text: #856404;
            --warning-border: #ffeeba;
            --info-bg: #cce5ff;
            --info-text: #004085;
            --info-border: #b8daff;
            --font-family-sans-serif: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji";
            --border-radius: .375rem;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .15);
        }
        body {
            font-family: var(--font-family-sans-serif);
            line-height: 1.6;
            color: var(--dark-color);
            background-color: #eef2f7; /* Un fondo ligeramente gris */
            margin: 0;
            padding: 20px;
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Alinea al inicio para scrolls largos */
            min-height: 100vh;
        }
        .container-setup {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            width: 100%;
            max-width: 900px; /* Ancho máximo para el contenido */
            margin-top: 20px;
            margin-bottom: 20px;
        }
        h1, h2, h3 {
            color: var(--primary-color);
            margin-bottom: 0.8rem;
        }
        h1 {
            font-size: 2rem;
            border-bottom: 2px solid #eee;
            padding-bottom: 0.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        h1 i {
            margin-right: 15px;
            font-size: 2.2rem;
        }
        h2 {
            font-size: 1.5rem;
            margin-top: 2rem;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 0.4rem;
        }
        h2 i {
            margin-right: 10px;
        }
        hr {
            border: 0;
            height: 1px;
            background-color: #ddd;
            margin: 2rem 0;
        }
        p {
            margin-bottom: 0.8rem;
            font-size: 0.95rem;
        }
        .message {
            padding: 1rem 1.5rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
            border-radius: var(--border-radius);
            display: flex;
            align-items: center;
            font-size: 0.9rem;
        }
        .message i {
            margin-right: 10px;
            font-size: 1.2rem;
        }
        .success-message {
            color: var(--success-text);
            background-color: var(--success-bg);
            border-color: var(--success-border);
        }
        .error-message {
            color: var(--error-text);
            background-color: var(--error-bg);
            border-color: var(--error-border);
        }
        .warning-message {
            color: var(--warning-text);
            background-color: var(--warning-bg);
            border-color: var(--warning-border);
        }
        .info-message {
            color: var(--info-text);
            background-color: var(--info-bg);
            border-color: var(--info-border);
        }
        .btn {
            display: inline-flex; /* Para alinear íconos y texto */
            align-items: center;
            gap: 8px; /* Espacio entre ícono y texto */
            padding: 0.6rem 1.2rem;
            font-size: 0.95rem;
            font-weight: 500;
            text-decoration: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease-in-out;
        }
        .btn-primary {
            color: #fff;
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .btn-secondary {
            color: #fff;
            background-color: var(--tertiary-color);
            border-color: var(--tertiary-color);
        }
        .btn-secondary:hover {
            background-color: #5a6268;
            border-color: #545b62;
        }
        .btn-success {
            color: #fff;
            background-color: var(--secondary-color);
            border-color: var(--secondary-color);
        }
        .btn-success:hover {
            background-color: #1e7e34;
            border-color: #1c7430;
        }
        .btn-danger {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .action-buttons {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px; /* Espacio entre botones */
        }
        .form-confirmation {
            margin-top: 1rem;
            margin-bottom: 1.5rem;
            padding: 1.5rem;
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
        }
        .form-confirmation p {
            margin-bottom: 1rem;
            font-size: 1rem;
        }
        .form-confirmation form {
            display: inline-block;
            margin-right: 10px;
        }
        .code-block {
            background-color: #f1f1f1;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 0.85em;
            overflow-x: auto;
            margin-bottom: 10px;
        }
        .setup-step {
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px dashed #ddd;
        }
        .setup-step:last-of-type {
            border-bottom: none;
        }
    </style>
</head>
<body>
    <div class="container-setup">
        <h1><i class="fas fa-cogs"></i> Configuración Inicial del Sistema</h1>

<?php
// --- INICIO LÓGICA PHP DE SETUP (adaptada) ---

// Intentar crear la base de datos si no existe
// ... (código de creación de BD, adaptando mensajes como se indicó arriba) ...
$db_created_in_setup = false; // Para rastrear si la BD se creó en esta ejecución

if ($conn->connect_error && basename($_SERVER['PHP_SELF']) == 'setup.php') {
    $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn_temp->connect_error) {
        echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: No se pudo conectar al servidor MySQL para crear la base de datos. Verifique sus credenciales en config.php. Error: " . $conn_temp->connect_error . "</div>";
        exit;
    }
    $conn = $conn_temp;
    $db_created_in_setup = true;
} else if ($conn->connect_error) {
    echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: Problema de conexión con la base de datos. Error: " . $conn->connect_error . "</div>";
    exit;
}

$db_check_query = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");

if ($db_check_query && $db_check_query->num_rows == 0) {
    echo "<div class='message info-message'><i class='fas fa-info-circle'></i>La base de datos '<strong>" . DB_NAME . "</strong>' no existe. Intentando crearla...</div>";
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    if ($conn->query($sql_create_db) === TRUE) {
        echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Base de datos '<strong>" . DB_NAME . "</strong>' creada exitosamente.</div>";
        if (!$conn->select_db(DB_NAME)) {
            echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: No se pudo seleccionar la base de datos '<strong>" . DB_NAME . "</strong>' después de crearla. " . $conn->error . "</div>";
            exit;
        }
        if (!$conn->set_charset("utf8mb4")) {
            echo "<div class='message warning-message'><i class='fas fa-exclamation-triangle'></i>Advertencia: No se pudo establecer el charset utf8mb4 para la nueva base de datos.</div>";
        }
    } else {
        echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: No se pudo crear la base de datos '<strong>" . DB_NAME . "</strong>'. Verifique los permisos del usuario MySQL. Error: " . $conn->error . "</div>";
        exit;
    }
} else if (!$db_check_query) {
     echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: No se pudo verificar la existencia de la base de datos '<strong>" . DB_NAME . "</strong>'. Error: " . $conn->error . "</div>";
     exit;
} else {
    if (!$conn->select_db(DB_NAME)) { // Asegurar selección si ya existía pero config.php no la seleccionó
         echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error fatal: La base de datos '<strong>" . DB_NAME . "</strong>' existe pero no se pudo seleccionar. " . $conn->error . "</div>";
         exit;
    }
    echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Base de datos '<strong>" . DB_NAME . "</strong>' ya existe y está seleccionada.</div>";
}


// --- 1. CREAR TABLA DEPARTAMENTOS ---
echo "<div class='setup-step'>";
echo "<h2><i class='fas fa-building'></i> Tabla de Departamentos</h2>";
$sql_departamentos = "CREATE TABLE IF NOT EXISTS departamentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre_departamento VARCHAR(150) NOT NULL UNIQUE,
    descripcion TEXT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_departamentos) === TRUE) {
    echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Tabla '<strong>departamentos</strong>' creada exitosamente o ya existente.</div>";

    // Insertar departamentos iniciales si la tabla está vacía
    $check_dept = $conn->query("SELECT COUNT(*) as count FROM departamentos");
    $count_dept = $check_dept->fetch_assoc()['count'];
    if ($count_dept == 0) {
        echo "<div class='message info-message'><i class='fas fa-info-circle'></i>Insertando departamentos iniciales...</div>";
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
                echo "<div class='message success-message'><i class='fas fa-check'></i>Departamento '<strong>" . htmlspecialchars($dept['nombre_departamento']) . "</strong>' insertado.</div>";
            } else {
                echo "<div class='message error-message'><i class='fas fa-times'></i>Error insertando departamento '<strong>" . htmlspecialchars($dept['nombre_departamento']) . "</strong>': " . $stmt_dept->error . "</div>";
            }
        }
        $stmt_dept->close();
    } else {
        echo "<div class='message info-message'><i class='fas fa-info-circle'></i>La tabla '<strong>departamentos</strong>' ya contiene datos.</div>";
    }
} else {
    echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>departamentos</strong>': " . $conn->error . "</div>";
}
echo "</div>"; // Fin setup-step Departamentos

// --- 2. CREAR TABLA USUARIOS_ADMIN ---
echo "<div class='setup-step'>";
echo "<h2><i class='fas fa-users-cog'></i> Tabla de Usuarios Administradores</h2>";
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
    echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Tabla '<strong>usuarios_admin</strong>' creada exitosamente o ya existente.</div>";

    // Insertar usuario administrador por defecto si la tabla está vacía
    $check_admin = $conn->query("SELECT COUNT(*) as count FROM usuarios_admin");
    $count_admin = $check_admin->fetch_assoc()['count'];
    if ($count_admin == 0) {
        echo "<div class='message info-message'><i class='fas fa-info-circle'></i>Insertando usuario administrador por defecto...</div>";
        $admin_user = 'admin';
        $admin_pass_plain = 'admin123'; // ¡CAMBIAR ESTA CONTRASEÑA INMEDIATAMENTE!
        $admin_pass_hash = password_hash($admin_pass_plain, PASSWORD_DEFAULT);
        $admin_nombre = 'Administrador Principal';
        $admin_email = 'admin@example.com';

        $stmt_admin = $conn->prepare("INSERT INTO usuarios_admin (nombre_usuario, contrasena_hash, nombre_completo, email) VALUES (?, ?, ?, ?)");
        $stmt_admin->bind_param("ssss", $admin_user, $admin_pass_hash, $admin_nombre, $admin_email);
        if ($stmt_admin->execute()) {
            echo "<div class='message success-message'><i class='fas fa-user-plus'></i>Usuario administrador por defecto ('<strong>" . htmlspecialchars($admin_user) . "</strong>') creado.</div>";
            echo "<div class='message warning-message'><i class='fas fa-exclamation-triangle'></i><strong>IMPORTANTE:</strong> La contraseña por defecto para el usuario '<strong>" . htmlspecialchars($admin_user) . "</strong>' es '<strong>" . htmlspecialchars($admin_pass_plain) . "</strong>'. ¡DEBES CAMBIARLA INMEDIATAMENTE por seguridad!</div>";
        } else {
            echo "<div class='message error-message'><i class='fas fa-times'></i>Error creando usuario administrador por defecto: " . $stmt_admin->error . "</div>";
        }
        $stmt_admin->close();
    } else {
        echo "<div class='message info-message'><i class='fas fa-info-circle'></i>La tabla '<strong>usuarios_admin</strong>' ya contiene datos.</div>";
    }

} else {
    echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>usuarios_admin</strong>': " . $conn->error . "</div>";
}
echo "</div>"; // Fin setup-step Usuarios Admin

// --- 3. CREAR TABLA TICKETS ---
echo "<div class='setup-step'>";
echo "<h2><i class='fas fa-ticket-alt'></i> Tabla de Tickets</h2>";
$sql_tickets = "CREATE TABLE IF NOT EXISTS tickets (
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
    tipo_averia VARCHAR(100) NULL DEFAULT NULL,
    diagnostico_admin TEXT NULL DEFAULT NULL,
    solucion_admin TEXT NULL DEFAULT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    id_admin_asignado INT NULL,
    ip_solicitante VARCHAR(45) NULL,
    notas_internas TEXT NULL,
    fecha_resolucion TIMESTAMP NULL,
    fecha_cierre TIMESTAMP NULL,
    comentario_usuario TEXT NULL DEFAULT NULL,
    calificacion_usuario TINYINT UNSIGNED NULL DEFAULT NULL,
    fecha_feedback TIMESTAMP NULL DEFAULT NULL,
    FOREIGN KEY (id_departamento) REFERENCES departamentos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (id_admin_asignado) REFERENCES usuarios_admin(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_estado (estado),
    INDEX idx_fecha_creacion (fecha_creacion),
    INDEX idx_fecha_cierre (fecha_cierre),
    INDEX idx_calificacion_usuario (calificacion_usuario)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if ($conn->query($sql_tickets) === TRUE) {
    echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Tabla '<strong>tickets</strong>' creada exitosamente o ya existente.</div>";

    $all_columns_to_check = [
        'comentario_usuario' => 'TEXT NULL DEFAULT NULL AFTER fecha_cierre',
        'calificacion_usuario' => 'TINYINT UNSIGNED NULL DEFAULT NULL AFTER comentario_usuario',
        'fecha_feedback' => 'TIMESTAMP NULL DEFAULT NULL AFTER calificacion_usuario',
        'tipo_averia' => 'VARCHAR(100) NULL DEFAULT NULL AFTER prioridad',
        'diagnostico_admin' => 'TEXT NULL DEFAULT NULL AFTER tipo_averia',
        'solucion_admin' => 'TEXT NULL DEFAULT NULL AFTER diagnostico_admin'
    ];

    $existing_columns_result = $conn->query("SHOW COLUMNS FROM `tickets`");
    $current_columns = [];
    if ($existing_columns_result) {
        while ($row = $existing_columns_result->fetch_assoc()) {
            $current_columns[] = $row['Field'];
        }
    }

    foreach ($all_columns_to_check as $column => $type_and_position) {
        if (!in_array($column, $current_columns)) {
            $add_col_sql = "ALTER TABLE `tickets` ADD COLUMN `$column` $type_and_position";
            if ($conn->query($add_col_sql) === TRUE) {
                echo "<div class='message success-message'><i class='fas fa-plus-circle'></i>Columna '<strong>$column</strong>' añadida/actualizada en la tabla 'tickets'.</div>";
            } else {
                echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error añadiendo/actualizando columna '<strong>$column</strong>' a 'tickets': " . $conn->error . "</div>";
            }
        }
    }

    $index_check_sql = "SHOW INDEX FROM `tickets` WHERE Key_name = 'idx_calificacion_usuario'";
    $index_result = $conn->query($index_check_sql);
    if ($index_result && $index_result->num_rows == 0) {
        $alter_index_sql = "ALTER TABLE `tickets` ADD INDEX `idx_calificacion_usuario` (`calificacion_usuario`)";
        if ($conn->query($alter_index_sql) === TRUE) {
            echo "<div class='message success-message'><i class='fas fa-check-circle'></i>Índice '<strong>idx_calificacion_usuario</strong>' añadido a la tabla 'tickets'.</div>";
        } else {
            echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error añadiendo índice '<strong>idx_calificacion_usuario</strong>' a 'tickets': " . $conn->error . "</div>";
        }
    }

    $check_tickets = $conn->query("SELECT COUNT(*) as count FROM tickets");
    $count_tickets = $check_tickets->fetch_assoc()['count'];
    if ($count_tickets == 0) {
        $insertar_ejemplos_confirmado = false;
        // Usar $_REQUEST para capturar tanto GET como POST
        if (isset($_REQUEST['confirm_insert_examples'])) {
            if ($_REQUEST['confirm_insert_examples'] === 'yes') {
                $insertar_ejemplos_confirmado = true;
            } elseif ($_REQUEST['confirm_insert_examples'] === 'no') {
                echo "<div class='message info-message'><i class='fas fa-info-circle'></i>No se insertarán tickets de ejemplo según su elección.</div>";
            }
        }

        if (!isset($_REQUEST['confirm_insert_examples'])) {
            // Mostrar formulario/opciones solo si no se ha tomado una decisión aún
            echo "<div class='form-confirmation'>";
            echo "<h3><i class='fas fa-clipboard-list'></i> Tickets de Ejemplo</h3>";
            echo "<p>La tabla '<strong>tickets</strong>' está vacía. ¿Desea insertar 20 tickets de ejemplo para demostración?</p>";
            // Usar la URL actual para el action, añadiendo el parámetro
            $setup_url = htmlspecialchars($_SERVER['PHP_SELF']);
            echo "<form method='POST' action='" . $setup_url . "'>";
            echo "<input type='hidden' name='confirm_insert_examples' value='yes'>";
            echo "<button type='submit' class='btn btn-success'><i class='fas fa-check'></i> Sí, insertar ejemplos</button>";
            echo "</form>";
            echo "<form method='POST' action='" . $setup_url . "'>";
            echo "<input type='hidden' name='confirm_insert_examples' value='no'>";
            echo "<button type='submit' class='btn btn-danger'><i class='fas fa-times'></i> No, gracias</button>";
            echo "</form>";
            echo "</div>";
        } elseif ($insertar_ejemplos_confirmado) {
            echo "<div class='message info-message'><i class='fas fa-info-circle'></i>Insertando 20 tickets de ejemplo...</div>";
            // Definición de ejemplos actualizada para incluir tipo_averia, diagnostico_admin, solucion_admin
            // y omitir dni, telefono, email (serán NULL)
            $ejemplos = [
                // id, nombre, id_dept, asunto, desc, estado, prioridad, tipo_averia, diagnostico_admin, solucion_admin, comentario_usr, calificacion_usr, fecha_feedback_usr
                ["TICKET-0001", "Juan Pérez", 1, "Consulta de trámite", "Necesito información sobre el estado de mi trámite.", "Abierto", "Medio", null, null, null, null, null, null],
                ["TICKET-0002", "Ana Torres", 2, "Problema con impresora", "La impresora no imprime desde ayer.", "Cerrado", "Grave", "Hardware", "Fallo en cartucho de tinta", "Reemplazo de cartucho.", "La atención fue rápida, pero la impresora sigue fallando a veces.", 3, "2024-05-20 10:00:00"],
                ["TICKET-0003", "Carlos Ruiz", 3, "Pago de arbitrios", "No puedo pagar mis arbitrios en línea.", "Resuelto", "Alto", "Software", "Error en pasarela de pago", "Se actualizó el módulo de pagos.", "Solucionado eficientemente.", 5, "2024-05-19 15:30:00"],
                ["TICKET-0004", "María López", 4, "Licencia de construcción", "¿Cuáles son los requisitos para licencia?", "Abierto", "Bajo", null, null, null, null, null, null],
                ["TICKET-0005", "Pedro Sánchez", 5, "Alumbrado público", "Foco quemado en mi calle.", "Cerrado", "Leve", "Infraestructura", "Foco dañado", "Cambio de foco.", "Muy buen servicio, lo arreglaron el mismo día.", 5, "2024-05-18 11:00:00"],
                ["TICKET-0006", "Lucía Gómez", 6, "Solicitud de audiencia", "Quiero una cita con el alcalde.", "Abierto", "Medio", null, null, null, null, null, null],
                ["TICKET-0007", "Miguel Castro", 7, "Documentos extraviados", "Perdí un documento presentado.", "Resuelto", "Grave", "Procedimiento", "Documento no encontrado inicialmente", "Búsqueda exhaustiva y localización.", null, null, null],
                ["TICKET-0008", "Sofía Vargas", 1, "Demora en atención", "No responden mi solicitud.", "Abierto", "Alto", null, null, null, null, null, null],
                ["TICKET-0009", "Luis Fernández", 2, "Computadora lenta", "La PC tarda mucho en iniciar.", "Cerrado", "Leve", "Software", "Exceso de programas al inicio", "Optimización de inicio.", "El técnico fue amable y resolvió el problema.", 4, "2024-05-17 14:20:00"],
                ["TICKET-0010", "Elena Ríos", 3, "Error en recibo", "Mi recibo tiene un monto incorrecto.", "Abierto", "Medio", null, null, null, null, null, null],
                ["TICKET-0011", "Jorge Salas", 4, "Consulta de planos", "¿Dónde puedo ver los planos urbanos?", "Resuelto", "Bajo", "Información", "Consulta resuelta", "Se proporcionó enlace a catastro digital.", null, null, null],
                ["TICKET-0012", "Patricia León", 5, "Basura acumulada", "No recogen la basura hace días.", "Cerrado", "Alto", "Servicio Público", "Retraso en ruta de recolección", "Recolección efectuada y reprogramación.", "El problema fue resuelto, pero tardaron varios días en responder.", 2, "2024-05-16 09:15:00"],
                ["TICKET-0013", "Ricardo Díaz", 6, "Solicitud de información", "Requiero información sobre trámites.", "Abierto", "Medio", null, null, null, null, null, null],
                ["TICKET-0014", "Gabriela Mena", 7, "Problema con usuario web", "No puedo acceder a mi cuenta.", "Cerrado", "Grave", "Software", "Contraseña bloqueada", "Restablecimiento de contraseña.", "Todo bien.", 4, "2024-05-23 11:00:00"],
                ["TICKET-0015", "Fernando Paredes", 1, "Consulta de expediente", "¿Cómo consulto mi expediente?", "Abierto", "Bajo", null, null, null, null, null, null],
                ["TICKET-0016", "Carmen Silva", 2, "Fallo de red", "No hay internet en la oficina.", "Resuelto", "Muy Grave", "Redes", "Switch defectuoso", "Reemplazo de switch.", "Excelente servicio, muy rápido y efectivo.", 5, "2024-05-15 18:00:00"],
                ["TICKET-0017", "Oscar Medina", 3, "Pago duplicado", "Realicé dos pagos por error.", "Cerrado", "Medio", "Financiero", "Error del usuario", "Se gestionó la devolución de un pago.", null, null, null],
                ["TICKET-0018", "Valeria Torres", 4, "Consulta de zonificación", "¿Qué zonificación tiene mi predio?", "Abierto", "Bajo", null, null, null, null, null, null],
                ["TICKET-0019", "Hugo Ramos", 5, "Parque sin mantenimiento", "El parque está descuidado.", "Cerrado", "Leve", "Servicio Público", "Falta de personal temporal", "Mantenimiento realizado.", "Regular, se demoraron un poco.", 3, "2024-05-14 12:00:00"],
                ["TICKET-0020", "Natalia Quispe", 6, "Solicitud de reunión", "Necesito reunirme con el secretario.", "Abierto", "Medio", null, null, null, null, null, null]
            ];
            $stmt_ticket = $conn->prepare("INSERT INTO tickets (id, nombre_solicitante, id_departamento, asunto, descripcion, estado, prioridad, tipo_averia, diagnostico_admin, solucion_admin, fecha_creacion, comentario_usuario, calificacion_usuario, fecha_feedback) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
            foreach ($ejemplos as $idx => $ej) {
                // Asignación directa desde el array $ej, que ya está estructurado correctamente
                $id_ticket_ejemplo      = $ej[0];
                $nombre_solicitante     = $ej[1];
                $id_departamento        = $ej[2];
                $asunto                 = $ej[3];
                $descripcion            = $ej[4];
                $estado                 = $ej[5];
                $prioridad              = $ej[6];
                $tipo_averia            = $ej[7]; // Puede ser null
                $diagnostico_admin      = $ej[8]; // Puede ser null
                $solucion_admin         = $ej[9]; // Puede ser null
                $comentario_usuario     = $ej[10]; // Puede ser null
                $calificacion_usuario   = $ej[11]; // Puede ser null
                $fecha_feedback         = $ej[12]; // Puede ser null

                if (empty($fecha_feedback)) {
                    $fecha_feedback = null;
                }

                $stmt_ticket->bind_param("ssissssssssis", 
                    $id_ticket_ejemplo, $nombre_solicitante, $id_departamento, $asunto, $descripcion, 
                    $estado, $prioridad, $tipo_averia, $diagnostico_admin, $solucion_admin, 
                    $comentario_usuario, $calificacion_usuario, $fecha_feedback
                );

                if ($stmt_ticket->execute()) {
                    echo "<div class='message success-message'><i class='fas fa-ticket-alt'></i>Ticket de ejemplo '<strong>" . htmlspecialchars($id_ticket_ejemplo) . "</strong>' insertado.</div>";
                } else {
                    echo "<div class='message error-message'><i class='fas fa-times'></i>Error insertando ticket de ejemplo '<strong>" . htmlspecialchars($id_ticket_ejemplo) . "</strong>': " . $stmt_ticket->error . "</div>";
                }
            }
            $stmt_ticket->close();
        } elseif ((isset($_POST['confirm_insert_examples']) && $_POST['confirm_insert_examples'] === 'no') || (isset($_GET['insert_examples']) && $_GET['insert_examples'] === 'no')) {
             // Ya se mostró mensaje arriba
        }
    } else {
        echo "<div class='message info-message'><i class='fas fa-info-circle'></i>La tabla '<strong>tickets</strong>' ya contiene datos. No se requiere inserción de ejemplos.</div>";
    }
} else {
    echo "<div class='message error-message'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>tickets</strong>': " . $conn->error . "</div>";
}
echo "</div>"; // Fin setup-step Tickets

// --- FIN DE PROCESO ---
echo "<div class='setup-step'>"; // Usar setup-step para consistencia visual
echo "<h2><i class='fas fa-check-double'></i> Proceso de Configuración Completado</h2>";
echo "<div class='message success-message'><i class='fas fa-thumbs-up'></i>El sistema ha sido configurado. Si es la primera vez que ejecutas esto, revisa los mensajes anteriores para confirmar que todo se creó correctamente.</div>";

echo "<div class='action-buttons'>";
echo "<a href='index.php' class='btn btn-primary'><i class='fas fa-home'></i> Ir a la Página Principal</a>";
echo "<a href='admin/login.php' class='btn btn-secondary'><i class='fas fa-user-shield'></i> Ir al Login de Admin</a>";
echo "</div>";
echo "</div>";


$conn->close();
?>
    </div> <!-- Fin .container-setup -->
</body>
</html>
