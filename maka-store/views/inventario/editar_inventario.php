<?php
require_once '../../includes/db.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    die("ID de inventario no válido");
}

$stmt = $pdo->prepare("SELECT * FROM inventario WHERE id_inventario = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    die("Inventario no encontrado");
}
?>

<?php include_once '../../partials/menu.php'; ?>
<h2>Editar Inventario</h2>

<form action="../controllers/inventario/actualizar_inventario.php" method="POST">
    <input type="hidden" name="id_inventario" value="<?= $item['id_inventario'] ?>">

    <label>Cantidad en Stock:</label>
    <input type="number" name="cantidad_stock" value="<?= $item['cantidad_stock'] ?>" min="0" required>

    <button type="submit">Guardar Cambios</button>
</form>