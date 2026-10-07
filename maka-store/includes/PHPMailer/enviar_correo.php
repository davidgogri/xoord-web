<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function enviarCorreo($destinatario, $asunto, $mensaje) {
    $mail = new PHPMailer(true);

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Servidor SMTP de Gmail
        $mail->SMTPAuth = true;
        $mail->Username = 'maka.store.ml@gmail.com'; // Tu correo de Gmail
        $mail->Password = 'Mak@2023.'; // Tu contraseña o contraseña de aplicación
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Usar TLS
        $mail->Port = 587; // Puerto seguro para Gmail

        // Remitente y destinatario
        $mail->setFrom('maka.store.ml@gmail.com', 'Maka Store'); // Correo y nombre del remitente
        $mail->addAddress($destinatario); // Correo del destinatario

        // Contenido del correo
        $mail->isHTML(false); // Establece a true si quieres enviar HTML
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;

        // Enviar correo
        $mail->send();
        return true; // Éxito
    } catch (Exception $e) {
        error_log("Error al enviar el correo: " . $mail->ErrorInfo);
        return false; // Error
    }
}