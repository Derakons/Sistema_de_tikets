<?php
require_once 'core/config.php'; // Para la conexión $conn y session_start()

// Intentar crear la base de datos si no existe (solo si estamos en setup.php)
if ($conn->connect_error && basename($_SERVER['PHP_SELF']) == 'setup.php') {
    // Esto podría ocurrir si config.php intentó conectar a una BD que no existe y murió antes,
    // pero la conexión al *servidor* MySQL debería estar viva si config.php pasó ese punto.
    // Re-establecer conexión solo al servidor para crear la BD.
    $conn_temp = new mysqli(DB_HOST, DB_USER, DB_PASS);
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
    <!-- CSS Unificado del Sistema de Tickets -->
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body class="setup-body">
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
        echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: No se pudo conectar al servidor MySQL para crear la base de datos. Verifique sus credenciales en config.php. Error: " . $conn_temp->connect_error . "</div>";
        exit;
    }
    $conn = $conn_temp;
    $db_created_in_setup = true;
} else if ($conn->connect_error) {
    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: Problema de conexión con la base de datos. Error: " . $conn->connect_error . "</div>";
    exit;
}

$db_check_query = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");

if ($db_check_query && $db_check_query->num_rows == 0) {
    echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>La base de datos '<strong>" . DB_NAME . "</strong>' no existe. Intentando crearla...</div>";
    $sql_create_db = "CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;";
    if ($conn->query($sql_create_db) === TRUE) {
        echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Base de datos '<strong>" . DB_NAME . "</strong>' creada exitosamente.</div>";
        if (!$conn->select_db(DB_NAME)) {
            echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: No se pudo seleccionar la base de datos '<strong>" . DB_NAME . "</strong>' después de crearla. " . $conn->error . "</div>";
            exit;
        }
        if (!$conn->set_charset("utf8mb4")) {
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i>Advertencia: No se pudo establecer el charset utf8mb4 para la nueva base de datos.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: No se pudo crear la base de datos '<strong>" . DB_NAME . "</strong>'. Verifique los permisos del usuario MySQL. Error: " . $conn->error . "</div>";
        exit;
    }
} else if (!$db_check_query) {
     echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: No se pudo verificar la existencia de la base de datos '<strong>" . DB_NAME . "</strong>'. Error: " . $conn->error . "</div>";
     exit;
} else {
    if (!$conn->select_db(DB_NAME)) { // Asegurar selección si ya existía pero config.php no la seleccionó
         echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error fatal: La base de datos '<strong>" . DB_NAME . "</strong>' existe pero no se pudo seleccionar. " . $conn->error . "</div>";
         exit;
    }
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Base de datos '<strong>" . DB_NAME . "</strong>' ya existe y está seleccionada.</div>";
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
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Tabla '<strong>departamentos</strong>' creada exitosamente o ya existente.</div>";

    // Insertar departamentos iniciales si la tabla está vacía
    $check_dept = $conn->query("SELECT COUNT(*) as count FROM departamentos");
    $count_dept = $check_dept->fetch_assoc()['count'];
    if ($count_dept == 0) {
        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>Insertando departamentos iniciales...</div>";
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
                echo "<div class='alert alert-success'><i class='fas fa-check'></i>Departamento '<strong>" . htmlspecialchars($dept['nombre_departamento']) . "</strong>' insertado.</div>";
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-times'></i>Error insertando departamento '<strong>" . htmlspecialchars($dept['nombre_departamento']) . "</strong>': " . $stmt_dept->error . "</div>";
            }
        }
        $stmt_dept->close();
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>La tabla '<strong>departamentos</strong>' ya contiene datos.</div>";
    }
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>departamentos</strong>': " . $conn->error . "</div>";
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
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Tabla '<strong>usuarios_admin</strong>' creada exitosamente o ya existente.</div>";

    // Insertar usuario administrador por defecto si la tabla está vacía
    $check_admin = $conn->query("SELECT COUNT(*) as count FROM usuarios_admin");
    $count_admin = $check_admin->fetch_assoc()['count'];
    if ($count_admin == 0) {
        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>Insertando usuario administrador por defecto...</div>";
        $admin_user = 'admin';
        $admin_pass_plain = 'admin123'; // ¡CAMBIAR ESTA CONTRASEÑA INMEDIATAMENTE!
        $admin_pass_hash = password_hash($admin_pass_plain, PASSWORD_DEFAULT);
        $admin_nombre = 'Administrador Principal';
        $admin_email = 'admin@example.com';

        $stmt_admin = $conn->prepare("INSERT INTO usuarios_admin (nombre_usuario, contrasena_hash, nombre_completo, email) VALUES (?, ?, ?, ?)");
        $stmt_admin->bind_param("ssss", $admin_user, $admin_pass_hash, $admin_nombre, $admin_email);
        if ($stmt_admin->execute()) {
            echo "<div class='alert alert-success'><i class='fas fa-user-plus'></i>Usuario administrador por defecto ('<strong>" . htmlspecialchars($admin_user) . "</strong>') creado.</div>";
            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i><strong>IMPORTANTE:</strong> La contraseña por defecto para el usuario '<strong>" . htmlspecialchars($admin_user) . "</strong>' es '<strong>" . htmlspecialchars($admin_pass_plain) . "</strong>'. ¡DEBES CAMBIARLA INMEDIATAMENTE por seguridad!</div>";
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-times'></i>Error creando usuario administrador por defecto: " . $stmt_admin->error . "</div>";
        }
        $stmt_admin->close();
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>La tabla '<strong>usuarios_admin</strong>' ya contiene datos.</div>";
    }

} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>usuarios_admin</strong>': " . $conn->error . "</div>";
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
    echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Tabla '<strong>tickets</strong>' creada exitosamente o ya existente.</div>";

    // Verificar y añadir columnas que puedan faltar
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
                echo "<div class='alert alert-success'><i class='fas fa-plus-circle'></i>Columna '<strong>$column</strong>' añadida/actualizada en la tabla 'tickets'.</div>";
            } else {
                echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error añadiendo/actualizando columna '<strong>$column</strong>' a 'tickets': " . $conn->error . "</div>";
            }
        }
    }

    // Verificar índices
    $index_check_sql = "SHOW INDEX FROM `tickets` WHERE Key_name = 'idx_calificacion_usuario'";
    $index_result = $conn->query($index_check_sql);
    if ($index_result && $index_result->num_rows == 0) {
        $alter_index_sql = "ALTER TABLE `tickets` ADD INDEX `idx_calificacion_usuario` (`calificacion_usuario`)";
        if ($conn->query($alter_index_sql) === TRUE) {
            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i>Índice '<strong>idx_calificacion_usuario</strong>' añadido a la tabla 'tickets'.</div>";
        } else {
            echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error añadiendo índice '<strong>idx_calificacion_usuario</strong>' a 'tickets': " . $conn->error . "</div>";
        }
    }

    // Verificar si insertar tickets de ejemplo
    $check_tickets = $conn->query("SELECT COUNT(*) as count FROM tickets");
    $count_tickets = $check_tickets->fetch_assoc()['count'];
    if ($count_tickets == 0) {
        $insertar_ejemplos_confirmado = false;
        
        if (isset($_REQUEST['confirm_insert_examples'])) {
            if ($_REQUEST['confirm_insert_examples'] === 'yes') {
                $insertar_ejemplos_confirmado = true;
            } elseif ($_REQUEST['confirm_insert_examples'] === 'no') {
                echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>No se insertarán tickets de ejemplo según su elección.</div>";
            }
        }

        if (!isset($_REQUEST['confirm_insert_examples'])) {
            echo "<div class='setup-step'>";
            echo "<h3><i class='fas fa-clipboard-list'></i> Tickets de Ejemplo</h3>";
            echo "<p>La tabla '<strong>tickets</strong>' está vacía. ¿Desea insertar 20 tickets de ejemplo para demostración?</p>";
            
            $setup_url = htmlspecialchars($_SERVER['PHP_SELF']);
            echo "<div class='d-flex gap-2'>";
            echo "<form method='POST' action='" . $setup_url . "' class='d-inline'>";
            echo "<input type='hidden' name='confirm_insert_examples' value='yes'>";
            echo "<button type='submit' class='btn btn-success'><i class='fas fa-check'></i> Sí, insertar ejemplos</button>";
            echo "</form>";
            echo "<form method='POST' action='" . $setup_url . "' class='d-inline'>";
            echo "<input type='hidden' name='confirm_insert_examples' value='no'>";
            echo "<button type='submit' class='btn btn-secondary'><i class='fas fa-times'></i> No, gracias</button>";
            echo "</form>";
            echo "</div>";
            echo "</div>";
        } elseif ($insertar_ejemplos_confirmado) {
            echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>Insertando 20 tickets de ejemplo...</div>";
            
            // Insertar ejemplos de tickets
            $ejemplos = [
                ["TICKET-0001", "Juan Pérez", 1, "Consulta de trámite", "Necesito información sobre el estado de mi trámite.", "Abierto", "Medio", null, null, null, null, null, null],
                ["TICKET-0002", "Ana Torres", 2, "Problema con impresora", "La impresora no imprime desde ayer.", "Cerrado", "Grave", "Hardware", "Fallo en cartucho de tinta", "Reemplazo de cartucho.", "La atención fue rápida, pero la impresora sigue fallando a veces.", 3, "2024-05-20 10:00:00"],
                ["TICKET-0003", "Carlos Ruiz", 3, "Pago de arbitrios", "No puedo pagar mis arbitrios en línea.", "Resuelto", "Alto", "Software", "Error en pasarela de pago", "Se actualizó el módulo de pagos.", "Solucionado eficientemente.", 5, "2024-05-19 15:30:00"],
                ["TICKET-0004", "María López", 4, "Licencia de construcción", "¿Cuáles son los requisitos para licencia?", "Abierto", "Bajo", null, null, null, null, null, null],
                ["TICKET-0005", "Pedro Sánchez", 5, "Alumbrado público", "Foco quemado en mi calle.", "Cerrado", "Leve", "Infraestructura", "Foco dañado", "Cambio de foco.", "Muy buen servicio, lo arreglaron el mismo día.", 5, "2024-05-18 11:00:00"]
            ];
            
            $stmt_ticket = $conn->prepare("INSERT INTO tickets (id, nombre_solicitante, id_departamento, asunto, descripcion, estado, prioridad, tipo_averia, diagnostico_admin, solucion_admin, fecha_creacion, comentario_usuario, calificacion_usuario, fecha_feedback) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)");
            
            foreach ($ejemplos as $ej) {
                $fecha_feedback = $ej[12] ?: null;
                $stmt_ticket->bind_param("ssissssssssis", 
                    $ej[0], $ej[1], $ej[2], $ej[3], $ej[4], 
                    $ej[5], $ej[6], $ej[7], $ej[8], $ej[9], 
                    $ej[10], $ej[11], $fecha_feedback
                );

                if ($stmt_ticket->execute()) {
                    echo "<div class='alert alert-success'><i class='fas fa-ticket-alt'></i>Ticket de ejemplo '<strong>" . htmlspecialchars($ej[0]) . "</strong>' insertado.</div>";
                } else {
                    echo "<div class='alert alert-danger'><i class='fas fa-times'></i>Error insertando ticket de ejemplo '<strong>" . htmlspecialchars($ej[0]) . "</strong>': " . $stmt_ticket->error . "</div>";
                }
            }
            $stmt_ticket->close();
        }
    } else {
        echo "<div class='alert alert-info'><i class='fas fa-info-circle'></i>La tabla '<strong>tickets</strong>' ya contiene datos. No se requiere inserción de ejemplos.</div>";
    }
} else {
    echo "<div class='alert alert-danger'><i class='fas fa-times-circle'></i>Error creando tabla '<strong>tickets</strong>': " . $conn->error . "</div>";
}
echo "</div>"; // Fin setup-step Tickets

// --- FIN DE PROCESO ---
echo "<div class='setup-step'>";
echo "<h2><i class='fas fa-check-double'></i> Proceso de Configuración Completado</h2>";
echo "<div class='alert alert-success'><i class='fas fa-thumbs-up'></i>El sistema ha sido configurado exitosamente. Si es la primera vez que ejecutas esto, revisa los mensajes anteriores para confirmar que todo se creó correctamente.</div>";

echo "<div class='d-flex gap-2 mt-4'>";
echo "<a href='index.php' class='btn btn-primary'><i class='fas fa-home'></i> Ir a la Página Principal</a>";
echo "<a href='admin/login.php' class='btn btn-secondary'><i class='fas fa-user-shield'></i> Ir al Login de Admin</a>";
echo "</div>";
echo "</div>";

$conn->close();
?>
    </div> <!-- Fin .container-setup -->
    
    <!-- Scripts de Bootstrap -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
