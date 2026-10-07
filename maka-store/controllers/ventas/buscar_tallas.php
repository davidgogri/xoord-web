<?php
require_once '../../../../includes/db.php';

$q = $_GET['q'] ?? '';

if ($q) {
    $stmt = $pdo->prepare("SELECT id_talla, nombre_talla AS nombre FROM tallas WHERE nombre_talla LIKE ?");
    $stmt->execute(["%$q%"]);
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($resultados);
} else {
    echo json_encode([]);
}
?>