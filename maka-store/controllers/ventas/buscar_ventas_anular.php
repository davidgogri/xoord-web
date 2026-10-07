<?php
// Habilitar visualización de errores (desactivar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '../../includes/db.php'; // Ajusta la ruta si es necesario

header('Content-Type: application/json'); // Indicar que la respuesta es JSON

$results = [];
try {
    if (isset($_GET['q']) && !empty($_GET['q'])) {
        $query = '%' . $_GET['q'] . '%';

        // Buscar ventas por ID, nombre de cliente o fecha
        $stmt = $pdo->prepare("
            SELECT 
                v.id_venta, 
                c.nombre AS nombre_cliente, 
                v.total_venta, 
                v.fecha_venta
            FROM 
                ventas v
            JOIN 
                clientes c ON v.id_cliente = c.id_cliente
            WHERE 
                v.tipo_venta = 'Venta' AND (
                    CAST(v.id_venta AS CHAR) LIKE ? OR
                    c.nombre LIKE ? OR
                    v.fecha_venta LIKE ?
                )
            ORDER BY v.fecha_venta DESC, v.id_venta DESC
            LIMIT 10
        ");
        $stmt->execute([$query, $query, $query]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // En un entorno de producción, solo loguear el error y devolver un array vacío o un mensaje genérico.
    error_log("Error en buscar_ventas_anular.php: " . $e->getMessage());
    // Puedes devolver un error JSON si lo prefieres para depuración en el frontend
    // echo json_encode(['error' => 'Error al buscar ventas.']);
    // exit;
}

echo json_encode($results);
?>