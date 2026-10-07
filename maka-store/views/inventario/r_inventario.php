<?php
// Incluir el header, que maneja la sesión, conexión DB y el HTML inicial
include __DIR__ . '/../../partials/header.php'; 

// Las variables $pdo y $rol ya están disponibles desde partials/header.php

// Si el usuario no está logueado, redirigir al login (redundante si header.php ya lo hace, pero seguro)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Opcional: Si esta página también requiere permisos de 'admin'
// if ($rol !== 'admin') {
//     ?>
<?php
//     include __DIR__ . '/../../partials/footer.php';
//     exit; 
// }

// Consulta SQL para obtener el inventario detallado
try {
    $stmt = $pdo->query("SELECT Producto, Color, Talla, Stock, Costo FROM v_Inventario");
    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Mostrar un error más amigable en caso de problema de BD
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger" role="alert">
            Error al consultar el inventario: <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../partials/footer.php';
    exit;
}

// **Nuevas consultas para obtener los totales de Stock y Costo**
$totalStock = 0;
$totalCosto = 0;

try {
    // Consulta para el total de Stock
    $stmtStock = $pdo->query("SELECT SUM(Stock) AS total_stock FROM v_Inventario");
    $resultadoStock = $stmtStock->fetch(PDO::FETCH_ASSOC);
    $totalStock = $resultadoStock['total_stock'] ?? 0;

    // Consulta para el total de Costo
    $stmtCosto = $pdo->query("SELECT SUM(Costo) AS total_costo FROM v_Inventario");
    $resultadoCosto = $stmtCosto->fetch(PDO::FETCH_ASSOC);
    $totalCosto = $resultadoCosto['total_costo'] ?? 0;

} catch (PDOException $e) {
    // Si hay un error en estas consultas, los totales serán 0
    // Puedes loguear el error si es necesario
    error_log("Error al calcular totales de inventario: " . $e->getMessage());
}
?>

<h1 class="mb-4">Consulta de Inventario</h1>

<?php if (!empty($inventario)): ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-info text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Total de Unidades en Stock</h5>
                    <p class="card-text fs-3 fw-bold"><?php echo number_format($totalStock, 0); ?> Unidades</p>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-success text-white shadow-sm h-100">
                <div class="card-body">
                    <h5 class="card-title">Costo Total del Inventario</h5>
                    <p class="card-text fs-3 fw-bold">$<?php echo number_format($totalCosto, 2); ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Detalle de Inventario por Producto y Color</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Color</th>
                            <th>Talla</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventario as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['Producto']); ?></td>
                                <td><?php echo htmlspecialchars($item['Color']); ?></td>
                                <td><?php echo htmlspecialchars($item['Talla']); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars($item['Stock']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['Costo'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info" role="alert">
        No hay datos disponibles en el inventario.
    </div>
<?php endif; ?>

<?php 
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap
include __DIR__ . '/../../partials/footer.php'; 
?>