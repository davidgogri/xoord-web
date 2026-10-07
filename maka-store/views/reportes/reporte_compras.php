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

$compras = [];
$total_compras_periodo = 0;
$top_productos_comprados = [];
$top_proveedores = [];

if (isset($pdo) && $pdo instanceof PDO) {
    try {
        // 1. Obtener todas las compras detalladas en el rango de fechas
        $stmt_compras = $pdo->prepare("SELECT * FROM v_compras WHERE Fecha BETWEEN ? AND ? ORDER BY Fecha DESC");
        $stmt_compras->execute([$fecha_inicio, $fecha_fin]);
        $compras = $stmt_compras->fetchAll(PDO::FETCH_ASSOC);

        // 2. Calcular el total de compras del periodo
        // MODIFICADO: Cambiado 'total_compra' a 'Total_Compra'
        $stmt_total = $pdo->prepare("SELECT SUM(Total_Compra) AS total FROM (SELECT DISTINCT id_compra, Total_Compra FROM v_compras WHERE Fecha BETWEEN ? AND ?) AS compras_unicas");
        $stmt_total->execute([$fecha_inicio, $fecha_fin]);
        $total_compras_periodo_result = $stmt_total->fetch(PDO::FETCH_ASSOC);
        $total_compras_periodo = $total_compras_periodo_result['total'] ?? 0;

        // 3. Top 5 Productos más Comprados (por cantidad)
        $stmt_productos_comprados = $pdo->prepare("SELECT Producto, SUM(Cantidad) AS total_cantidad FROM v_compras WHERE Fecha BETWEEN ? AND ? GROUP BY Producto ORDER BY total_cantidad DESC LIMIT 5");
        $stmt_productos_comprados->execute([$fecha_inicio, $fecha_fin]);
        $top_productos_comprados = $stmt_productos_comprados->fetchAll(PDO::FETCH_ASSOC);

        // 4. Top 5 Proveedores (por monto total de compras a ellos)
        // MODIFICADO: Cambiado 'total_compra' a 'Total_Compra'
        $stmt_proveedores = $pdo->prepare("SELECT Proveedor, SUM(Total_Compra) AS total_monto FROM (SELECT DISTINCT id_compra, Proveedor, Total_Compra FROM v_compras WHERE Fecha BETWEEN ? AND ?) AS compras_proveedor GROUP BY Proveedor ORDER BY total_monto DESC LIMIT 5");
        $stmt_proveedores->execute([$fecha_inicio, $fecha_fin]);
        $top_proveedores = $stmt_proveedores->fetchAll(PDO::FETCH_ASSOC);

    } catch (PDOException $e) {
        error_log("Error al cargar el reporte de compras: " . $e->getMessage());
        echo "<div class='alert alert-danger'>Error al cargar los datos del reporte: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div class='alert alert-danger'>Error: La conexión a la base de datos no está disponible.</div>";
}

// Preparar datos para Chart.js
$productos_comprados_labels = [];
$productos_comprados_data = [];
foreach ($top_productos_comprados as $item) {
    $productos_comprados_labels[] = $item['Producto'];
    $productos_comprados_data[] = $item['total_cantidad'];
}

$proveedores_labels = [];
$proveedores_data = [];
foreach ($top_proveedores as $item) {
    $proveedores_labels[] = $item['Proveedor'];
    $proveedores_data[] = $item['total_monto'];
}
?>

<div class="container-fluid mt-4">
    <h1 class="mb-4 text-center">Reporte de Compras Detallado</h1>

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
            <div class="card text-center h-100 shadow-sm border-danger">
                <div class="card-body">
                    <h5 class="card-title text-danger">Total de Compras del Periodo</h5>
                    <p class="card-text fs-3 fw-bold">$<?php echo number_format($total_compras_periodo, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Top 5 Productos más Comprados</h5>
                </div>
                <div class="card-body">
                    <canvas id="productosCompradosChart"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-4 col-md-12 mb-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Top 5 Proveedores (por monto)</h5>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center">
                    <canvas id="proveedoresChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Detalle de Compras (<?php echo htmlspecialchars($fecha_inicio); ?> al <?php echo htmlspecialchars($fecha_fin); ?>)</h5>
        </div>
        <div class="card-body">
            <?php if (!empty($compras)): ?>
                <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                    <table class="table table-striped table-hover table-sm">
                        <thead class="sticky-top bg-white shadow-sm">
                            <tr>
                                <th>ID Compra</th>
                                <th>Fecha</th>
                                <th>Proveedor</th>
                                <th>Usuario</th>
                                <th>Producto</th>
                                <th>Color</th>
                                <th>Talla</th>
                                <th>Cantidad</th>
                                <th>Precio Compra</th>
                                <th>Subtotal Item</th>
                                <th>Total Compra</th>
                                <th>Método Pago</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($compras as $compra): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($compra['id_compra']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Proveedor']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Usuario']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Producto']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Color']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Talla']); ?></td>
                                    <td><?php echo htmlspecialchars($compra['Cantidad']); ?></td>
                                    <td>$<?php echo number_format($compra['Precio_Compra'], 2); ?></td> <td>$<?php echo number_format($compra['Subtotal'], 2); ?></td>
                                    <td>$<?php echo number_format($compra['Total_Compra'], 2); ?></td> <td><?php echo htmlspecialchars($compra['Metodo_Pago']); ?></td> </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center" role="alert">
                    No se encontraron compras para el periodo seleccionado.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
    // Datos para los gráficos
    const productosCompradosLabels = <?php echo json_encode($productos_comprados_labels); ?>;
    const productosCompradosData = <?php echo json_encode($productos_comprados_data); ?>;
    const proveedoresLabels = <?php echo json_encode($proveedores_labels); ?>;
    const proveedoresData = <?php echo json_encode($proveedores_data); ?>;

    // Gráfico de Productos Comprados
    if (productosCompradosLabels.length > 0) {
        const productosCompradosCtx = document.getElementById('productosCompradosChart').getContext('2d');
        new Chart(productosCompradosCtx, {
            type: 'bar',
            data: {
                labels: productosCompradosLabels,
                datasets: [{
                    label: 'Cantidad Comprada',
                    data: productosCompradosData,
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
        document.getElementById('productosCompradosChart').parentElement.innerHTML = '<p class="text-center text-muted mt-3">No hay datos de productos comprados para mostrar.</p>';
    }

    // Gráfico de Proveedores
    if (proveedoresLabels.length > 0) {
        const proveedoresCtx = document.getElementById('proveedoresChart').getContext('2d');
        new Chart(proveedoresCtx, {
            type: 'doughnut',
            data: {
                labels: proveedoresLabels,
                datasets: [{
                    label: 'Monto de Compras',
                    data: proveedoresData,
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
                                    label += '$' + context.parsed.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                                }
                                return label;
                            }
                        }
                    }
                }
            }
        });
    } else {
        document.getElementById('proveedoresChart').parentElement.innerHTML = '<p class="text-center text-muted mt-3">No hay datos de proveedores para mostrar.</p>';
    }
</script>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts (Bootstrap JS)
if (!file_exists($footer_path)) {
    die("Error crítico: No se encontró el archivo footer.php en la ruta: " . $footer_path . ". Verifique la ruta y los permisos.");
}
include $footer_path;
?>