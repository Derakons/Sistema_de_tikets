<?php
require_once '../core/config.php';
require_once '../core/functions.php';

echo "<h1>Prueba de generación de IDs de ticket</h1>";

// Obtener un nuevo ID de ticket
$nuevo_id = generar_numero_ticket($conn);
echo "<p>Nuevo ID generado: <strong>" . htmlspecialchars($nuevo_id) . "</strong></p>";

// Mostrar los IDs existentes para referencia
echo "<h2>IDs existentes en la base de datos:</h2>";
$sql = "SELECT id FROM tickets ORDER BY id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>" . htmlspecialchars($row['id']) . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p>No hay tickets existentes en la base de datos.</p>";
}

$conn->close();
?>
