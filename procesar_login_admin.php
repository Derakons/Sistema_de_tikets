<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';

// No incluir header.php aquí, ya que este archivo solo procesa lógica y redirecciones

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = limpiar_datos($_POST['username']);
    $password = $_POST['password']; // No limpiar aquí para verificar el hash

    // Buscar el usuario en la base de datos (asumiendo una tabla 'usuarios_admin')
    $sql = "SELECT id, nombre_usuario, contrasena_hash, nombre_completo FROM usuarios_admin WHERE nombre_usuario = ? LIMIT 1";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            // Verificar la contraseña
            if (password_verify($password, $admin['contrasena_hash'])) {
                // Inicio de sesión exitoso
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['nombre_usuario'];
                $_SESSION['admin_nombre_completo'] = $admin['nombre_completo'];
                
                // Regenerar ID de sesión por seguridad
                session_regenerate_id(true);

                header("Location: admin.php"); // Redirigir al panel de administración
                exit;
            } else {
                // Contraseña incorrecta
                $_SESSION['login_error'] = "Usuario o contraseña incorrectos.";
                header("Location: login_admin.php");
                exit;
            }
        } else {
            // Usuario no encontrado
            $_SESSION['login_error'] = "Usuario o contraseña incorrectos.";
            header("Location: login_admin.php");
            exit;
        }
        $stmt->close();
    } else {
        // Error en la preparación de la consulta
        $_SESSION['login_error'] = "Error en la base de datos.";
        header("Location: login_admin.php");
        exit;
    }
} else {
    header("Location: login_admin.php");
    exit;
}

$conn->close();
?>