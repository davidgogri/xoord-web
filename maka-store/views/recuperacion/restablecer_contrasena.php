<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../includes/db.php';

// Verificar si se recibió el token
if (!isset($_GET['token'])) {
    die("Token no proporcionado.");
}

$token = $_GET['token'];

// Verificar si el token es válido y no ha expirado
$stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE reset_token = ? AND reset_token_expires > NOW()");
$stmt->execute([$token]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario) {
    die("El enlace de restablecimiento no es válido o ha expirado.");
}

// Procesar el formulario de restablecimiento
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nueva_contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT);

    // Actualizar la contraseña y limpiar el token
    $stmt = $pdo->prepare("UPDATE usuarios SET contrasena = ?, reset_token = NULL, reset_token_expires = NULL WHERE id_usuario = ?");
    $stmt->execute([$nueva_contrasena, $usuario['id_usuario']]);

    echo "Tu contraseña ha sido restablecida exitosamente.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> <!-- Favicon -->
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        form {
            max-width: 400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .form-group button {
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .form-group button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <h2>Restablecer Contraseña</h2>
    <form method="POST">
        <div class="form-group">
            <label for="contrasena">Nueva Contraseña:</label>
            <input type="password" name="contrasena" placeholder="Ingresa tu nueva contraseña" required>
        </div>
        <div class="form-group">
            <button type="submit">Restablecer Contraseña</button>
        </div>
    </form>
</body>
</html>