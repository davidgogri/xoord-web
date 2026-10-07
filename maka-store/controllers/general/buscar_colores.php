<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$q = trim($_GET['q'] ?? '');

if ($q) {
    $stmt = $pdo->prepare("SELECT id_color, nombre_color FROM colores WHERE nombre_color LIKE ?");
    $stmt->execute(["%$q%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados);
} else {
    echo json_encode([]);
}
?>