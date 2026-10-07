<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];

    // Verificar si el correo existe en la base de datos
    $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario) {
        // Generar un token único
        $token = bin2hex(random_bytes(32));

        // Generar la fecha de expiración con la zona horaria de Bogotá
        $expiracion = date('Y-m-d H:i:s', strtotime('+1 hour')); // Fecha actual + 1 hora

        // Guardar el token y su fecha de expiración en la base de datos
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_token_expires = ? WHERE id_usuario = ?");
        $stmt->execute([$token, $expiracion, $usuario['id_usuario']]);

        // Crear el enlace de restablecimiento
        $enlace = "http://maka-store.xoord.com/views/recuperacion/restablecer_contrasena.php?token=$token";
        $mensaje = "Haz clic en el siguiente enlace para restablecer tu contraseña: $enlace";

        // Configurar el correo
        $asunto = "Restablecimiento de Contraseña";
        $headers = "From: no-reply@maka-store.xoord.com\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        if (mail($correo, $asunto, $mensaje, $headers)) {
            echo "Se ha enviado un enlace de restablecimiento a tu correo electrónico.";
        } else {
            echo "Error al enviar el correo electrónico. Por favor, inténtalo de nuevo.";
        }
    } else {
        echo "El correo electrónico no está registrado.";
    }
}
?>