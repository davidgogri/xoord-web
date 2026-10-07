<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../..//includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

$stmt = $pdo->prepare("SELECT * FROM proveedores WHERE id_proveedor = ?");
$stmt->execute([$id]);
$proveedor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$proveedor) {
    die("Proveedor no encontrado");
}
?>

<?php include_once '../../partials/menu.php'; ?>
<h2>Editar Proveedor</h2>

<form action="../controllers/proveedores/editar_proveedor.php" method="POST">
    <input type="hidden" name="id_proveedor" value="<?= $proveedor['id_proveedor'] ?>">

    <label>Nombre de la Empresa:</label>
    <input type="text" name="nombre_empresa" value="<?= htmlspecialchars($proveedor['nombre_empresa']) ?>" required>

    <label>Contacto:</label>
    <input type="text" name="contacto" value="<?= htmlspecialchars($proveedor['contacto']) ?>">

    <label>Teléfono:</label>
    <input type="text" name="telefono" value="<?= htmlspecialchars($proveedor['telefono']) ?>">

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($proveedor['email']) ?>">

    <label>Dirección:</label>
    <textarea name="direccion"><?= htmlspecialchars($proveedor['direccion']) ?></textarea>

    <label>Productos que provee:</label>
    <textarea name="productos_que_proveen"><?= htmlspecialchars($proveedor['productos_que_proveen']) ?></textarea>

    <button type="submit">Guardar Cambios</button>
</form>