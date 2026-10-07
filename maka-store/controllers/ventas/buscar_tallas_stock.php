<?php
// controllers/ventas/buscar_tallas_stock.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$id_producto = $_GET['id_producto'] ?? null;
$id_punto_venta = $_GET['id_punto_venta'] ?? null;
$id_color = $_GET['id_color'] ?? null;

if (!is_numeric($id_producto) || !is_numeric($id_punto_venta) || !is_numeric($id_color)) {
    echo json_encode(['error' => 'ID de producto, punto de venta o color no válido.']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            t.id_talla,
            t.nombre_talla,
            i.cantidad_stock
        FROM
            inventario i
        JOIN
            tallas t ON i.id_talla = t.id_talla
        WHERE
            i.id_producto = :id_producto AND
            i.id_punto_venta = :id_punto_venta AND
            i.id_color = :id_color AND
            i.cantidad_stock > 0
        GROUP BY
            t.id_talla
        ORDER BY
            t.nombre_talla ASC
    ");
    $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
    $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->bindParam(':id_color', $id_color, PDO::PARAM_INT);
    $stmt->execute();
    $tallas_con_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($tallas_con_stock);
} catch (PDOException $e) {
    error_log("Error al buscar tallas en stock: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos.']);
}
?>