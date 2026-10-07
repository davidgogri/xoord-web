<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Protección por sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../../../login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cliente = $_POST['id_cliente'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'] ?? null;
    $celular = $_POST['celular'] ?? null;
    $email = $_POST['email'] ?? null;
    $instagram = $_POST['instagram'] ?? null;
    $fecha_cumpleanos = $_POST['fecha_cumpleanos'] ?? null;

    try {
        $stmt = $pdo->prepare("
            UPDATE clientes 
            SET nombre = ?, apellido = ?, celular = ?, email = ?, instagram = ?, fecha_cumpleanos = ?
            WHERE id_cliente = ?
        ");
        $stmt->execute([$nombre, $apellido, $celular, $email, $instagram, $fecha_cumpleanos, $id_cliente]);

        // Redirigir al listado – Ruta absoluta corregida
        header("Location: /views/clientes/lista_clientes.php");
        exit;

    } catch (PDOException $e) {
        die("Error al actualizar cliente: " . $e->getMessage());
    }
}
?>