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

// Configuración de la aplicación
// Asumiendo que config.php está en /core/, RUTA_APP es un nivel arriba.
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$script_dir = str_replace('\\' , '/', dirname($_SERVER['SCRIPT_NAME'])); // Directorio del script actual
// Si SCRIPT_NAME es /Sistema de tikets/core/algoscript.php, dirname es /Sistema de tikets/core
// Necesitamos /Sistema de tikets/
$app_path = dirname($script_dir);
if ($app_path === '/' || $app_path === '\\') {
    $app_path = ''; // Evitar doble barra si está en la raíz del host
}
// Asegurarse de que RUTA_APP_URL termine con una barra, incluso si $app_path está vacío (raíz del host)
$base_url = $protocol . $host . ($app_path ? $app_path . '/' : '/');
define('RUTA_APP_URL', $base_url);


// Conexión al servidor MySQL (sin seleccionar base de datos)
$conn = @new mysqli(DB_HOST, DB_USER, DB_PASS);
if ($conn->connect_error) {
    // Si la conexión al servidor falla, no podemos hacer mucho más.
    die("Conexión fallida con el servidor de base de datos: " . $conn->connect_error);
}

// Verificar si la base de datos existe
$db_exists_result = $conn->query("SHOW DATABASES LIKE '" . DB_NAME . "'");

if ($db_exists_result && $db_exists_result->num_rows == 0) {
    // La base de datos no existe
    if (basename($_SERVER['PHP_SELF']) != 'setup.php') {
        // No estamos en setup.php, así que redirigimos a setup.php
        // La redirección debe ser a la URL base + setup.php
        header('Location: ' . RUTA_APP_URL . 'setup.php?error=db_not_found');
        exit;
    }
    // Si estamos en setup.php, ese script se encargará de crear la BD.
    // No intentamos seleccionar la BD aquí porque fallaría y setup.php lo hará después de crearla.
} else if ($db_exists_result) {
    // La base de datos existe o podría existir (si $db_exists_result es false, hubo un error en SHOW DATABASES)
    // Intentar seleccionar la base de datos
    if (!$conn->select_db(DB_NAME)) {
        // Si la selección falla (ej. por permisos, aunque la BD exista)
        if (basename($_SERVER['PHP_SELF']) != 'setup.php') {
            die("Error seleccionando la base de datos '" . DB_NAME . "': " . $conn->error . ". Por favor, ejecute setup.php o verifique los permisos.");
        }
        // Si estamos en setup.php, se intentará crear/seleccionar de nuevo.
    } else {
        // Base de datos seleccionada correctamente, establecer charset.
        if (!$conn->set_charset("utf8mb4")) {
            // Opcional: registrar este error o mostrar un aviso
            // printf("Error cargando el conjunto de caracteres utf8mb4: %s\n", $conn->error);
        }
    }
} else {
    // Error al ejecutar SHOW DATABASES (poco probable si la conexión al servidor fue exitosa)
     if (basename($_SERVER['PHP_SELF']) != 'setup.php') {
        die("Error al verificar la existencia de la base de datos: " . $conn->error);
    }
}

// Incluir funciones globales
// __DIR__ es el directorio del archivo actual (core), así que functions.php está en el mismo directorio.
require_once __DIR__ . '/functions.php';

?>