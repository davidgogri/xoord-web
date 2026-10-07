<?php include_once '../partials/menu.php'; ?>
<h2>Ajustar Stock Manualmente</h2>

<form action="../controllers/inventario/ajustar_stock.php" method="POST">
    <label>ID del Inventario:</label>
    <input type="number" name="id_inventario" required>

    <label>Nueva Cantidad:</label>
    <input type="number" name="nueva_cantidad" min="0" required>

    <button type="submit">Ajustar Stock</button>
</form>