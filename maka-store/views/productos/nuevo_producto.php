<?php
// Iniciar sesión (protección)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Incluir menú y diseño general
include '../../partials/menu.php';
?>

<h2>Registrar Nuevo Producto</h2>

<form action="../../controllers/productos/registrar_producto.php" method="POST">
    <label>Nombre del producto:</label>
    <input type="text" name="nombre" placeholder="Ej. Vestido Rojo" required>

    <label>Descripción (opcional):</label>
    <textarea name="descripcion" placeholder="Material, estilo, detalles..."></textarea>

    <label>Precio de compra:</label>
    <input type="number" step="0.01" name="precio_compra" placeholder="Ej. 15.99" required>

    <label>Precio de venta:</label>
    <input type="number" step="0.01" name="precio_venta" placeholder="Ej. 39.99" required>

    <button type="submit">Guardar Producto</button>
</form>