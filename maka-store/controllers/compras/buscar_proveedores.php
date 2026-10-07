<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

$q = trim($_GET['q'] ?? '');

if ($q) {
    // Mensaje de depuración
    error_log("Búsqueda iniciada con término: $q");

    // Consulta para buscar proveedores por nombre de empresa
    $stmt = $pdo->prepare("SELECT id_proveedor, nombre_empresa FROM proveedores WHERE nombre_empresa LIKE ? AND estado = 'activo'");
    $stmt->execute(["%$q%"]);
    
    // Verificar si la consulta fue exitosa
    if ($stmt->errorCode() !== '00000') {
        $errorInfo = $stmt->errorInfo();
        error_log("Error en la consulta: " . $errorInfo[2]);
        echo json_encode([]);
        exit;
    }

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mensaje de depuración
    error_log("Resultados encontrados: " . count($resultados));

    // Devolver los resultados como JSON
    echo json_encode($resultados);
} else {
    // Si no hay consulta, devolver un array vacío
    error_log("No se proporcionó un término de búsqueda.");
    echo json_encode([]);
}
?>