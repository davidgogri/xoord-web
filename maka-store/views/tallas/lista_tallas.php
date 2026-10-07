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

<h2>Listado de Tallas</h2>

<a href="nueva_talla.php">➕ Agregar Nueva Talla</a>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Talla</th>
        </tr>
    </thead>
    <tbody>
        <?php
        $stmt = $pdo->query("SELECT * FROM tallas");
        while ($talla = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td><?= htmlspecialchars($talla['id_talla']) ?></td>
            <td><?= htmlspecialchars($talla['nombre_talla']) ?></td>
        </tr>
        <?php } ?>
    </tbody>
</table>