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

// Funciones para el dashboard y la visualización de tickets

/**
 * Calcula el tiempo transcurrido desde una fecha dada hasta ahora.
 * @param string $fecha_pasada Fecha en formato YYYY-MM-DD HH:MM:SS.
 * @return string Texto descriptivo del tiempo transcurrido (ej: "hace 5 minutos", "ayer", "hace 2 días").
 */
function tiempo_transcurrido($fecha_pasada) {
    if (empty($fecha_pasada)) {
        return "Fecha desconocida";
    }
    try {
        $ahora = new DateTime();
        $fecha = new DateTime($fecha_pasada);
        $diferencia = $ahora->diff($fecha);

        if ($diferencia->y > 0) {
            return "hace " . $diferencia->y . " " . ($diferencia->y > 1 ? "años" : "año");
        }
        if ($diferencia->m > 0) {
            return "hace " . $diferencia->m . " " . ($diferencia->m > 1 ? "meses" : "mes");
        }
        if ($diferencia->d > 0) {
            if ($diferencia->d == 1) return "ayer";
            return "hace " . $diferencia->d . " días";
        }
        if ($diferencia->h > 0) {
            return "hace " . $diferencia->h . " " . ($diferencia->h > 1 ? "horas" : "hora");
        }
        if ($diferencia->i > 0) {
            return "hace " . $diferencia->i . " " . ($diferencia->i > 1 ? "minutos" : "minuto");
        }
        if ($diferencia->s > 0) {
            return "hace " . $diferencia->s . " " . ($diferencia->s > 1 ? "segundos" : "segundo");
        }
        return "justo ahora";
    } catch (Exception $e) {
        error_log("Error en tiempo_transcurrido: " . $e->getMessage() . " para fecha: " . $fecha_pasada);
        return "Fecha inválida";
    }
}

/**
 * Devuelve una clase CSS de Bootstrap basada en el estado del ticket.
 * @param string $estado Estado del ticket.
 * @return string Clase CSS.
 */
function obtener_clase_estado($estado) {
    if ($estado === null) return 'primary';
    switch (strtolower((string)$estado)) {
        case 'abierto':
            return 'success';
        case 'en progreso':
            return 'warning';
        case 'resuelto':
            return 'info';
        case 'cerrado':
            return 'secondary';
        case 'en espera':
            return 'light';
        default:
            return 'primary';
    }
}

/**
 * Devuelve una clase CSS de Bootstrap basada en la prioridad del ticket.
 * @param string $prioridad Prioridad del ticket.
 * @return string Clase CSS.
 */
function obtener_clase_prioridad($prioridad) {
    if ($prioridad === null) return 'light';
    switch (strtolower((string)$prioridad)) {
        case 'muy grave':
            return 'danger'; 
        case 'alto':
        case 'grave':
            return 'danger'; 
        case 'medio':
            return 'warning'; 
        case 'bajo':
        case 'leve':
            return 'info'; 
        default:
            return 'light'; 
    }
}

/**
 * Limita un texto a una longitud máxima y añade puntos suspensivos si es necesario.
 * @param string $texto El texto a limitar.
 * @param int $longitud_maxima La longitud máxima deseada.
 * @param string $sufijo El sufijo a añadir si el texto se corta (por defecto "...").
 * @return string El texto limitado.
 */
function limitar_texto($texto, $longitud_maxima, $sufijo = '...') {
    if ($texto === null) return '';
    if (mb_strlen((string)$texto) > $longitud_maxima) {
        return mb_substr((string)$texto, 0, $longitud_maxima - mb_strlen($sufijo)) . $sufijo;
    }
    return (string)$texto;
}

/**
 * Formatea una fecha y hora a un formato legible.
 * @param string|null $fecha_string La fecha en formato que pueda ser interpretado por strtotime (ej: YYYY-MM-DD HH:MM:SS).
 * @param string $formato El formato de salida deseado (por defecto 'd/m/Y H:i:s').
 * @return string La fecha formateada o un mensaje de error/alternativo si la fecha es inválida.
 */
function formatear_fecha_hora($fecha_string, $formato = 'd/m/Y H:i:s') {
    if (empty($fecha_string)) {
        return 'Fecha no disponible';
    }
    try {
        $fecha = new DateTime($fecha_string);
        return $fecha->format($formato);
    } catch (Exception $e) {
        // Log del error si es necesario, o simplemente devolver un texto alternativo
        error_log("Error al formatear fecha: " . $e->getMessage() . " para fecha: " . $fecha_string);
        return 'Fecha inválida';
    }
}

?>
