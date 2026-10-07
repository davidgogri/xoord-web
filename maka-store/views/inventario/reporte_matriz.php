<?php
// 1. Incluir header (inicia sesión, conecta a la base de datos, carga Bootstrap)
include __DIR__ . '/../../partials/header.php';

// 2. Obtener datos y preparar la matriz
$filter_producto = isset($_GET['filter_producto']) ? trim($_GET['filter_producto']) : '';
$filter_talla = isset($_GET['filter_talla']) ? trim($_GET['filter_talla']) : '';

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
?>

<!-- 3. Tu HTML va aquí -->

<div class="container mt-4">
    <h1 class="mb-4 text-primary text-center">
        <i class="fas fa-table me-2"></i> Reporte Matriz de Inventario
    </h1>
    <p class="text-muted text-center">Visualiza el stock de productos por color en formato de matriz.</p>

    <!-- Filtros -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" action="" class="row g-3 align-items-center">
                <div class="col-md-6">
                    <label for="filter_producto" class="form-label"><i class="fas fa-search me-2"></i>Filtrar por Producto:</label>
                    <input type="text" name="filter_producto" id="filter_producto" class="form-control"
                           placeholder="Buscar producto..." value="<?= htmlspecialchars($filter_producto) ?>">
                </div>
                <div class="col-md-4">
                    <label for="filter_talla" class="form-label"><i class="fas fa-tshirt me-2"></i>Filtrar por Talla:</label>
                    <select name="filter_talla" id="filter_talla" class="form-select">
                        <option value="">Todas las tallas</option>
                        <option value="Unica" <?= $filter_talla === 'Unica' ? 'selected' : '' ?>>Unica</option>
                        <option value="Plus" <?= $filter_talla === 'Plus' ? 'selected' : '' ?>>Plus</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-2"></i>Aplicar Filtros</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($productos) && empty($colores)): ?>
        <div class="alert alert-info text-center mt-5" role="alert">
            <i class="fas fa-box-open me-2"></i> No hay datos disponibles para mostrar.
        </div>
    <?php else: ?>
    <style>
    .bg-success.text-white.fw-bold td {
        border-top: 2px solid #fff !important;
    }
</style>
        <div class="table-responsive">
            <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                <thead class="bg-primary text-white">
                    <tr>
                        <th scope="col">Color / Producto</th>
                        <?php foreach ($productos as $producto): ?>
                            <th scope="col"><?= htmlspecialchars($producto) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <!-- Fila de totales por producto -->
                    <tr class="bg-success text-white fw-bold">
                        <td><strong>TOTAL</strong></td>
                        <?php 
                        // Calcular totales por producto
                        $totales_productos = [];
                        foreach ($productos as $producto) {
                            $total = 0;
                            foreach ($colores as $color) {
                                if (isset($matriz[$color][$producto])) {
                                    $total += $matriz[$color][$producto];
                                }
                            }
                            $totales_productos[$producto] = $total;
                        }
                        ?>
                        <?php foreach ($productos as $producto): ?>
                            <td><?= $totales_productos[$producto] ?></td>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($colores as $color): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($color) ?></strong></td>
                            <?php foreach ($productos as $producto): ?>
                                <td class="<?php echo isset($matriz[$color][$producto]) ? 'bg-success text-white' : 'bg-light'; ?>">
                                    <?php echo isset($matriz[$color][$producto]) ? $matriz[$color][$producto] : '-'; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <!-- Botón de regreso al dashboard -->
    <div class="d-flex justify-content-center mt-4 mb-5">
        <a href="../../dashboard.php" class="btn btn-secondary btn-lg shadow-sm">
            <i class="fas fa-arrow-alt-circle-left me-2"></i> Volver al Dashboard
        </a>
    </div>
</div>

<!-- 4. Incluir el footer AL FINAL -->
<?php
include __DIR__ . '/../../partials/footer.php';
?>