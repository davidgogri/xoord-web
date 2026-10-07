<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../partials/menu.php';
?>

<h2>Registrar Nueva Talla</h2>

<form action="../../controllers/tallas/registrar_talla.php" method="POST">
    <label>Nombre de la talla:</label>
    <input type="text" name="nombre_talla" placeholder="Ej. S, M, L, XL" required>

    <button type="submit">Guardar Talla</button>
</form>