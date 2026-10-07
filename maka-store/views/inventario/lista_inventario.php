<?php
// Habilitar la visualización de errores (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir header.php que ya maneja session_start() y la conexión a la base de datos ($pdo)
include __DIR__ . '/../../partials/header.php';

// Verificar la sesión del usuario
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Inicializar variable de búsqueda
$buscar_producto = $_GET['buscar_producto'] ?? '';
$where_clause = '';
$params = [];

// Si hay un término de búsqueda, construir la cláusula WHERE
if (!empty($buscar_producto)) {
    $where_clause = " WHERE p.nombre LIKE :buscar_producto";
    $params[':buscar_producto'] = '%' . $buscar_producto . '%';
}

// Consulta para obtener el inventario con detalles de productos, colores y tallas
// Se aplica el filtro si existe
$sql = "
    SELECT
        i.id_producto,
        p.nombre AS producto,
        t.nombre_talla AS talla,
        c.nombre_color AS color,
        i.cantidad_stock
    FROM
        inventario AS i
    LEFT JOIN
        productos AS p ON i.id_producto = p.id_producto
    LEFT JOIN
        colores AS c ON i.id_color = c.id_color
    LEFT JOIN
        tallas AS t ON i.id_talla = t.id_talla 
    " . $where_clause . "
    ORDER BY
        p.nombre ASC, c.nombre_color ASC, t.nombre_talla ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-warehouse me-2"></i> Inventario de Productos
        </h1>
        <p class="text-muted text-center">Consulta la cantidad disponible de cada producto en tu stock.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="lista_inventario.php" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <label for="buscar_producto" class="visually-hidden">Buscar Producto</label>
                        <input type="text" class="form-control" id="buscar_producto" name="buscar_producto"
                               placeholder="Buscar por nombre de producto..." value="<?= htmlspecialchars($buscar_producto) ?>">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (count($inventario) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">Producto</th>
                            <th scope="col">Talla</th>
                            <th scope="col">Color</th>
                            <th scope="col">Cantidad en Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventario as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['producto']) ?></td>
                                <td><?= htmlspecialchars($item['talla']) ?></td>
                                <td><?= htmlspecialchars($item['color']) ?></td>
                                <td><?= htmlspecialchars($item['cantidad_stock']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-box-open me-2"></i> No se encontraron productos que coincidan con la búsqueda o el inventario está vacío.
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center mt-4 mb-5">
            <a href="../../dashboard.php" class="btn btn-secondary btn-lg shadow-sm">
                <i class="fas fa-arrow-alt-circle-left me-2"></i> Volver al Dashboard
            </a>
        </div>
    </div>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap, etc.
include '../../partials/footer.php';
?>