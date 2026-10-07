<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}
require_once '../../includes/db.php';

// Consulta SQL para obtener todos los datos del inventario
$query = "SELECT Producto, Talla, Stock, Costo FROM v_Prod_Inventario where Stock > 0 ORDER BY Producto ASC";

try {
    // Preparar y ejecutar la consulta
    $stmt = $pdo->query($query);
    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calcular sumatorias
    $total_stock = 0;
    $total_costo = 0;

    foreach ($inventario as $item) {
        $total_stock += $item['Stock'];
        $total_costo += $item['Costo'];
    }

    // Redirigir a la vista
    include '../../views/inventario/reporte_inventario.php';
} catch (PDOException $e) {
    // Captura errores de la consulta
    die("Error en la consulta: " . $e->getMessage());
}
?>