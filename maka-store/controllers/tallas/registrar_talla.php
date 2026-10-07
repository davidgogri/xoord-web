<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Protección por sesión
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../../login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_talla = $_POST['nombre_talla'];

    try {
        // Insertar nueva talla
        $stmt = $pdo->prepare("INSERT INTO tallas (nombre_talla) VALUES (?)");
        $stmt->execute([$nombre_talla]);

        // Redirigir al listado – Ruta absoluta corregida
        header("Location: /views/tallas/lista_tallas.php");
        exit;

    } catch (PDOException $e) {
        die("Error al registrar talla: " . $e->getMessage());
    }
}
?>