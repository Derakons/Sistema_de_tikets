<?php
require_once '../core/config.php';
require_once '../core/functions.php';

session_start();

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['ticket_id']) || empty($_POST['ticket_id'])) {
        $response['message'] = 'ID de ticket no proporcionado.';
        echo json_encode($response);
        exit;
    }

    if (!isset($_POST['calificacion_usuario']) || !filter_var($_POST['calificacion_usuario'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]])) {
        $response['message'] = 'Calificación inválida. Debe ser un número entre 1 y 5.';
        echo json_encode($response);
        exit;
    }

    $ticket_id = limpiar_datos($_POST['ticket_id']);
    $calificacion = (int)$_POST['calificacion_usuario'];
    $comentario = isset($_POST['comentario_usuario']) ? limpiar_datos($_POST['comentario_usuario']) : null;

    // Verificar que el ticket existe y está cerrado
    $stmt_check = $conn->prepare("SELECT estado FROM tickets WHERE id = ?");
    if (!$stmt_check) {
        $response['message'] = "Error preparando la consulta de verificación: " . $conn->error;
        echo json_encode($response);
        exit;
    }
    $stmt_check->bind_param("s", $ticket_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows === 0) {
        $response['message'] = 'Ticket no encontrado.';
        $stmt_check->close();
        echo json_encode($response);
        exit;
    }

    $ticket_data = $result_check->fetch_assoc();
    $stmt_check->close();

    if ($ticket_data['estado'] !== 'Cerrado') {
        $response['message'] = 'Solo se puede enviar feedback para tickets cerrados.';
        echo json_encode($response);
        exit;
    }

    // Guardar el feedback
    $sql = "UPDATE tickets SET comentario_usuario = ?, calificacion_usuario = ?, fecha_feedback = CURRENT_TIMESTAMP WHERE id = ? AND (comentario_usuario IS NULL AND calificacion_usuario IS NULL)";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sis", $comentario, $calificacion, $ticket_id);
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                $response['success'] = true;
                $response['message'] = '¡Gracias por tu feedback!';
                // Opcional: Redirigir o mostrar mensaje de éxito en la misma página.
                // Para redirigir:
                // header("Location: seguimiento.php?numero_ticket=" . urlencode($ticket_id) . "&feedback_success=1");
                // exit;
            } else {
                // Esto puede ocurrir si el feedback ya fue enviado o el ticket ID no es válido
                $response['message'] = 'No se pudo guardar el feedback. Es posible que ya hayas enviado tu opinión para este ticket o el ticket no exista.';
            }
        } else {
            $response['message'] = "Error al guardar el feedback: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $response['message'] = "Error al preparar la consulta de guardado: " . $conn->error;
    }
} else {
    $response['message'] = 'Método de solicitud no válido.';
}

$conn->close();

// Si no se ha redirigido, se devuelve una respuesta JSON
// Esto es útil si se maneja el formulario con AJAX.
// Si no es AJAX, la redirección de arriba es más apropiada.
// Para este ejemplo, asumiremos que podría ser AJAX o una recarga de página.
if ($response['success']) {
     header("Location: seguimiento.php?numero_ticket=" . urlencode($ticket_id) . "&feedback_success=1#feedback-section");
     exit;
} else {
    // Si falla, redirigir de vuelta con un mensaje de error
    // Es importante asegurar que $ticket_id está definido incluso en caso de error temprano.
    // Si $ticket_id no está disponible (ej. no se envió), redirigir a una página genérica o mostrar error.
    $redirect_url = "seguimiento.php";
    if (isset($ticket_id) && !empty($ticket_id)) {
        $redirect_url .= "?numero_ticket=" . urlencode($ticket_id);
    }
    $redirect_url .= (strpos($redirect_url, '?') === false ? '?' : '&') . "feedback_error=" . urlencode($response['message']) . "#feedback-section";
    header("Location: " . $redirect_url);
    exit;
}
?>
