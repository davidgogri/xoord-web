<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../partials/menu.php';
require_once '../../includes/db.php';
?>

<h2>Listado de Clientes</h2>

<a href="nuevo_cliente.php">➕ Agregar Nuevo Cliente</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Email</th>
            <th>Teléfono</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM clientes");
        while ($cliente = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?= htmlspecialchars($cliente['id_cliente']) ?></td>
            <td><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></td>
            <td><?= htmlspecialchars($cliente['email']) ?></td>
            <td><?= htmlspecialchars($cliente['celular']) ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>