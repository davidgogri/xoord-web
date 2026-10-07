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

<h2>Registrar Nuevo Cliente</h2>

<form action="../../controllers/clientes/registrar_cliente.php" method="POST">
    <label>Nombre:</label>
    <input type="text" name="nombre" required><br>

    <label>Apellido:</label>
    <input type="text" name="apellido"><br>

    <label>Celular:</label>
    <input type="text" name="celular"><br>

    <label>Email:</label>
    <input type="email" name="email"><br>

    <label>Instagram:</label>
    <input type="text" name="instagram"><br>

    <label>Fecha de Cumpleaños:</label>
    <input type="date" name="fecha_cumpleanos"><br>

    <button type="submit">Guardar Cliente</button>
</form>