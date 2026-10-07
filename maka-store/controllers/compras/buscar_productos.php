<?php
// controllers/compras/buscar_productos.php

// Habilitar visualización de errores (SOLO PARA DEPURACIÓN, QUITAR EN PRODUCCIÓN)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Asegúrate de que la ruta a db.php es correcta desde este archivo
// Desde controllers/compras/ hasta includes/db.php es ../../includes/db.php
require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json'); // ¡Muy importante para que JS lo interprete como JSON!

$productos = [];
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) { // Puedes ajustar a 3 si prefieres
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id_producto,
            nombre AS label,          -- 'label' es lo que jQuery UI muestra
            precio_compra             -- Necesitamos esto para poblar el campo
        FROM
            productos
        WHERE
            nombre LIKE :query_param
        ORDER BY
            nombre ASC
        LIMIT 10
    ");
    $stmt->bindValue(':query_param', '%' . $query . '%');
    $stmt->execute();
    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($productos);

} catch (PDOException $e) {
    error_log("Error al buscar productos para compras: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al buscar productos: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("Error inesperado en buscar_productos para compras: " . $e->getMessage());
    echo json_encode(['error' => 'Ocurrió un error inesperado al buscar productos: ' . $e->getMessage()]);
    exit;
}
?>