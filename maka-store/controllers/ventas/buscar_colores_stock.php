<?php
// controllers/ventas/buscar_colores_stock.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id_producto = $_GET['id_producto'] ?? null;
$id_punto_venta = $_GET['id_punto_venta'] ?? null;

if (!is_numeric($id_producto) || !is_numeric($id_punto_venta)) {
    echo json_encode(['error' => 'ID de producto o punto de venta no válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            c.id_color,
            c.nombre_color
        FROM
            inventario i
        JOIN
            colores c ON i.id_color = c.id_color
        WHERE
            i.id_producto = :id_producto AND
            i.id_punto_venta = :id_punto_venta AND
            i.cantidad_stock > 0
        GROUP BY
            c.id_color
        ORDER BY
            c.nombre_color ASC
    ");
    $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
    $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->execute();
    $colores_con_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($colores_con_stock);
} catch (PDOException $e) {
    error_log("Error al buscar colores en stock: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos.']);
}
?>