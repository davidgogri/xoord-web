<?php
require_once '../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_inventario'];
    $cantidad_stock = $_POST['cantidad_stock'];

    try {
        $stmt = $pdo->prepare("UPDATE inventario SET cantidad_stock = ? WHERE id_inventario = ?");
        $stmt->execute([$cantidad_stock, $id]);

        header("Location: ../../inventario/lista_inventario.php");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar: " . $e->getMessage());
    }
}
?>