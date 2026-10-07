<?php
// controllers/ventas/get_stock_by_punto_venta.php

ini_set('display_errors', 1); // Activar para depuración
ini_set('display_startup_errors', 1); // Activar para depuración
error_reporting(E_ALL); // Reportar todos los errores

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$response = [];
$id_punto_venta = $_GET['id_punto_venta'] ?? null;

if (!isset($pdo)) {
    echo json_encode(['error' => 'Error: Conexión a la base de datos no disponible.']);
    exit;
}

if ($id_punto_venta === null || !is_numeric($id_punto_venta)) {
    echo json_encode(['error' => 'ID de punto de venta no válido o no proporcionado.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            s.id_producto,
            s.id_color,
            s.id_talla,
            s.cantidad_stock AS stock_disponible
        FROM
            inventario s
        WHERE
            s.id_punto_venta = :id_punto_venta
            AND s.cantidad_stock > 0
    ");
    $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->execute();
    $stock_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stock_data = [];
    foreach ($stock_raw as $item) {
        $key = $item['id_producto'] . '-' . $item['id_color'] . '-' . $item['id_talla'];
        $stock_data[$key] = (int)$item['stock_disponible'];
    }
    echo json_encode($stock_data);
    exit; // Terminar ejecución aquí

} catch (PDOException $e) {
    error_log("Error al obtener stock por punto de venta: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al cargar stock: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("Error inesperado en get_stock_by_punto_venta.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error inesperado al cargar stock: ' . $e->getMessage()]);
    exit;
}
?>