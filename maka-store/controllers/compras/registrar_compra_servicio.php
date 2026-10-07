<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

try {
    // Verificar que el usuario está logueado
    if (!isset($_SESSION['usuario_id'])) {
        throw new Exception("Debes iniciar sesión para registrar una compra.");
    }

    // Obtener datos del formulario
    $concepto = $_POST['concepto'];
    $monto = $_POST['monto'];
    $metodo_pago = $_POST['metodo_pago'];
    $fecha_compra = $_POST['fecha_compra'];
    $descripcion = $_POST['descripcion'] ?? null;
    $id_usuario = $_SESSION['usuario_id'];

    // Insertar en la tabla `compras_servicios`
    $stmt = $pdo->prepare("
        INSERT INTO compras_servicios (concepto, monto, metodo_pago, fecha_compra, descripcion, id_usuario)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$concepto, $monto, $metodo_pago, $fecha_compra, $descripcion, $id_usuario]);

    // Redirigir al listado de compras de servicios
    header("Location: ../../views/compras/listar_compras_servicios.php");
    exit;
} catch (Exception $e) {
    error_log("Error al registrar la compra de servicio: " . $e->getMessage());
    echo "Error al registrar la compra de servicio. Por favor, inténtalo de nuevo.";
}
?>