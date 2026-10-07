<?php
// controllers/inventario/buscar_colores_por_producto.php

// Revisa si ya hay una sesión iniciada. Si no, la inicia.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegura que las peticiones se realicen a través de AJAX y que el usuario tenga el rol de administrador
if ($_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest' || !isset($_SESSION['usuario_id']) || !isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acceso denegado. Se requiere autenticación y rol de administrador.']);
    exit;
}

// Configura la cabecera para que la respuesta sea en formato JSON
header('Content-Type: application/json');

// Incluye la conexión a la base de datos
require_once __DIR__ . '/../../includes/db.php';

// 1. Validar y sanitizar las entradas
$id_producto = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
$id_punto_venta = filter_input(INPUT_GET, 'id_punto_venta', FILTER_VALIDATE_INT);

// 2. Verificar que los parámetros no estén vacíos
if ($id_producto === false || $id_punto_venta === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros de producto o punto de venta inválidos.']);
    exit;
}

try {
    // 3. Consulta SQL para obtener los colores disponibles para el producto y punto de venta
    // Usamos JOIN para vincular el inventario con la tabla de colores y obtener el nombre del color
    $sql = "SELECT DISTINCT c.id_color, c.nombre_color
            FROM inventario i
            JOIN colores c ON i.id_color = c.id_color
            WHERE i.id_producto = :id_producto AND i.id_punto_venta = :id_punto_venta
            ORDER BY c.nombre_color ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
    $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
    $stmt->execute();

    // 4. Obtener todos los resultados
    $colores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Devolver los resultados en formato JSON
    echo json_encode($colores);

} catch (PDOException $e) {
    // 6. Manejo de errores de base de datos
    error_log("Error de base de datos en buscar_colores_por_producto: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Error al cargar los colores.']);
}
?>
