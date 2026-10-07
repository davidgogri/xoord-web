<?php
// controllers/ventas/buscar_colores.php

// Activar la visualización de errores para depuración (IMPRESCINDIBLE MIENTRAS EL ERROR PERSISTA)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
require_once __DIR__ . '/../../includes/db.php';

// Establecer el encabezado para indicar que la respuesta será JSON.
header('Content-Type: application/json');

$colores_response = []; // Usar un nombre de variable diferente
$query = $_GET['q'] ?? ''; // Obtener la consulta de búsqueda

// Validar la longitud mínima de la consulta
if (strlen($query) < 2) { // Cambiado a 2 caracteres
    echo json_encode([]);
    exit;
}

try {
    // Preparar la consulta SQL para buscar colores por nombre
    $stmt = $pdo->prepare("
        SELECT
            id_color,
            nombre_color,
            nombre_color AS label -- Añadimos el alias 'label' para autocompletado
        FROM
            colores
        WHERE
            nombre_color LIKE :query
        ORDER BY
            nombre_color ASC
        LIMIT 10
    ");

    // Enlazar el parámetro y ejecutar
    $param = '%' . $query . '%';
    $stmt->bindValue(':query', $param, PDO::PARAM_STR);
    $stmt->execute();

    // Recoger los resultados directamente
    $colores_response = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enviar la respuesta JSON y terminar
    echo json_encode($colores_response);
    exit;

} catch (PDOException $e) {
    // Capturar y loguear errores de la base de datos
    error_log("PDO Error en buscar_colores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error de base de datos al buscar colores: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    // Capturar cualquier otra excepción inesperada
    error_log("Error inesperado en buscar_colores.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error inesperado al buscar colores: ' . $e->getMessage()]);
    exit;
}
?>