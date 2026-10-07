<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Ruta absoluta corregida (sin duplicar 'maka-store')
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$q = $_GET['q'] ?? '';

if ($q) {
    try {
        $stmt = $pdo->prepare("SELECT id_producto, nombre FROM productos WHERE nombre LIKE ?");
        $stmt->execute(["%$q%"]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($resultados);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Error en la consulta: ' . $e->getMessage()]);
    }
} else {
    echo json_encode([]);
}
?>