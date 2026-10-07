<?php
// controllers/inventario/buscar_productos.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php';
header('Content-Type: application/json');

$productos = [];
$query = $_GET['q'] ?? '';
$id_punto_venta = $_GET['id_punto_venta'] ?? null;

if (empty($id_punto_venta) || !is_numeric($id_punto_venta)) {
    echo json_encode(['error' => 'ID de punto de venta no válido o no proporcionado.']);
    exit;
}

try {
    $sql = "
        SELECT distinct
            p.id_producto,
            p.nombre AS label
        FROM
            productos p
        JOIN
            inventario i ON p.id_producto = i.id_producto
        WHERE
            i.id_punto_venta = :id_punto_venta
            AND i.cantidad_stock > 0
            AND p.nombre LIKE :query_param
        ORDER BY
            p.nombre ASC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->bindValue(':query_param', '%' . $query . '%');
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($productos);

} catch (PDOException $e) {
    error_log("Error al buscar productos en buscar_productos.php (inventario): " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos.']);
    exit;
}
?>