<?php
require_once 'config.php';

// Función para limpiar los datos de entrada
function limpiar_datos($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato);
    return $dato;
}

// Función para generar un número de ticket único
function generar_numero_ticket($conn) {
    // Formato: TICKET-XXXX (donde XXXX es un número secuencial de 4 dígitos)
    $prefijo = "TICKET";
    $numero_secuencial = 1;

    // Intentar obtener el último ticket y extraer su número para incrementarlo
    $sql = "SELECT id FROM tickets ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    if ($fila = $resultado->fetch_assoc()) {
        $ultimo_numero_completo = $fila['id'];
        // Extraer el número secuencial del ID (formato TICKET-XXXX)
        if (preg_match('/' . $prefijo . '-(\d+)/', $ultimo_numero_completo, $matches)) {
            $numero_secuencial = intval($matches[1]) + 1;
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

// Función para generar el cuerpo de un informe tipo notificación para correo
function generar_informe_notificacion($ticket_data) {
    $fecha_actual = date('Y-m-d');
    $fecha_cierre = $ticket_data['fecha_cierre'] ? date('Y-m-d', strtotime($ticket_data['fecha_cierre'])) : 'N/A';
    $correo = $ticket_data['email_solicitante'] ?? 'N/A';
    $id_ticket = $ticket_data['id'] ?? 'N/A';
    $nombre = $ticket_data['nombre_solicitante'] ?? 'Usuario';
    $departamento = $ticket_data['nombre_departamento'] ?? 'N/A';
    $asunto = $ticket_data['descripcion_breve'] ?? ($ticket_data['asunto'] ?? '');
    $detalle = $ticket_data['detalle_fallo'] ?? '';
    $solucion = $ticket_data['cierre_solucion'] ?? '';
    $estado = $ticket_data['estado'] ?? '';
    $fecha_creacion = $ticket_data['fecha_creacion'] ? date('Y-m-d', strtotime($ticket_data['fecha_creacion'])) : 'N/A';
    return "*Notificación de cierre de ticket en el Sistema de Tickets de la Municipalidad Provincial de Canchis*\n\n"
    . "Hola. Este es un mensaje del Sistema de Tickets de Soporte TI.\n\n"
    . "Según la política institucional, su ticket *$id_ticket* ha sido cerrado el *$fecha_cierre*.\n"
    . "A continuación, el resumen de su caso:\n\n"
    . "*Departamento:* $departamento\n"
    . "*Asunto:* $asunto\n"
    . "*Detalle reportado:* $detalle\n"
    . "*Solución aplicada:* $solucion\n"
    . "*Estado final:* $estado\n"
    . "*Fecha de creación:* $fecha_creacion\n"
    . "*Fecha de cierre:* $fecha_cierre\n\n"
    . "Si tiene alguna pregunta sobre este ticket, puede responder a este correo o comunicarse con el área de TI de la Municipalidad.\n\n"
    . "Una vez más, gracias por usar el Sistema de Tickets de la Municipalidad Provincial de Canchis.\n\n"
    . "📧 Correo registrado: *$correo*\n"
    . "📅 Este mensaje se generó el *$fecha_actual*\n";
}

?>
