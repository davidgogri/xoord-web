<?php
// controllers/ventas/registrar_venta_chat.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

// Validar que la solicitud sea POST y que el cuerpo no esté vacío
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty(file_get_contents('php://input'))) {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no permitido o datos faltantes.']);
    exit;
}

// Validar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'Debe iniciar sesión para registrar una venta.']);
    exit;
}

// Recibir y decodificar los datos JSON
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos básicos
if (!isset($data['id_cliente'], $data['id_punto_venta'], $data['metodo_pago'], $data['productos']) || empty($data['productos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos de venta incompletos.']);
    exit;
}

$id_cliente = filter_var($data['id_cliente'], FILTER_VALIDATE_INT);
$id_punto_venta = filter_var($data['id_punto_venta'], FILTER_VALIDATE_INT);
$metodo_pago = htmlspecialchars($data['metodo_pago'], ENT_QUOTES, 'UTF-8');
$productos = $data['productos'];
$fecha_venta = date('Y-m-d H:i:s');
$usuario_id = $_SESSION['usuario_id'];

if ($id_cliente === false || $id_punto_venta === false) {
    echo json_encode(['success' => false, 'message' => 'Datos de cliente o punto de venta no válidos.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $total_venta = 0;
    foreach ($productos as $producto) {
        $id_producto = filter_var($producto['id_producto'], FILTER_VALIDATE_INT);
        $cantidad = filter_var($producto['cantidad'], FILTER_VALIDATE_INT);
        $id_color = filter_var($producto['id_color'], FILTER_VALIDATE_INT);
        $id_talla = filter_var($producto['id_talla'], FILTER_VALIDATE_INT);
        $precio_venta_unitario = filter_var($producto['precio_venta'], FILTER_VALIDATE_FLOAT);

        if ($id_producto === false || $cantidad === false || $id_color === false || $id_talla === false || $precio_venta_unitario === false) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Datos de producto no válidos.']);
            exit;
        }

        // Re-verificar el stock antes de la venta
        $stmt_stock = $pdo->prepare("
            SELECT cantidad_stock FROM inventario
            WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ?
        ");
        $stmt_stock->execute([$id_producto, $id_color, $id_talla, $id_punto_venta]);
        $stock_disponible = $stmt_stock->fetchColumn();

        if ($stock_disponible < $cantidad) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Stock insuficiente. Intenta de nuevo.']);
            exit;
        }

        $total_venta += $cantidad * $precio_venta_unitario;
    }

    // 1. Insertar en la tabla 'ventas'
    $stmt_venta = $pdo->prepare("
        INSERT INTO ventas (id_cliente, id_punto_venta, id_usuario, total_venta, metodo_pago, fecha_venta)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt_venta->execute([$id_cliente, $id_punto_venta, $usuario_id, $total_venta, $metodo_pago, $fecha_venta]);
    $id_venta = $pdo->lastInsertId();

    // 2. Insertar en 'detalles_venta' y actualizar el inventario
    $stmt_detalle = $pdo->prepare("
        INSERT INTO detalles_venta (id_venta, id_producto, id_color, id_talla, cantidad, precio_venta, subtotal)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_update_stock = $pdo->prepare("
        UPDATE inventario 
        SET cantidad_stock = cantidad_stock - ?
        WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ?
    ");

    foreach ($productos as $producto) {
        $id_producto = $producto['id_producto'];
        $cantidad = $producto['cantidad'];
        $id_color = $producto['id_color'];
        $id_talla = $producto['id_talla'];
        $precio_venta_unitario = $producto['precio_venta'];
        $subtotal = $cantidad * $precio_venta_unitario;

        $stmt_detalle->execute([$id_venta, $id_producto, $id_color, $id_talla, $cantidad, $precio_venta_unitario, $subtotal]);
        $stmt_update_stock->execute([$cantidad, $id_producto, $id_color, $id_talla, $id_punto_venta]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'id_venta' => $id_venta, 'message' => 'Venta registrada exitosamente.']);

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Error al registrar la venta por chat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error de base de datos al registrar la venta.']);
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Error inesperado al registrar la venta por chat: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error inesperado al registrar la venta.']);
}
?>