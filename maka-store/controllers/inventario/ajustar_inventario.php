<?php
// controllers/inventario/ajustar_inventario.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = 'Método de solicitud no permitido.';
    echo json_encode($response);
    exit;
}

if (!isset($_SESSION['usuario_id'])) {
    $response['message'] = 'Debe iniciar sesión para realizar esta acción.';
    echo json_encode($response);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['id_producto'], $data['id_color'], $data['id_talla'], $data['id_punto_venta'], $data['cantidad'], $data['accion'])) {
    $response['message'] = 'Datos incompletos. Faltan campos requeridos.';
    echo json_encode($response);
    exit;
}

$id_producto = $data['id_producto'];
$id_color = $data['id_color'];
$id_talla = $data['id_talla'];
$id_punto_venta = $data['id_punto_venta'];
$cantidad = intval($data['cantidad']);
$accion = $data['accion']; // 'sumar' o 'restar'
$observacion = $data['observacion'] ?? '';
$id_usuario = $_SESSION['usuario_id'];

if ($cantidad <= 0) {
    $response['message'] = 'La cantidad debe ser un número positivo.';
    echo json_encode($response);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. Obtener el registro de inventario actual
    $stmt = $pdo->prepare("SELECT id_inventario, cantidad_stock FROM inventario WHERE id_producto = ? AND id_color = ? AND id_talla = ? AND id_punto_venta = ?");
    $stmt->execute([$id_producto, $id_color, $id_talla, $id_punto_venta]);
    $inventario_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inventario_item) {
        $response['message'] = 'No se encontró el producto en el inventario para el punto de venta y características seleccionadas.';
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }

    $id_inventario = $inventario_item['id_inventario'];
    $cantidad_anterior = $inventario_item['cantidad_stock'];
    $cantidad_nueva = $cantidad_anterior;

    if ($accion === 'sumar') {
        $cantidad_nueva += $cantidad;
    } elseif ($accion === 'restar') {
        if ($cantidad_anterior < $cantidad) {
            $response['message'] = 'No se puede restar esa cantidad, el stock es insuficiente.';
            $pdo->rollBack();
            echo json_encode($response);
            exit;
        }
        $cantidad_nueva -= $cantidad;
    } else {
        $response['message'] = 'Acción de ajuste no válida.';
        $pdo->rollBack();
        echo json_encode($response);
        exit;
    }

    // 2. Actualizar el stock
    $stmt_update = $pdo->prepare("UPDATE inventario SET cantidad_stock = ? WHERE id_inventario = ?");
    $stmt_update->execute([$cantidad_nueva, $id_inventario]);

    // 3. Registrar el ajuste en una tabla de auditoría (opcional pero muy recomendado)
    // Esto te permitirá tener un historial de todos los cambios de inventario
    // Asegúrate de tener una tabla 'auditoria_inventario' con los campos:
    // id_inventario, id_producto, id_color, id_talla, cantidad_anterior, cantidad_nueva, diferencia, observacion, id_usuario, fecha_accion
    
    $diferencia = ($accion === 'sumar') ? $cantidad : -$cantidad;
    $stmt_log = $pdo->prepare("INSERT INTO auditoria_inventario (id_inventario, id_producto, id_color, id_talla, cantidad_anterior, cantidad_nueva, diferencia, observacion, id_usuario) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_log->execute([$id_inventario, $id_producto, $id_color, $id_talla, $cantidad_anterior, $cantidad_nueva, $diferencia, $observacion, $id_usuario]);
    
    $pdo->commit();

    $response['success'] = true;
    $response['message'] = 'Inventario ajustado correctamente. Nuevo stock: ' . $cantidad_nueva;

} catch (PDOException $e) {
    $pdo->rollBack();
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
    error_log("Error en ajustar_inventario.php: " . $e->getMessage());
}

echo json_encode($response);
?>