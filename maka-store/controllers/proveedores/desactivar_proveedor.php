<?php
session_start();
require_once '../../../../includes/auth.php';
checkAuth('admin'); // Solo admins pueden desactivar

require_once '../../../../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

try {
    $stmt = $pdo->prepare("UPDATE proveedores SET estado = 'inactivo' WHERE id_proveedor = ?");
    $stmt->execute([$id]);

    header("Location: ../../lista_proveedores.php");
    exit;
} catch (PDOException $e) {
    die("Error al desactivar proveedor: " . $e->getMessage());
}
?>