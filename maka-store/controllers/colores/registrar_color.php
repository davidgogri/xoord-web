<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Protección por sesión y rol admin
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    header("Location: ../../../../login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_color = $_POST['nombre_color'];

    try {
        // Registrar nuevo color
        $stmt = $pdo->prepare("INSERT INTO colores (nombre_color) VALUES (?)");
        $stmt->execute([$nombre_color]);

        // Redirigir al listado – Ruta absoluta corregida
        header("Location: /views/colores/lista_colores.php");
        exit;

    } catch (PDOException $e) {
        die("Error al registrar color: " . $e->getMessage());
    }
}
?>