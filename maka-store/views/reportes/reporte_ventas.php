<?php
// Habilitar visualización de errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir rutas a los parciales
$path_to_partials = __DIR__ . '/../../partials/';
$header_path = $path_to_partials . 'header.php';
$footer_path = $path_to_partials . 'footer.php';

// Incluir el header.php. Esto iniciará la sesión, establecerá la conexión $pdo,
// y definirá la variable $rol.
if (!file_exists($header_path)) {
    die("Error crítico: No se encontró el archivo header.php en la ruta: " . $header_path . ". Verifique la ruta y los permisos.");
}
include $header_path;

// Redirigir al login si el usuario no está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login.php");
    exit;
}

// Verificar rol de administrador
if (!isset($rol) || $rol !== 'admin') {
    // Si no es admin, redirigir o mostrar mensaje de acceso denegado
    header("Location: /index.php"); // O a alguna página de acceso denegado
    exit;
}

// --- Lógica de filtrado y obtención de datos ---
$fecha_inicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Por defecto, primer día del mes actual
$fecha_fin = $_GET['fecha_fin'] ?? date('Y-m-d');      // Por defecto, día actual

$ventas = [];
$total_ventas_periodo = 0;
$top_productos = [];
$top_colores = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. Obtener todas las ventas detalladas en el rango de fechas
        $stmt_ventas = $pdo->prepare("SELECT * FROM v_ventas WHERE Fecha BETWEEN ? AND ? ORDER BY Fecha DESC");
        $stmt_ventas->execute([$fecha_inicio, $fecha_fin]);
        $ventas = $stmt_ventas->fetchAll(PDO::FETCH_ASSOC);

        // 2. Calcular el total de ventas del periodo
        $stmt_total = $pdo->prepare("SELECT SUM(total_venta) AS total FROM (SELECT DISTINCT id_venta, total_venta FROM v_ventas WHERE Fecha BETWEEN ? AND ?) AS ventas_unicas");
        $stmt_total->execute([$fecha_inicio, $fecha_fin]);
        $total_ventas_periodo_result = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_ventas_periodo = $total_ventas_periodo_result['total'] ?? 0;

        // 3. Top 5 Productos más Vendidos
        $stmt_productos = $pdo->prepare("SELECT Producto, SUM(Cantidad) AS total_cantidad FROM v_ventas WHERE Fecha BETWEEN ? AND ? GROUP BY Producto ORDER BY total_cantidad DESC LIMIT 5");
        $stmt_productos->execute([$fecha_inicio, $fecha_fin]);
        $top_productos = $stmt_productos->fetchAll(PDO::FETCH_ASSOC);

        // 4. Top 5 Colores más Vendidos
        $stmt_colores = $pdo->prepare("SELECT Color, SUM(Cantidad) AS total_cantidad FROM v_ventas WHERE Fecha BETWEEN ? AND ? GROUP BY Color ORDER BY total_cantidad DESC LIMIT 5");
        $stmt_colores->execute([$fecha_inicio, $fecha_fin]);
        $top_colores = $stmt_colores->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al cargar el reporte de ventas: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Error al cargar los datos del reporte: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Error: La conexión a la base de datos no está disponible.</div>";
}

// Preparar datos para Chart.js
$productos_labels = [];
$productos_data = [];
foreach ($top_productos as $item) {
    $productos_labels[] = $item['Producto'];
    $productos_data[] = $item['total_cantidad'];
}

$colores_labels = [];
$colores_data = [];
foreach ($top_colores as $item) {
    $colores_labels[] = $item['Color'];
    $colores_data[] = $item['total_cantidad'];
}
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4 text-center">Reporte de Ventas Detallado</h1>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Filtrar por Fechas</h5>
        </div>
        <div class="card-body">
            <form action="" method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="fecha_inicio" class="form-label">Fecha de Inicio:</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars($fecha_inicio); ?>">
                </div>
                <div class="col-md-4">
                    <label for="fecha_fin" class="form-label">Fecha de Fin:</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars($fecha_fin); ?>">
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary w-100">Aplicar Filtro</button>
                </div>
            </form>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card text-center h-100 shadow-sm border-success">
                <div class="card-body">
                    <h5 class="card-title text-success">Total de Ventas del Periodo</h5>
                    <p class="card-text fs-3 fw-bold">$<?php echo number_format($total_ventas_periodo, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Top 5 Productos más Vendidos</h5>
                </div>
                <div class="card-body">
                    <canvas id="productosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Top 5 Colores más Vendidos</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="coloresChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Detalle de Ventas (<?php echo htmlspecialchars($fecha_inicio); ?> al <?php echo htmlspecialchars($fecha_fin); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($ventas)): ?>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="sticky-top bg-white shadow-sm">
                            <tr>
                                <th>ID Venta</th>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Usuario</th>
                                <th>Producto</th>
                                <th>Color</th>
                                <th>Talla</th>
                                <th>Cantidad</th>
                                <th>Precio Venta</th>
                                <th>Subtotal Item</th>
                                <th>Total Venta</th>
                                <th>Método Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ventas as $venta): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($venta['id_venta']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Cliente']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Producto']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Color']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Talla']); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Cantidad']); ?></td>
                                    <td>$<?php echo number_format($venta['precio_venta'], 2); ?></td>
                                    <td>$<?php echo number_format($venta['Subtotal'], 2); ?></td>
                                    <td>$<?php echo number_format($venta['total_venta'], 2); ?></td>
                                    <td><?php echo htmlspecialchars($venta['Metodo_pago']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No se encontraron ventas para el periodo seleccionado.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    // Datos para los gráficos
    const productosLabels = <?php echo json_encode($productos_labels); ?>;
    const productosData = <?php echo json_encode($productos_data); ?>;
    const coloresLabels = <?php echo json_encode($colores_labels); ?>;
    const coloresData = <?php echo json_encode($colores_data); ?>;

    // Gráfico de Productos
    if (productosLabels.length > 0) {
        const productosCtx = document.getElementById('productosChart').getContext('2d');
        new Chart(productosCtx, {
            type: 'bar',
            data: {
                labels: productosLabels,
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: productosData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)',
                        'rgba(54, 162, 235, 0.6)',
                        'rgba(255, 206, 86, 0.6)',
                        'rgba(75, 192, 192, 0.6)',
                        'rgba(153, 102, 255, 0.6)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Producto'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    } else {
        document.getElementById('productosChart').parentElement.innerHTML = '<p class="text-center text-muted mt-3">No hay datos de productos para mostrar.</p>';
    }

    // Gráfico de Colores
    if (coloresLabels.length > 0) {
        const coloresCtx = document.getElementById('coloresChart').getContext('2d');
        new Chart(coloresCtx, {
            type: 'doughnut',
            data: {
                labels: coloresLabels,
                datasets: [{
                    label: 'Cantidad Vendida',
                    data: coloresData,
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.6)', // Rojo
                        'rgba(54, 162, 235, 0.6)', // Azul
                        'rgba(255, 206, 86, 0.6)', // Amarillo
                        'rgba(75, 192, 192, 0.6)', // Verde azulado
                        'rgba(153, 102, 255, 0.6)', // Morado
                        'rgba(255, 159, 64, 0.6)', // Naranja
                        'rgba(199, 199, 199, 0.6)' // Gris
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)',
                        'rgba(75, 192, 192, 1)',
                        'rgba(153, 102, 255, 1)',
                        'rgba(255, 159, 64, 1)',
                        'rgba(199, 199, 199, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                let label = context.label || '';
                                if (label) {
                                    label += ': ';
                                }
                                if (context.parsed !== null) {
                                    label += context.parsed + ' unidades';
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('coloresChart').parentElement.innerHTML = '<p class="text-center text-muted mt-3">No hay datos de colores para mostrar.</p>';
    }
</script>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts (Bootstrap JS)
if (!file_exists($footer_path)) {
    die("Error crítico: No se encontró el archivo footer.php en la ruta: " . $footer_path . ". Verifique la ruta y los permisos.");
}
include $footer_path;
?>