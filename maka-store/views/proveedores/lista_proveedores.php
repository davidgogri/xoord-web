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

<h2>Listado de Proveedores</h2>

<a href="nuevo_proveedor.php">➕ Agregar Nuevo Proveedor</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Empresa</th>
            <th>Contacto</th>
            <th>Teléfono</th>
            <th>Email</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM proveedores WHERE estado = 'activo'");
        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?= htmlspecialchars($p['id_proveedor']) ?></td>
            <td><?= htmlspecialchars($p['nombre_empresa']) ?></td>
            <td><?= htmlspecialchars($p['contacto']) ?></td>
            <td><?= htmlspecialchars($p['telefono']) ?></td>
            <td><?= htmlspecialchars($p['email']) ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>