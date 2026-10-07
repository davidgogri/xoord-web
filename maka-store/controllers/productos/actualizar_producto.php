<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Protección por sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login.php");
    exit;
}

// Ruta absoluta corregida
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_producto'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'] ?? null;
    $precio_compra = $_POST['precio_compra'];
    $precio_venta = $_POST['precio_venta'];

    try {
        // Actualizar producto
        $stmt = $pdo->prepare("
            UPDATE productos 
            SET nombre = ?, descripcion = ?, precio_compra = ?, precio_venta = ?
            WHERE id_producto = ?
        ");
        $stmt->execute([$nombre, $descripcion, $precio_compra, $precio_venta, $id]);

        // Redirigir al listado con ruta absoluta
        header("Location: /views/productos/lista_productos.php");
        exit;

    } catch (PDOException $e) {
        die("Error al actualizar producto: " . $e->getMessage());
    }
} else {
    die("Método no permitido.");
}
?>