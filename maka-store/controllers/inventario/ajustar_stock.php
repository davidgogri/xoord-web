<?php
require_once '../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_inventario'];
    $cantidad = $_POST['nueva_cantidad'];

    try {
        $stmt = $pdo->prepare("UPDATE inventario SET cantidad_stock = ? WHERE id_inventario = ?");
        $stmt->execute([$cantidad, $id]);

        header("Location: ../../inventario/lista_inventario.php");
        exit;
    } catch (PDOException $e) {
        die("Error al ajustar stock: " . $e->getMessage());
    }
}
?>