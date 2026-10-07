<?php
// Habilitar la visualización de errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../../login.php");
    exit;
}
require_once '../../includes/db.php';

try {
    // Recibir los datos del formulario
    $id_cliente = $_POST['id_cliente'];
    $metodo_pago = $_POST['metodo_pago'];
    $productos_ids = $_POST['producto_id']; // Array de IDs de productos
    $precios_venta = $_POST['precio_venta']; // Array de precios modificados
    $colores_ids = $_POST['color_id']; // Array de IDs de colores
    $tallas_ids = $_POST['talla_id']; // Array de IDs de tallas
    $cantidades = $_POST['cantidad']; // Array de cantidades

    // Calcular el total de la venta
    $total_venta = 0;

    // Validar stock y preparar datos para la venta
    foreach ($productos_ids as $index => $id_producto) {
        $cantidad_vendida = $cantidades[$index];
        $id_color = $colores_ids[$index];
        $id_talla = $tallas_ids[$index];

        // Consultar el stock disponible
        $stmt_stock = $pdo->prepare("
            SELECT cantidad_stock 
            FROM inventario 
            WHERE id_producto = ? AND id_color = ? AND id_talla = ?
        ");
        $stmt_stock->execute([$id_producto, $id_color, $id_talla]);
        $inventario = $stmt_stock->fetch(PDO::FETCH_ASSOC);

        if (!$inventario || $inventario['cantidad_stock'] < $cantidad_vendida) {
            throw new Exception("No hay suficiente stock disponible para el producto con ID $id_producto, color $id_color, talla $id_talla.");
        }

        // Sumar al total de la venta
        $total_venta += $precios_venta[$index] * $cantidad_vendida;
    }

    // Insertar la venta en la tabla "ventas"
    $stmt_venta = $pdo->prepare("
        INSERT INTO ventas (id_cliente, id_usuario, total_venta, metodo_pago, fecha_venta)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt_venta->execute([$id_cliente, $_SESSION['usuario_id'], $total_venta, $metodo_pago]);
    $id_venta = $pdo->lastInsertId(); // Obtener el ID de la venta recién creada

    // Insertar los detalles de la venta y actualizar el inventario
    foreach ($productos_ids as $index => $id_producto) {
        $precio_venta = $precios_venta[$index];
        $id_color = $colores_ids[$index];
        $id_talla = $tallas_ids[$index];
        $cantidad_vendida = $cantidades[$index];

        // Calcular el subtotal
        $subtotal = $precio_venta * $cantidad_vendida;

        // Insertar los detalles de la venta
        $stmt_detalle = $pdo->prepare("
            INSERT INTO detalles_venta (id_venta, id_producto, id_color, id_talla, cantidad, precio_venta, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt_detalle->execute([$id_venta, $id_producto, $id_color, $id_talla, $cantidad_vendida, $precio_venta, $subtotal]);

        // Actualizar el inventario
        $stmt_actualizar_inventario = $pdo->prepare("
            UPDATE inventario 
            SET cantidad_stock = cantidad_stock - ? 
            WHERE id_producto = ? AND id_color = ? AND id_talla = ?
        ");
        $stmt_actualizar_inventario->execute([$cantidad_vendida, $id_producto, $id_color, $id_talla]);
    }

    // Redirigir al usuario a la lista de ventas
    header("Location: ../../../views/ventas/lista_ventas.php");
    exit;
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>