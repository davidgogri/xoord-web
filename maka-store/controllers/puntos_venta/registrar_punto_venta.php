<?php
// Habilitar la visualización de errores (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario ha iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../includes/db.php';

// Verificar si la solicitud es de tipo POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Obtener y sanear los datos del formulario
    $nombre_punto = filter_input(INPUT_POST, 'nombre_punto', FILTER_SANITIZE_STRING);
    $direccion = filter_input(INPUT_POST, 'direccion', FILTER_SANITIZE_STRING);
    $telefono = filter_input(INPUT_POST, 'telefono', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $estado = filter_input(INPUT_POST, 'estado', FILTER_SANITIZE_STRING); // 'activo' o 'inactivo'

    // 2. Validaciones básicas (puedes añadir más si es necesario)
    if (empty($nombre_punto) || empty($direccion) || empty($estado)) {
        $_SESSION['error_message'] = "El nombre del punto, la dirección y el estado son campos obligatorios.";
        header("Location: ../../views/puntos_venta/nuevo_punto_venta.php"); // Redirigir de vuelta al formulario
        exit;
    }

    if (!in_array($estado, ['activo', 'inactivo'])) {
        $_SESSION['error_message'] = "El valor del estado no es válido.";
        header("Location: ../../views/puntos_venta/nuevo_punto_venta.php");
        exit;
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "El formato del email no es válido.";
        header("Location: ../../views/puntos_venta/nuevo_punto_venta.php");
        exit;
    }

    try {
        // 3. Preparar la consulta SQL para insertar los datos
        $stmt = $pdo->prepare("
            INSERT INTO puntos_venta (nombre_punto, direccion, telefono, email, estado)
            VALUES (:nombre_punto, :direccion, :telefono, :email, :estado)
        ");

        // 4. Asignar los parámetros y ejecutar la consulta
        $stmt->bindParam(':nombre_punto', $nombre_punto);
        $stmt->bindParam(':direccion', $direccion);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':estado', $estado);

        $stmt->execute();

        // Si la inserción fue exitosa, redirigir a la lista de puntos de venta
        $_SESSION['success_message'] = "Punto de venta '{$nombre_punto}' registrado exitosamente.";
        header("Location: ../../views/puntos_venta/listar_puntos_venta.php"); // Redirigir a la lista
        exit;

    } catch (PDOException $e) {
        // En caso de error, almacenar el mensaje y redirigir
        error_log("Error al registrar punto de venta: " . $e->getMessage());
        $_SESSION['error_message'] = "Error al registrar el punto de venta: " . $e->getMessage();
        header("Location: ../../views/puntos_venta/nuevo_punto_venta.php"); // Redirigir de vuelta al formulario
        exit;
    }
} else {
    // Si no es una solicitud POST, redirigir a la página de registro o dashboard
    header("Location: ../../views/puntos_venta/nuevo_punto_venta.php");
    exit;
}
?>