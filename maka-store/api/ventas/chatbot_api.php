<?php
// public_html/maka-store/api/ventas/chatbot_api.php

// 1. Configuración de errores y buffer de salida
// Habilitar la visualización de errores solo para desarrollo.
// En producción, es mejor que los errores se logueen y no se muestren al usuario.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar un buffer de salida para capturar cualquier salida inesperada (errores, espacios en blanco, etc.)
// Esto es CRÍTICO para APIs que deben devolver JSON puro.
ob_start();

// 2. Inicio de sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 3. Inclusión de dependencias
// Las rutas están correctas si la raíz del proyecto es public_html/maka-store/
require_once __DIR__ . '/../../includes/db.php'; // Asegúrate de que este archivo define $pdo
require_once __DIR__ . '/../../controllers/VentasController.php'; // Asegúrate de que esta clase está bien definida

// 4. Capturar cualquier salida hasta este punto (por ejemplo, errores o espacios en blanco antes de las inclusiones)
$unexpectedOutput = ob_get_clean(); // Vacía el buffer y obtiene su contenido

// 5. Preparar la respuesta JSON por defecto
$response = [
    'bot_message' => 'Ocurrió un error inesperado en el servidor.',
    'action' => 'error'
];

// 6. Configurar la cabecera de respuesta como JSON
header('Content-Type: application/json');

// 7. Manejo de errores de salida inesperada
if (!empty($unexpectedOutput)) {
    // Si se detectó alguna salida inesperada (probablemente un error PHP), la incluimos en la respuesta.
    // Esto es para DEPURACIÓN. En un entorno de producción, NO querrías exponer esto al cliente.
    $response['bot_message'] = 'Error de servidor: Salida inesperada antes del JSON.';
    $response['debug_info'] = $unexpectedOutput; // Aquí verás el "br /><b>" y el mensaje de error real.
    echo json_encode($response);
    exit; // Terminar la ejecución para evitar más problemas.
}

// 8. Validación de usuario logueado
if (!isset($_SESSION['usuario_id'])) {
    $response['bot_message'] = 'No estás logueado. Por favor, inicia sesión para usar el asistente de ventas.';
    $response['action'] = 'unauthenticated'; // Un nuevo tipo de acción para manejar en el frontend si quieres
    echo json_encode($response);
    exit;
}

// 9. Procesamiento de la solicitud
try {
    $userMessage = $_POST['message'] ?? '';

    // Validar que la conexión PDO esté disponible
    if (!isset($pdo) || !$pdo instanceof PDO) {
        throw new Exception("La conexión a la base de datos (PDO) no está disponible.");
    }

    // Instanciar el controlador de ventas y pasarle la conexión PDO
    $ventasController = new VentasController($pdo);

    // Llamar al método del controlador que manejará la interacción del chatbot
    $controllerResponse = $ventasController->handleChatbotInteraction($userMessage);

    // Asegurarse de que el controlador devuelva un array válido para json_encode
    if (is_array($controllerResponse)) {
        $response = $controllerResponse;
    } else {
        throw new Exception("El controlador devolvió un formato de respuesta inesperado.");
    }

} catch (PDOException $e) {
    // Captura errores específicos de la base de datos
    $response['bot_message'] = 'Error de base de datos: ' . $e->getMessage();
    $response['action'] = 'error';
    // Puedes agregar $response['sql_error_code'] = $e->getCode(); para más info si necesitas
} catch (Exception $e) {
    // Captura cualquier otra excepción general (ej. si VentasController no existe o falla su instanciación)
    $response['bot_message'] = 'Ocurrió un error inesperado: ' . $e->getMessage();
    $response['action'] = 'error';
}

// 10. Enviar la respuesta JSON final y terminar
echo json_encode($response);
exit;
// Es una buena práctica OMITIR la etiqueta de cierre de PHP `?>` en archivos que solo contienen código PHP
// para evitar la inyección accidental de espacios o saltos de línea al final del archivo.