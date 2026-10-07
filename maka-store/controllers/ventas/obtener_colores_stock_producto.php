<?php
// Habilitar la visualización de errores (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// No iniciar sesión aquí a menos que sea estrictamente necesario para la búsqueda,
// ya que esta es una API para JS. La seguridad de sesión se maneja en las vistas.

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

$results = [];

// Obtener los parámetros de la URL
$id_producto = filter_input(INPUT_GET, 'id_producto', FILTER_VALIDATE_INT);
$id_talla = filter_input(INPUT_GET, 'id_talla', FILTER_VALIDATE_INT);
// --- NUEVO: Obtener el ID del punto de venta ---
$id_punto_venta = filter_input(INPUT_GET, 'id_punto_venta', FILTER_VALIDATE_INT);

if ($id_producto === false || $id_talla === false || $id_punto_venta === false) {
    // Si algún ID no es un entero válido o es nulo
    echo json_encode([]);
    exit;
}

try {
    // Consulta para obtener los colores disponibles y su cantidad en inventario
    // para un producto, talla Y PUNTO DE VENTA específicos
    $stmt = $pdo->prepare("
        SELECT
            i.id_color,
            c.nombre_color,
            i.cantidad AS cantidad_disponible
        FROM
            inventario i
        JOIN
            colores c ON i.id_color = c.id_color
        WHERE
            i.id_producto = :id_producto AND
            i.id_talla = :id_talla AND
            i.id_punto_venta = :id_punto_venta AND
            i.cantidad > 0
        ORDER BY
            c.nombre_color ASC
    ");

    $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
    $stmt->bindParam(':id_talla', $id_talla, PDO::PARAM_INT);
    $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT); // Nuevo parámetro

    $stmt->execute();
    $colores_stock = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($colores_stock as $item) {
        $results[] = [
            'id_color' => $item['id_color'],
            'nombre_color' => $item['nombre_color'],
            'cantidad_disponible' => (int)$item['cantidad_disponible']
        ];
    }

} catch (PDOException $e) {
    error_log("Error al obtener colores y stock en obtener_colores_stock_producto.php: " . $e->getMessage());
    // No revelar errores detallados en producción
    echo json_encode(['error' => 'Error interno al obtener colores y stock.']);
}

echo json_encode($results);
?>