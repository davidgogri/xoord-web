<?php
// Habilitar visualización de errores (solo para desarrollo)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir header.php (maneja session_start() y conexión a la base de datos)
include __DIR__ . '/../../partials/header.php';

// Verificar sesión del usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Obtener rol del usuario
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$rol = $usuario['rol'] ?? 'usuario';

if ($rol !== 'admin') {
    die("Acceso denegado. Solo administradores pueden ver este reporte.");
}

// Obtener parámetros de filtro
$filter_producto = isset($_GET['filter_producto']) ? trim($_GET['filter_producto']) : '';
$filter_talla = isset($_GET['filter_talla']) ? trim($_GET['filter_talla']) : '';

// Consulta SQL para obtener datos de la vista v_Inventario
$query = "
    SELECT Producto, Color, Talla, Stock
    FROM v_Inventario
    WHERE Stock > 0
";

$conditions = [];
$params = [];

if (!empty($filter_producto)) {
    $conditions[] = "Producto LIKE ?";
    $params[] = "%$filter_producto%";
}

if (!empty($filter_talla)) {
    $conditions[] = "Talla = ?";
    $params[] = $filter_talla;
}

if (!empty($conditions)) {
    $query .= " AND " . implode(' AND ', $conditions);
}

$query .= " ORDER BY Producto, Color";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$datos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Preparar datos para la matriz
$productos = [];
$colores = [];
$matriz = [];

foreach ($datos as $fila) {
    if (!in_array($fila['Producto'], $productos)) {
        $productos[] = $fila['Producto'];
    }
    if (!in_array($fila['Color'], $colores)) {
        $colores[] = $fila['Color'];
    }
    $matriz[$fila['Color']][$fila['Producto']] = $fila['Stock'];
}

// Pasar los datos a la vista
include '../../views/inventario/reporte_matriz.php';
?>