<?php
session_start();
if ($_SESSION['rol'] !== 'admin') {
    die("Acceso denegado");
}

require_once '../../../../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

$stmt = $pdo->prepare("UPDATE usuarios SET estado = 'inactivo' WHERE id_usuario = ?");
$stmt->execute([$id]);

header("Location: ../../usuarios/lista_usuarios.php");
exit;