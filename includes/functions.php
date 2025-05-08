<?php
require_once 'config.php';

// Función para limpiar los datos de entrada
function limpiar_datos($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Función para generar un número de ticket único (ejemplo básico)
function generar_numero_ticket($conn) {
    // Formato: TICKET-XXX (donde XXX es un número secuencial de 3 dígitos)
    $prefijo = "TICKET";
    $numero_secuencial = 1;

    // Intentar obtener el último número para este día y sumarle 1
    // Esto es una simplificación, en un sistema real se necesitaría un mejor control de concurrencia
    $sql = "SELECT id FROM tickets WHERE id LIKE ? ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $param = $prefijo . "%";
    $stmt->bind_param("s", $param);
    $stmt->execute();
    $resultado = $stmt->get_result();
    if ($fila = $resultado->fetch_assoc()) {
        $ultimo_numero_completo = $fila['id'];
        $partes = explode('-', $ultimo_numero_completo);
        if (count($partes) === 3) {
            $numero_secuencial = intval(end($partes)) + 1;
        }
    }
    $stmt->close();

    return $prefijo . "-" . str_pad($numero_secuencial, 4, "0", STR_PAD_LEFT);
}

// Función para verificar si el administrador está logueado
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

// Función para proteger páginas de administración
function proteger_pagina_admin() {
    if (!isAdminLoggedIn()) {
        $_SESSION['login_error'] = "Debes iniciar sesión para acceder a esta página.";
        header("Location: login_admin.php");
        exit;
    }
}

// Función para obtener el nombre completo del administrador logueado
function getAdminFullName() {
    return $_SESSION['admin_nombre_completo'] ?? 'Administrador'; // Devuelve 'Administrador' si no está definido
}

// Función para cerrar sesión del administrador
function logoutAdmin() {
    // Destruir todas las variables de sesión.
    $_SESSION = array();

    // Si se desea destruir la sesión completamente, borre también la cookie de sesión.
    // Nota: ¡Esto destruirá la sesión, y no solo los datos de la sesión!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Finalmente, destruir la sesión.
    session_destroy();

    header("Location: login_admin.php");
    exit;
}

// Más funciones pueden ser agregadas aquí (ej: obtener tickets, actualizar ticket, etc.)

/**
 * Obtiene todos los departamentos de la base de datos.
 * @param mysqli $conn Conexión a la base de datos.
 * @return array Lista de departamentos.
 */
function obtener_departamentos($conn) {
    $departamentos = [];
    $sql = "SELECT id, nombre_departamento FROM departamentos ORDER BY nombre_departamento ASC";
    $result = $conn->query($sql);
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $departamentos[] = $row;
        }
    }
    return $departamentos;
}

?>
