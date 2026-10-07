<?php
// controllers/compras/buscar_colores.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../includes/db.php'; // Ajusta la ruta si es necesario

header('Content-Type: application/json');

$colores = [];
$query = $_GET['q'] ?? '';

if (strlen($query) < 2) { // Puedes ajustar a 3
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            id_color,
            nombre_color AS label
        FROM
            colores
        WHERE
            nombre_color LIKE :query_param
        ORDER BY
            nombre_color ASC
        LIMIT 10
    ");
    $stmt->bindValue(':query_param', '%' . $query . '%');
    $stmt->execute();
    $colores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($colores);

} catch (PDOException $e) {
    error_log("Error al buscar colores para compras: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al buscar colores: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("Error inesperado en buscar_colores para compras: " . $e->getMessage());
    echo json_encode(['error' => 'Ocurrió un error inesperado al buscar colores: ' . $e->getMessage()]);
    exit;
}
?>