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

<h2>Registrar Nuevo Proveedor</h2>

<form action="/controllers/proveedores/registrar_proveedor.php" method="POST">
    <label>Nombre de la Empresa:</label>
    <input type="text" name="nombre_empresa" required><br>

    <label>Contacto:</label>
    <input type="text" name="contacto"><br>

    <label>Teléfono:</label>
    <input type="text" name="telefono"><br>

    <label>Email:</label>
    <input type="email" name="email"><br>

    <label>Dirección:</label>
    <textarea name="direccion"></textarea><br>

    <label>Productos que provee:</label>
    <textarea name="productos_que_proveen"></textarea><br>

    <button type="submit">Guardar Proveedor</button>
</form>