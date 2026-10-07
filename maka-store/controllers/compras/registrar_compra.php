<?php
// Iniciar la sesión
session_start(); //

// Mostrar errores para depuración
ini_set('display_errors', 1); //
ini_set('display_startup_errors', 1); //
error_reporting(E_ALL); //

// **IMPORTANTE:** Ajusta esta ruta si es necesario para que sea consistente.
// Si tu proyecto 'maka-store' está en public_html, y 'includes' está dentro de 'maka-store',
// y este archivo está en 'maka-store/controllers/compras/', entonces la ruta relativa es '../../includes/db.php'.
// Si la ruta absoluta con DOCUMENT_ROOT te funcionaba, puedes seguir usándola, pero es vital que apunte al mismo db.php
require_once __DIR__ . '/../../includes/db.php'; //

try {
    // Verificar que el usuario está logueado
    if (!isset($_SESSION['usuario_id'])) { //
        throw new Exception("Debes iniciar sesión para registrar una compra."); //
    }

    // Datos generales de la compra
    $id_proveedor = $_POST['id_proveedor']; //
    // **NUEVO: Recoger el id_punto_venta**
    $id_punto_venta = $_POST['id_punto_venta'];
    $metodo_pago = $_POST['metodo_pago']; //
    $total_compra = 0; // Calcularemos el total más adelante
    $id_usuario = $_SESSION['usuario_id']; //

    // Validar que se hayan enviado productos
    if (empty($_POST['producto_id']) || empty($_POST['cantidad']) || empty($_POST['precio_compra'])) { //
        throw new Exception("Debe agregar al menos un producto para registrar la compra."); //
    }

    // Iniciar transacción
    $pdo->beginTransaction(); //

    // Insertar en la tabla `compras`
    // **MODIFICADO: Añadir id_punto_venta al INSERT de compras**
    $stmtCompra = $pdo->prepare("
        INSERT INTO compras (id_proveedor, id_usuario, total_compra, metodo_pago, fecha_compra, id_punto_venta)
        VALUES (?, ?, ?, ?, NOW(), ?)
    "); //
    $stmtCompra->execute([$id_proveedor, $id_usuario, $total_compra, $metodo_pago, $id_punto_venta]); //

    // Obtener el ID de la compra recién insertada
    $id_compra = $pdo->lastInsertId(); //

    // Procesar los productos
    $productos = $_POST['producto_id']; //
    $colores = $_POST['color_id']; //
    $tallas = $_POST['talla_id']; //
    $cantidades = $_POST['cantidad']; //
    $precios = $_POST['precio_compra']; //

    foreach ($productos as $index => $id_producto) { //
        $id_color = $colores[$index]; //
        $id_talla = $tallas[$index]; //
        $cantidad = $cantidades[$index]; //
        $precio_compra = $precios[$index]; //
        $subtotal = $cantidad * $precio_compra; //

        // Insertar en la tabla `detalles_compra`
        $stmtDetalle = $pdo->prepare("
            INSERT INTO detalles_compra (id_compra, id_producto, id_color, id_talla, cantidad, precio_compra, subtotal)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        "); //
        $stmtDetalle->execute([$id_compra, $id_producto, $id_color, $id_talla, $cantidad, $precio_compra, $subtotal]); //

        // Acumular el total de la compra
        $total_compra += $subtotal; //

        // Actualizar el inventario
        // **MODIFICADO: Incluir id_punto_venta en la búsqueda de inventario**
        $stmtInventario = $pdo->prepare("
            SELECT id_inventario, cantidad_stock
            FROM inventario
            WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ?
        "); //
        $stmtInventario->execute([$id_producto, $id_color, $id_talla, $id_punto_venta]); //
        $inventario = $stmtInventario->fetch(PDO::FETCH_ASSOC); //

        if ($inventario) { //
            // Si el producto ya existe en el inventario para ese punto de venta, actualizar la cantidad
            $nueva_cantidad = $inventario['cantidad_stock'] + $cantidad; //
            // **MODIFICADO: Actualizar por id_inventario que ya tiene el id_punto_venta implícito**
            $stmtActualizar = $pdo->prepare("
                UPDATE inventario
                SET cantidad_stock = ?
                WHERE id_inventario = ?
            "); //
            $stmtActualizar->execute([$nueva_cantidad, $inventario['id_inventario']]); //
        } else {
            // Si el producto no existe en el inventario para ese punto de venta, insertar un nuevo registro
            // **MODIFICADO: Añadir id_punto_venta al INSERT de inventario**
            $stmtInsertar = $pdo->prepare("
                INSERT INTO inventario (id_producto, id_color, id_talla, cantidad_stock, id_punto_venta)
                VALUES (?, ?, ?, ?, ?)
            "); //
            $stmtInsertar->execute([$id_producto, $id_color, $id_talla, $cantidad, $id_punto_venta]); //
        }
    }

    // Actualizar el total de la compra en la tabla `compras`
    $stmtActualizarTotal = $pdo->prepare("UPDATE compras SET total_compra = ? WHERE id_compra = ?"); //
    $stmtActualizarTotal->execute([$total_compra, $id_compra]); //

    // Confirmar la transacción
    $pdo->commit(); //

    // Redirigir al usuario
    header("Location: ../../views/compras/listar_compras.php"); //
    exit; //
} catch (Exception $e) {
    // Revertir la transacción en caso de error
    $pdo->rollBack(); //
    error_log("Error al registrar la compra: " . $e->getMessage()); //
    echo "Error al registrar la compra. Por favor, inténtalo de nuevo."; //
}
?>