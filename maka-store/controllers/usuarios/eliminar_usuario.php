<?php
session_start();
if ($_SESSION['rol'] !== 'admin') {
    die("No tienes permiso para eliminar usuarios.");
}

require_once '../../../../includes/db.php';

$id = $_GET['id'] ?? null;

if (!$id) {
    die("ID no válido");
}

$stmt = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$id]);

header("Location: ../../lista_usuarios.php");
exit;