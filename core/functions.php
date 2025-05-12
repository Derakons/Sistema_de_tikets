<?php
require_once __DIR__ . '/config.php';

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
        
        // Determinar la ubicación del script actual
        $script_path = $_SERVER['SCRIPT_NAME'];
        
        if (strpos($script_path, '/admin/') !== false) {
            header("Location: login.php");
        } else {
            header("Location: admin/login.php");
        }
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
    
    // Redireccionar a la página de login
    $script_path = $_SERVER['SCRIPT_NAME'];
    
    // Determinar la ruta de redirección basada en la ubicación del script
    if (strpos($script_path, '/admin/') !== false) {
        header("Location: login.php");
        exit;
    } else {
        header("Location: admin/login.php");
        exit;
    }
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
    $asunto = "Notificación de Resolución: Ticket N° " . $ticket_data['id'] . " - " . ($ticket_data['descripcion_breve'] ?? 'Sin asunto');
    $usuario = $ticket_data['usuario'] ?? 'N/A';
    $departamento = $ticket_data['nombre_departamento'] ?? 'N/A';
    $fecha_creacion = date('d/m/Y H:i:s', strtotime($ticket_data['fecha_creacion']));
    $fecha_cierre = $ticket_data['fecha_cierre'] ? date('d/m/Y H:i:s', strtotime($ticket_data['fecha_cierre'])) : 'N/A';
    $tiempo_resolucion = '';
    if ($ticket_data['fecha_cierre'] && $ticket_data['fecha_creacion']) {
        $inicio = strtotime($ticket_data['fecha_creacion']);
        $fin = strtotime($ticket_data['fecha_cierre']);
        $minutos = round(($fin - $inicio) / 60);
        $tiempo_resolucion = $minutos . ' minutos';
    }
    $diagnostico = $ticket_data['diagnostico'] ?? '';
    $cierre = $ticket_data['cierre_solucion'] ?? '';
    $prioridad = $ticket_data['prioridad'] ?? 'N/A';
    $tipo = $ticket_data['identificacion_tipo'] ?? 'N/A';
    $estado = $ticket_data['estado'] ?? 'N/A';
    $fecha_actual = date('d/m/Y H:i:s');
    $res = "Asunto: $asunto\n";
    $res .= "El presente documento informa sobre la resolución del ticket N° {$ticket_data['id']}, solicitado por el usuario $usuario y gestionado por el departamento de $departamento.\n";
    $res .= "La incidencia reportada por el usuario fue \"{$ticket_data['descripcion_breve']}\", la cual fue clasificada como una avería de $tipo con una prioridad asignada de $prioridad.\n";
    $res .= "Para solucionar esta situación, el equipo técnico implementó una serie de medidas:\n";
    $res .= ($cierre ? $cierre . "\n" : "");
    $res .= "Como resultado de estas acciones, el estado final del ticket es $estado.\n";
    $res .= "El ticket fue generado el $fecha_creacion y fue cerrado el $fecha_cierre. El tiempo total empleado para la resolución de la incidencia fue de $tiempo_resolucion.\n";
    $res .= "Se ha comunicado al usuario $usuario la resolución y el cierre formal de su solicitud mediante correo electrónico. Se le ha informado que, si tiene alguna consulta adicional sobre las medidas implementadas o cualquier otra inquietud, puede responder al mensaje recibido o contactar directamente al área de TI.\n";
    $res .= "Atentamente,\nEl Equipo de Soporte Técnico\n(Informe generado: $fecha_actual)\n";
    return $res;
}

?>
