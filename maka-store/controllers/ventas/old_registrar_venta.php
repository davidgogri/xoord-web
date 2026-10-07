<?php
// controllers/ventas/registrar_venta.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

// Validar que la solicitud es POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error_message'] = "Método de solicitud no permitido.";
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;
}

// Validar sesión de usuario
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = "Debe iniciar sesión para registrar una venta.";
    header("Location: ../../login.php");
    exit;
}

// Recibir y sanear los datos del formulario
$id_cliente = filter_input(INPUT_POST, 'id_cliente', FILTER_VALIDATE_INT);
$id_punto_venta = filter_input(INPUT_POST, 'id_punto_venta', FILTER_VALIDATE_INT);
$metodo_pago = filter_input(INPUT_POST, 'metodo_pago', FILTER_SANITIZE_STRING);
$fecha_venta = filter_input(INPUT_POST, 'fecha_venta', FILTER_SANITIZE_STRING);
$observaciones = filter_input(INPUT_POST, 'observaciones', FILTER_SANITIZE_STRING, FILTER_FLAG_NO_ENCODE_QUOTES);
$total_venta_form = filter_input(INPUT_POST, 'total_venta', FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

// Arrays de detalles de productos
$productos_ids = $_POST['producto_id'] ?? [];
$colores_ids = $_POST['color_id'] ?? [];
$tallas_ids = $_POST['talla_id'] ?? [];
$cantidades = $_POST['cantidad'] ?? [];
$precios_venta = $_POST['precio_venta'] ?? [];

// Validaciones básicas
if (!$id_cliente || !$id_punto_venta || !$metodo_pago || !$fecha_venta || empty($productos_ids)) {
    $_SESSION['error_message'] = "Todos los campos obligatorios deben ser completados y debe haber al menos un producto.";
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;
}

if (!is_array($productos_ids) || !is_array($colores_ids) || !is_array($tallas_ids) || !is_array($cantidades) || !is_array($precios_venta) ||
    count($productos_ids) !== count($colores_ids) ||
    count($productos_ids) !== count($tallas_ids) ||
    count($productos_ids) !== count($cantidades) ||
    count($productos_ids) !== count($precios_venta)) {
    $_SESSION['error_message'] = "Datos de productos incompletos o inconsistentes.";
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;
}

// Calcular el total de la venta para validar con el total del formulario
$calculated_total_venta = 0;
foreach ($productos_ids as $index => $id_producto) {
    $current_cantidad = filter_var($cantidades[$index], FILTER_VALIDATE_INT);
    $current_precio_unitario = filter_var($precios_venta[$index], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    if ($current_cantidad === false || $current_cantidad <= 0 || $current_precio_unitario === false || $current_precio_unitario < 0) {
        $_SESSION['error_message'] = "Cantidad o precio de venta inválido para un producto.";
        header("Location: ../../views/ventas/nueva_venta.php");
        exit;
    }
    $calculated_total_venta += ($current_cantidad * $current_precio_unitario);
}

// Pequeña tolerancia para errores de coma flotante
if (abs($calculated_total_venta - $total_venta_form) > 0.01) {
    $_SESSION['error_message'] = "El total de la venta calculado no coincide con el total enviado. Posible manipulación o error de cálculo. Total esperado: " . number_format($calculated_total_venta, 2) . ", Total recibido: " . number_format($total_venta_form, 2);
    error_log("Discrepancia en el total de venta: Calculado $calculated_total_venta, Recibido $total_venta_form");
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;
}
$total_venta = $calculated_total_venta; // Usar el total calculado para mayor seguridad

try {
    $pdo->beginTransaction();

    // Consulta SQL para verificar el stock actual y actualizarlo
    $stmt_check_stock = $pdo->prepare("
        SELECT cantidad_stock
        FROM inventario
        WHERE id_producto = :id_producto
          AND id_color = :id_color
          AND id_talla = :id_talla
          AND id_punto_venta = :id_punto_venta
        FOR UPDATE
    ");

    $stmt_update_stock = $pdo->prepare("
        UPDATE inventario
        SET cantidad_stock = cantidad_stock - :cantidad_vendida
        WHERE id_producto = :id_producto
          AND id_color = :id_color
          AND id_talla = :id_talla
          AND id_punto_venta = :id_punto_venta
    ");

    foreach ($productos_ids as $index => $current_producto_id) {
        $current_color_id = filter_var($colores_ids[$index], FILTER_VALIDATE_INT);
        $current_talla_id = filter_var($tallas_ids[$index], FILTER_VALIDATE_INT);
        $current_cantidad = filter_var($cantidades[$index], FILTER_VALIDATE_INT);

        if ($current_producto_id === false || $current_color_id === false || $current_talla_id === false || $current_cantidad === false || $current_cantidad <= 0) {
            throw new Exception("Datos de producto o cantidad inválidos en la línea " . ($index + 1));
        }

        // 1. Verificar stock
        $stmt_check_stock->bindParam(':id_producto', $current_producto_id, PDO::PARAM_INT);
        $stmt_check_stock->bindParam(':id_color', $current_color_id, PDO::PARAM_INT);
        $stmt_check_stock->bindParam(':id_talla', $current_talla_id, PDO::PARAM_INT);
        $stmt_check_stock->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
        $stmt_check_stock->execute();
        $stock_row = $stmt_check_stock->fetch(PDO::FETCH_ASSOC);

        if (!$stock_row || $stock_row['cantidad_stock'] < $current_cantidad) {
            $product_name_for_error = "Producto ID: $current_producto_id, Color ID: $current_color_id, Talla ID: $current_talla_id";
            if ($stock_row) {
                throw new Exception("Stock insuficiente para " . $product_name_for_error . ". Disponible: " . $stock_row['cantidad_stock'] . ", Solicitado: " . $current_cantidad);
            } else {
                throw new Exception("Producto no encontrado en inventario para " . $product_name_for_error . " en el punto de venta seleccionado.");
            }
        }
    }

    // Insertar la venta principal
    $stmt_venta = $pdo->prepare("
        INSERT INTO ventas (id_cliente, id_usuario, id_punto_venta, fecha_venta, total_venta, metodo_pago, observaciones)
        VALUES (:id_cliente, :id_usuario, :id_punto_venta, :fecha_venta, :total_venta, :metodo_pago, :observaciones)
    ");
    $stmt_venta->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
    $stmt_venta->bindParam(':id_usuario', $_SESSION['usuario_id'], PDO::PARAM_INT);
    $stmt_venta->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt_venta->bindParam(':fecha_venta', $fecha_venta, PDO::PARAM_STR);
    $stmt_venta->bindParam(':total_venta', $total_venta);
    $stmt_venta->bindParam(':metodo_pago', $metodo_pago, PDO::PARAM_STR);
    $stmt_venta->bindParam(':observaciones', $observaciones, PDO::PARAM_STR);
    $stmt_venta->execute();
    $id_venta = $pdo->lastInsertId();

    // Insertar detalles de venta y actualizar el inventario (ahora que el stock ha sido verificado y bloqueado)
    $stmt_detalle = $pdo->prepare("
        INSERT INTO detalles_venta (id_venta, id_producto, id_color, id_talla, cantidad, precio_venta, subtotal)
        VALUES (:id_venta, :id_producto, :id_color, :id_talla, :cantidad, :precio_venta, :subtotal)
    ");

    foreach ($productos_ids as $index => $current_producto_id) {
        $current_color_id = filter_var($colores_ids[$index], FILTER_VALIDATE_INT);
        $current_talla_id = filter_var($tallas_ids[$index], FILTER_VALIDATE_INT);
        $current_cantidad = filter_var($cantidades[$index], FILTER_VALIDATE_INT);
        $current_precio_unitario = filter_var($precios_venta[$index], FILTER_VALIDATE_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $subtotal_item = $current_cantidad * $current_precio_unitario;

        // Insertar detalle de venta
        $stmt_detalle->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_detalle->bindParam(':id_producto', $current_producto_id, PDO::PARAM_INT);
        $stmt_detalle->bindParam(':id_color', $current_color_id, PDO::PARAM_INT);
        $stmt_detalle->bindParam(':id_talla', $current_talla_id, PDO::PARAM_INT);
        $stmt_detalle->bindParam(':cantidad', $current_cantidad, PDO::PARAM_INT);
        $stmt_detalle->bindParam(':precio_venta', $current_precio_unitario);
        $stmt_detalle->bindParam(':subtotal', $subtotal_item);
        $stmt_detalle->execute();

        // Actualizar stock (esto ya ha sido pre-verificado, ahora se aplica el cambio)
        $stmt_update_stock->bindParam(':cantidad_vendida', $current_cantidad, PDO::PARAM_INT);
        $stmt_update_stock->bindParam(':id_producto', $current_producto_id, PDO::PARAM_INT);
        $stmt_update_stock->bindParam(':id_color', $current_color_id, PDO::PARAM_INT);
        $stmt_update_stock->bindParam(':id_talla', $current_talla_id, PDO::PARAM_INT);
        $stmt_update_stock->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
        $stmt_update_stock->execute();
    }

    $pdo->commit();
    $_SESSION['success_message'] = "Venta registrada exitosamente con ID: " . $id_venta;
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error_message'] = "Error al registrar la venta: " . $e->getMessage();
    error_log("Error en registrar_venta.php: " . $e->getMessage() . " Stack: " . $e->getTraceAsString());
    header("Location: ../../views/ventas/nueva_venta.php");
    exit;
}
?>