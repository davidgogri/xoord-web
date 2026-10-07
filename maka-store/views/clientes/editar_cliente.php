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

$stmt = $pdo->prepare("SELECT * FROM clientes WHERE id_cliente = ?");
$stmt->execute([$id]);
$cliente = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$cliente) {
    die("Cliente no encontrado");
}
?>

<?php include '../../partials/menu.php'; ?>
<h2>Editar Cliente</h2>

<form action="../../controllers/clientes/editar_cliente.php" method="POST">
    <input type="hidden" name="id_cliente" value="<?= $cliente['id_cliente'] ?>">

    <label>Nombre:</label>
    <input type="text" name="nombre" value="<?= htmlspecialchars($cliente['nombre']) ?>" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido" value="<?= htmlspecialchars($cliente['apellido']) ?>"><br>

    <label>Celular:</label>
    <input type="text" name="celular" value="<?= htmlspecialchars($cliente['celular']) ?>"><br>

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($cliente['email']) ?>"><br>

    <label>Instagram:</label>
    <input type="text" name="instagram" value="<?= htmlspecialchars($cliente['instagram']) ?>"><br>

    <label>Fecha de Cumpleaños:</label>
    <input type="date" name="fecha_cumpleanos" value="<?= $cliente['fecha_cumpleanos'] ?>"><br>

    <button type="submit">Guardar Cambios</button>
</form>