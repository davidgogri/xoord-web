<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

require_once '../../includes/db.php';

$stmt = $pdo->prepare("SELECT * FROM productos WHERE id_producto = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$producto) {
    die("Producto no encontrado");
}
?>

<?php include '../../partials/menu.php'; ?>
<h2>Editar Producto</h2>

<form action="../../controllers/productos/actualizar_producto.php" method="POST">
    <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($producto['nombre']) ?>" required><br>

    <label>Descripción (opcional):</label>
    <textarea name="descripcion"><?= htmlspecialchars($producto['descripcion']) ?></textarea><br>

    <label>Precio de Compra:</label>
    <input type="number" step="0.01" name="precio_compra" value="<?= $producto['precio_compra'] ?>" required><br>

    <label>Precio de Venta:</label>
    <input type="number" step="0.01" name="precio_venta" value="<?= $producto['precio_venta'] ?>" required><br>

    <button type="submit">Guardar Cambios</button>
</form>