<?php
// controllers/ventas/buscar_productos.php

// Habilitar visualización de errores (SOLO PARA DEPURACIÓN, QUITAR EN PRODUCCIÓN)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Asegúrate de que la ruta a db.php es correcta desde este archivo
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json'); // ¡Muy importante!

$productos = [];
$query = $_GET['q'] ?? '';
$id_punto_venta = $_GET['id_punto_venta'] ?? null; // Obtener id_punto_venta de la URL

// Validar que tenemos un id_punto_venta válido y que la consulta tiene al menos 3 caracteres
if (empty($id_punto_venta) || !is_numeric($id_punto_venta)) {
    // Es crucial que devuelva un array vacío o un error específico
    // en lugar de lanzar un error PHP que no sea JSON, para que el frontend lo maneje.
    echo json_encode(['error' => 'ID de punto de venta no válido o no proporcionado.']);
    exit;
}

if (strlen($query) < 3) {
    // Si la consulta es demasiado corta, simplemente se devuelve un array vacío.
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT distinct
            p.id_producto,
            p.nombre AS label, -- Alias 'label' es crucial para el JS de autocompletado
            p.precio_venta
        FROM
            productos p
        JOIN -- Usar JOIN para asegurar que solo se muestren productos con inventario
            inventario i ON p.id_producto = i.id_producto
        WHERE
            i.id_punto_venta = :id_punto_venta
            AND i.cantidad_stock > 0
            AND p.descripcion LIKE :query_param
        ORDER BY
            p.nombre ASC
        LIMIT 10
    ");
    $stmt->bindValue(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->bindValue(':query_param', '%' . $query . '%');
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($productos);

} catch (PDOException $e) {
    // Log el error en el servidor
    error_log("Error al buscar productos en buscar_productos.php: " . $e->getMessage());
    // Envía un mensaje de error JSON al frontend
    echo json_encode(['error' => 'Error de base de datos al buscar productos: ' . $e->getMessage()]);
    exit; // Termina la ejecución para evitar enviar contenido adicional
}
?>