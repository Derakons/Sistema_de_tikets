<?php
$page_title = "Resultado del Envío de Ticket";
require_once '../core/config.php';
require_once '../core/functions.php';

$detalle_fallo = '';
$descripcion_breve = '';
$departamento_id = '';
$nombre_solicitante = '';
$contacto_solicitante = '';
$ticket_id_generado = null;
$error_message = '';
$success_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiar y obtener datos del formulario
    $asunto = limpiar_datos($_POST['asunto']); // Usar 'asunto' como en el form de public/index.php
    $descripcion = limpiar_datos($_POST['descripcion']); // Usar 'descripcion' como en el form de public/index.php
    $departamento_id = limpiar_datos($_POST['id_departamento']); // ID de departamento
    // Asignar solicitante desde el campo 'nombre_completo'
    $nombre_solicitante = limpiar_datos($_POST['nombre_completo']);
    // Sin campos de teléfono ni email en formulario, asignar vacío para evitar null
    $telefono_solicitante = '';
    $email_solicitante = '';

    // Validaciones básicas (puedes agregar más)
    if (empty($asunto) || empty($descripcion) || empty($departamento_id)) {
        $error_message = "Por favor, complete todos los campos obligatorios: Asunto, Descripción y Departamento.";
    } else {
        $ticket_id_generado = generar_numero_ticket($conn); // Usar la función para el ID
        $fecha_creacion = date('Y-m-d H:i:s');
        $estado_inicial = 'Abierto';

        // Corregir los nombres de las columnas en la consulta SQL
        $sql = "INSERT INTO tickets (id, fecha_creacion, asunto, descripcion, id_departamento, nombre_solicitante, telefono_solicitante, email_solicitante, estado) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            // Ya se definieron $nombre_solicitante, $telefono_solicitante y $email_solicitante

            $stmt->bind_param("ssssissss", 
                $ticket_id_generado, 
                $fecha_creacion, 
                $asunto, // Usar $asunto
                $descripcion, // Usar $descripcion
                $departamento_id, 
                $nombre_solicitante, 
                $telefono_solicitante, // Cadena vacía
                $email_solicitante, // Cadena vacía
                $estado_inicial
            );
            
            if ($stmt->execute()) {
                $success_message = "Ticket creado exitosamente. Su número de ticket es: <strong>" . htmlspecialchars($ticket_id_generado) . "</strong>. Por favor, guárdelo para futuras consultas.";
            } else {
                $error_message = "Error al crear el ticket: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Error al preparar la consulta: " . $conn->error;
        }
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - <?php echo htmlspecialchars(SITE_TITLE); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/main.css">
</head>
<body class="has-sidebar">
<?php include_once __DIR__ . '/../core/templates/sidebar_public.php'; ?>
<div class="dashboard-main-content">
    <main class="container page-container page-content">
        <h1><?php echo htmlspecialchars($page_title); ?></h1>
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert"><?php echo $success_message; ?></div>
        <?php endif; ?>
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger" role="alert"><strong>Error:</strong> <?php echo $error_message; ?></div>
        <?php endif; ?>
        <p><a href="index.php" class="btn btn-primary">Crear Otro Ticket</a></p>
        <p><a href="seguimiento.php<?php echo $ticket_id_generado ? '?numero_ticket=' . htmlspecialchars($ticket_id_generado) : ''; ?>" class="btn btn-secondary">Hacer Seguimiento de este Ticket</a></p>
    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
</body>
</html>
