<?php
session_start();

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Protección por sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login.php");
    exit;
}

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_empresa = $_POST['nombre_empresa'];
    $contacto = $_POST['contacto'] ?? null;
    $telefono = $_POST['telefono'] ?? null;
    $email = $_POST['email'] ?? null;
    $direccion = $_POST['direccion'] ?? null;
    $productos_que_proveen = $_POST['productos_que_proveen'] ?? null;

    try {
        $stmt = $pdo->prepare("
            INSERT INTO proveedores (
                nombre_empresa, contacto, telefono, email, direccion, productos_que_proveen
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $nombre_empresa, $contacto, $telefono, $email, $direccion, $productos_que_proveen
        ]);

        // Redirección absoluta corregida
        header("Location: /views/proveedores/lista_proveedores.php");
        exit;

    } catch (PDOException $e) {
        die("Error al registrar proveedor: " . $e->getMessage());
    }
}
?>