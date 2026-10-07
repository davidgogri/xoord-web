<?php
// controllers/cambios/gestionar_cambio.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Verificación de Usuario y Conexión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}
require_once __DIR__ . '/../../includes/db.php'; // Asumiendo $pdo

$id_usuario = $_SESSION['usuario_id'];
$success_message = '';
$error_message = '';

// 2. Validación de CSRF Token (patrón de nueva_venta.php)
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $error_message = "Error de seguridad: Token CSRF inválido o faltante.";
    goto end_script;
}

// 3. Recolección y Validación de Datos
$tipo_origen = $_POST['tipo_origen'] ?? '';
$id_origen = (int) ($_POST['id_origen'] ?? 0);
$id_punto_venta = (int) ($_POST['id_punto_venta'] ?? 0);
$observaciones = trim($_POST['observaciones'] ?? '');

// Producto que SALE (del detalle original)
$id_producto_sale = (int) ($_POST['id_producto_sale'] ?? 0);
$id_color_sale = (int) ($_POST['id_color_sale'] ?? 0);
$id_talla_sale = (int) ($_POST['id_talla_sale'] ?? 0);
$cantidad_sale = (int) ($_POST['cantidad_sale'] ?? 0);

// Producto que ENTRA (el reemplazo)
$id_producto_entra = (int) ($_POST['id_producto_entra'] ?? 0);
$id_color_entra = (int) ($_POST['id_color_entra'] ?? 0);
$id_talla_entra = (int) ($_POST['id_talla_entra'] ?? 0);
$cantidad_entra = (int) ($_POST['cantidad_entra'] ?? 0);

if (!in_array($tipo_origen, ['venta', 'compra']) || $id_origen <= 0 || $id_punto_venta <= 0 || 
    $cantidad_sale <= 0 || $cantidad_entra <= 0 || $id_producto_sale <= 0 || $id_producto_entra <= 0) {
    $error_message = "Validación fallida: Faltan campos requeridos o son inválidos.";
    goto end_script;
}


// 4. INICIAR TRANSACCIÓN DE BASE DE DATOS
$pdo->beginTransaction();

try {
    // A. INSERTAR EN cambios_inventario
    $sql_cambio = "INSERT INTO cambios_inventario 
                   (id_origen, tipo_origen, id_usuario, id_punto_venta, observaciones) 
                   VALUES (?, ?, ?, ?, ?)";
    $stmt_cambio = $pdo->prepare($sql_cambio);
    $stmt_cambio->execute([$id_origen, $tipo_origen, $id_usuario, $id_punto_venta, $observaciones]);
    $id_cambio = $pdo->lastInsertId();

    // B. INSERTAR EN detalles_cambio_inventario
    $sql_detalle = "INSERT INTO detalles_cambio_inventario 
                    (id_cambio, id_producto_sale, id_color_sale, id_talla_sale, cantidad_sale, 
                     id_producto_entra, id_color_entra, id_talla_entra, cantidad_entra) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_detalle = $pdo->prepare($sql_detalle);
    $stmt_detalle->execute([
        $id_cambio, 
        $id_producto_sale, $id_color_sale, $id_talla_sale, $cantidad_sale, 
        $id_producto_entra, $id_color_entra, $id_talla_entra, $cantidad_entra
    ]);


    // C. AJUSTE DE INVENTARIO (Doble Movimiento)

    // C1. Movimiento del Producto Original (que se devuelve/saca del detalle)
    // VENTA: Producto original ENTRA de nuevo al stock (+).
    // COMPRA: Producto original SALE del stock (-).
    $operacion_sale = ($tipo_origen === 'venta') ? '+' : '-';
    $cantidad_a_validar_sale = ($tipo_origen === 'compra') ? $cantidad_sale : 0; // Solo validar stock si es resta (compra)

    $sql_inv_sale = "UPDATE inventario 
                     SET cantidad_stock = cantidad_stock {$operacion_sale} ? 
                     WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ? 
                     AND (cantidad_stock >= ? OR ? = 0)"; 

    $stmt_inv_sale = $pdo->prepare($sql_inv_sale);
    $stmt_inv_sale->execute([
        $cantidad_sale, 
        $id_producto_sale, $id_color_sale, $id_talla_sale, $id_punto_venta, 
        $cantidad_a_validar_sale, $cantidad_a_validar_sale
    ]);

    if ($stmt_inv_sale->rowCount() === 0) {
        throw new Exception("Error al ajustar inventario (Producto original). Stock insuficiente o combinación no existe.");
    }

    // C2. Movimiento del Producto Nuevo (el reemplazo)
    // VENTA: Producto nuevo SALE del stock (-).
    // COMPRA: Producto nuevo ENTRA al stock (+).
    $operacion_entra = ($tipo_origen === 'venta') ? '-' : '+';
    $cantidad_a_validar_entra = ($tipo_origen === 'venta') ? $cantidad_entra : 0; // Solo validar stock si es resta (venta)

    $sql_inv_entra = "UPDATE inventario 
                      SET cantidad_stock = cantidad_stock {$operacion_entra} ? 
                      WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ?
                      AND (cantidad_stock >= ? OR ? = 0)"; 

    $stmt_inv_entra = $pdo->prepare($sql_inv_entra);
    $stmt_inv_entra->execute([
        $cantidad_entra, 
        $id_producto_entra, $id_color_entra, $id_talla_entra, $id_punto_venta,
        $cantidad_a_validar_entra, $cantidad_a_validar_entra
    ]);

    if ($stmt_inv_entra->rowCount() === 0) {
        // En este punto, si falla y es una venta, el stock es insuficiente para el reemplazo.
        // Si falla y es una compra, es un error de ID.
        throw new Exception("Error al ajustar inventario (Producto de reemplazo). Stock insuficiente o combinación no existe.");
    }
    
    // D. CONFIRMAR TRANSACCIÓN
    $pdo->commit();
    $success_message = "Cambio de {$tipo_origen} ID {$id_origen} registrado con éxito. ID de Cambio: {$id_cambio}";

} catch (Exception $e) {
    // REVERTIR TRANSACCIÓN
    $pdo->rollBack();
    $error_message = "Error en la transacción de cambio: " . $e->getMessage();
    error_log("Error al gestionar cambio: " . $e->getMessage());
} catch (PDOException $e) {
     $pdo->rollBack();
     $error_message = "Error de base de datos en el ajuste de inventario: " . $e->getMessage();
     error_log("Error de BD: " . $e->getMessage());
}

end_script:
// 5. Redirigir y limpiar la sesión
$_SESSION['message'] = $success_message ?: $error_message;
$_SESSION['message_type'] = $success_message ? 'success' : 'danger';
header("Location: ../../dashboard.php?page=cambios"); // Ajusta a tu ruta de cambios
exit;