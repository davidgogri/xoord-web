<?php
// Activar visualización de errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Definir rutas a los parciales
$path_to_partials = __DIR__ . '/partials/';
$header_path = $path_to_partials . 'header.php';
$footer_path = $path_to_partials . 'footer.php';

// Incluir el header.php. Esto iniciará la sesión, establecerá la conexión $pdo,
// y definirá la variable $rol.
if (!file_exists($header_path)) {
    die("Error crítico: No se encontró el archivo header.php en la ruta: " . $header_path . ". Verifique la ruta y los permisos.");
}
include $header_path;

// Redirigir al login si el usuario no está logueado (aunque header.php ya lo hace)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login.php");
    exit;
}

// --- Funciones para calcular estadísticas ---

/**
 * Calcula el total de ventas del día actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float El total de ventas del día o 0 si hay un error/no hay ventas.
 */
function calcularVentasDelDia($pdo) {
    try {
        $fecha_hoy = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT SUM(total_venta) AS total_ventas_dia FROM ventas WHERE tipo_venta = 'Venta' AND DATE(fecha_venta) = ?");
        $stmt->execute([$fecha_hoy]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total_ventas_dia'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en calcularVentasDelDia: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcula el total de ventas del mes actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float El total de ventas del mes o 0 si hay un error/no hay ventas.
 */
function calcularVentasDelMes($pdo) {
    try {
        $anio_actual = date('Y');
        $mes_actual = date('m');
        
        $stmt = $pdo->prepare("SELECT SUM(total_venta) AS total_ventas_mes FROM ventas WHERE tipo_venta = 'Venta' AND  YEAR(fecha_venta) = ? AND MONTH(fecha_venta) = ?");
        $stmt->execute([$anio_actual, $mes_actual]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total_ventas_mes'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en calcularVentasDelMes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcula el total de compras de productos del mes actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float El total de compras del mes o 0 si hay un error/no hay compras.
 */
function calcularComprasDelMes($pdo) {
    try {
        $anio_actual = date('Y');
        $mes_actual = date('m');

        $stmt = $pdo->prepare("SELECT SUM(total_compra) AS total_compras_mes FROM compras WHERE YEAR(fecha_compra) = ? AND MONTH(fecha_compra) = ?");
        $stmt->execute([$anio_actual, $mes_actual]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total_compras_mes'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en calcularComprasDelMes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcula el total de compras de servicios del mes actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float El total de compras de servicios del mes o 0 si hay un error/no hay compras.
 */
function calcularComprasServiciosDelMes($pdo) {
    try {
        $anio_actual = date('Y');
        $mes_actual = date('m');

        $stmt = $pdo->prepare("SELECT SUM(monto) AS total_compras_servicios_mes FROM compras_servicios WHERE YEAR(fecha_compra) = ? AND MONTH(fecha_compra) = ?");
        $stmt->execute([$anio_actual, $mes_actual]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $resultado['total_compras_servicios_mes'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en calcularComprasServiciosDelMes: " . $e->getMessage());
        return 0;
    }
}

/**
 * Cuenta el número de ventas del día actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return int El número de ventas del día o 0.
 */
function contarVentasDelDia($pdo) {
    try {
        $fecha_hoy = date('Y-m-d');
        $stmt = $pdo->prepare("SELECT COUNT(*) AS total_ventas_dia FROM ventas WHERE tipo_venta = 'Venta' AND DATE(fecha_venta) = ?");
        $stmt->execute([$fecha_hoy]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total_ventas_dia'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en contarVentasDelDia: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcula la cantidad total de productos vendidos del día actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float La cantidad de productos vendidos del día o 0.
 */
function calcularProductosVendidosDelDia($pdo) {
    try {
        $fecha_hoy = date('Y-m-d');
        $stmt = $pdo->prepare("
            SELECT SUM(dv.cantidad) AS total_productos_vendidos
            FROM detalles_venta dv
            JOIN ventas v ON dv.id_venta = v.id_venta
            WHERE  v.tipo_venta = 'Venta' AND DATE(v.fecha_venta) = ?
        ");
        $stmt->execute([$fecha_hoy]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado['total_productos_vendidos'] ?? 0;
    } catch (PDOException $e) {
        error_log("Error en calcularProductosVendidosDelDia: " . $e->getMessage());
        return 0;
    }
}

/**
 * Calcula el neto (ventas - compras) del mes actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @return float El valor neto del mes o 0.
 */
function calcularNetoDelMes($pdo) {
    $ventas_mes = calcularVentasDelMes($pdo);
    $compras_mes = calcularComprasDelMes($pdo);
    $compras_servicios_mes = calcularComprasServiciosDelMes($pdo);
    return $ventas_mes - ($compras_mes + $compras_servicios_mes);
}

// --- Funciones para obtener datos para las gráficas ---

/**
 * Obtiene las ventas mensuales para los últimos N meses.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @param int $months Número de meses a recuperar.
 * @return array Array de datos con 'mes', 'anio', 'total'.
 */
function getMonthlySalesData($pdo, $months = 12) {
    $data = [];
    try {
        // Usamos v_ventas para obtener los datos agregados
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(fecha_venta, '%Y-%m') AS mes_anio,
                SUM(total_venta) AS total
            FROM v_ventas
            WHERE fecha_venta >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY mes_anio
            ORDER BY mes_anio ASC
        ");
        $stmt->execute([$months]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rellenar con meses faltantes para asegurar 12 meses
        $currentMonth = new DateTime();
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = $currentMonth->format('Y-m');
            $found = false;
            foreach ($results as $row) {
                if ($row['mes_anio'] === $monthKey) {
                    $data[] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = ['mes_anio' => $monthKey, 'total' => 0];
            }
            $currentMonth->modify('-1 month');
        }
        // Ordenar nuevamente por si acaso el relleno desordenó
        usort($data, function($a, $b) {
            return strtotime($a['mes_anio']) - strtotime($b['mes_anio']);
        });

    } catch (PDOException $e) {
        error_log("Error en getMonthlySalesData: " . $e->getMessage());
    }
    return $data;
}

/**
 * Obtiene las compras mensuales para los últimos N meses.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @param int $months Número de meses a recuperar.
 * @return array Array de datos con 'mes', 'anio', 'total'.
 */
function getMonthlyPurchasesData($pdo, $months = 12) {
    $data = [];
    try {
        // Usamos v_compras para obtener los datos agregados
        $stmt = $pdo->prepare("
            SELECT
                DATE_FORMAT(fecha_compra, '%Y-%m') AS mes_anio,
                SUM(total_compra) AS total
            FROM v_compras
            WHERE fecha_compra >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
            GROUP BY mes_anio
            ORDER BY mes_anio ASC
        ");
        $stmt->execute([$months]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Rellenar con meses faltantes para asegurar 12 meses
        $currentMonth = new DateTime();
        for ($i = $months - 1; $i >= 0; $i--) {
            $monthKey = $currentMonth->format('Y-m');
            $found = false;
            foreach ($results as $row) {
                if ($row['mes_anio'] === $monthKey) {
                    $data[] = $row;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data[] = ['mes_anio' => $monthKey, 'total' => 0];
            }
            $currentMonth->modify('-1 month');
        }
        // Ordenar nuevamente por si acaso el relleno desordenó
        usort($data, function($a, $b) {
            return strtotime($a['mes_anio']) - strtotime($b['mes_anio']);
        });

    } catch (PDOException $e) {
        error_log("Error en getMonthlyPurchasesData: " . $e->getMessage());
    }
    return $data;
}


/**
 * Obtiene los productos más vendidos (top N) del mes actual.
 * @param PDO $pdo Objeto PDO de la base de datos.
 * @param int $limit Número de productos a recuperar.
 * @return array Array de datos con 'nombre_producto', 'cantidad_vendida'.
 */
function getTopSellingProductsMonthly($pdo, $limit = 5) {
    $data = [];
    try {
        $anio_actual = date('Y');
        $mes_actual = date('m');
        $stmt = $pdo->prepare("
            SELECT
                p.nombre AS nombre_producto,
                SUM(dv.cantidad) AS cantidad_vendida
            FROM detalles_venta dv
            JOIN ventas v ON dv.id_venta = v.id_venta
            JOIN productos p ON dv.id_producto = p.id_producto
            WHERE  v.tipo_venta = 'Venta' AND YEAR(v.fecha_venta) = ? AND MONTH(v.fecha_venta) = ?
            GROUP BY p.nombre
            ORDER BY cantidad_vendida DESC
            LIMIT ?
        ");
        $stmt->bindValue(1, $anio_actual, PDO::PARAM_INT);
        $stmt->bindValue(2, $mes_actual, PDO::PARAM_INT);
        $stmt->bindValue(3, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error en getTopSellingProductsMonthly: " . $e->getMessage());
    }
    return $data;
}


// --- Inicialización de variables para las estadísticas y gráficas ---
$ventas_dia = 0;
$ventas_mes = 0;
$numero_ventas_dia = 0;
$productos_vendidos_dia = 0;
$compras_mes = 0;
$compras_servicios_mes = 0;
$neto_mes = 0;

$monthly_sales_data = [];
$monthly_purchases_data = [];
$top_selling_products = [];


// --- Carga de estadísticas y datos de gráficas solo si el usuario es admin ---
if (isset($rol) && $rol === 'admin') {
    if (isset($pdo) && $pdo instanceof PDO) {
        $ventas_dia = calcularVentasDelDia($pdo);
        $ventas_mes = calcularVentasDelMes($pdo);
        $numero_ventas_dia = contarVentasDelDia($pdo);
        $productos_vendidos_dia = calcularProductosVendidosDelDia($pdo);
        $compras_mes = calcularComprasDelMes($pdo);
        $compras_servicios_mes = calcularComprasServiciosDelMes($pdo);
        $neto_mes = calcularNetoDelMes($pdo);

        $monthly_sales_data = getMonthlySalesData($pdo);
        $monthly_purchases_data = getMonthlyPurchasesData($pdo);
        $top_selling_products = getTopSellingProductsMonthly($pdo);

    } else {
        error_log("Error: La conexión PDO no está disponible en dashboard.php para calcular estadísticas. Verifica partials/header.php.");
    }
} else {
    error_log("DEBUG: Usuario con ID " . ($_SESSION['usuario_id'] ?? 'N/A') . " y rol '" . ($rol ?? 'N/A') . "' NO tiene acceso a estadísticas admin.");
}
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid mt-4">
    <h1 class="mb-4 text-center text-primary fw-bold">🚀 Dashboard del Sistema</h1>
    <p class="text-center text-muted">¡Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre_usuario'] ?? 'Usuario'); ?>! Aquí tienes un resumen de la actividad del negocio.</p>

    <?php if (isset($rol) && $rol === 'admin'): ?>
        <hr class="my-5">
        <h2 class="mb-4 text-center text-secondary">📊 Estadísticas Rápidas</h2>
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4 mb-5 justify-content-center">
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-primary">
                    <div class="card-body">
                        <i class="fas fa-sack-dollar fa-3x text-primary mb-3"></i>
                        <h5 class="card-title text-primary">Ventas del Día</h5>
                        <p class="card-text fs-3 fw-bolder text-primary">$<?php echo number_format($ventas_dia, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-success">
                    <div class="card-body">
                        <i class="fas fa-chart-line fa-3x text-success mb-3"></i>
                        <h5 class="card-title text-success">Ventas del Mes</h5>
                        <p class="card-text fs-3 fw-bolder text-success">$<?php echo number_format($ventas_mes, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-info">
                    <div class="card-body">
                        <i class="fas fa-receipt fa-3x text-info mb-3"></i>
                        <h5 class="card-title text-info">Número de Ventas Hoy</h5>
                        <p class="card-text fs-3 fw-bolder text-info"><?php echo $numero_ventas_dia; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-warning">
                    <div class="card-body">
                        <i class="fas fa-boxes fa-3x text-warning mb-3"></i>
                        <h5 class="card-title text-warning">Productos Vendidos Hoy</h5>
                        <p class="card-text fs-3 fw-bolder text-warning"><?php echo $productos_vendidos_dia; ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-danger">
                    <div class="card-body">
                        <i class="fas fa-shopping-basket fa-3x text-danger mb-3"></i>
                        <h5 class="card-title text-danger">Compras del Mes (Productos)</h5>
                        <p class="card-text fs-3 fw-bolder text-danger">$<?php echo number_format($compras_mes, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-secondary">
                    <div class="card-body">
                        <i class="fas fa-tools fa-3x text-secondary mb-3"></i>
                        <h5 class="card-title text-secondary">Compras de Servicios del Mes</h5>
                        <p class="card-text fs-3 fw-bolder text-secondary">$<?php echo number_format($compras_servicios_mes, 2); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="col">
                <div class="card text-center h-100 shadow-lg border-dark">
                    <div class="card-body">
                        <i class="fas fa-balance-scale fa-3x text-dark mb-3"></i>
                        <h5 class="card-title text-dark">Neto del Mes</h5>
                        <p class="card-text fs-3 fw-bolder text-dark">$<?php echo number_format($neto_mes, 2); ?></p>
                    </div>
                </div>
            </div>
        </div>
        


        <hr class="my-5">
        <h2 class="mb-4 text-center text-primary">📈 Gráficas de Rendimiento</h2>
        <div class="row g-4 mb-5">
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header bg-gradient-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-area"></i> Ventas Mensuales (Últimos 12 Meses)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header bg-gradient-success text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart"></i> Compras Mensuales (Últimos 12 Meses)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="monthlyPurchasesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header bg-gradient-info text-white">
                        <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Ventas vs. Compras (Tendencia Mensual)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="salesVsPurchasesChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card shadow-lg h-100">
                    <div class="card-header bg-gradient-warning text-white">
                        <h5 class="mb-0"><i class="fas fa-award"></i> Top 5 Productos Más Vendidos (Este Mes)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="topSellingProductsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Datos para las gráficas
            const monthlySalesData = <?php echo json_encode($monthly_sales_data); ?>;
            const monthlyPurchasesData = <?php echo json_encode($monthly_purchases_data); ?>;
            const topSellingProducts = <?php echo json_encode($top_selling_products); ?>;

            // Preparar etiquetas y datos para Chart.js
            const salesLabels = monthlySalesData.map(item => item.mes_anio);
            const salesTotals = monthlySalesData.map(item => item.total);

            const purchasesLabels = monthlyPurchasesData.map(item => item.mes_anio);
            const purchasesTotals = monthlyPurchasesData.map(item => item.total);

            const productNames = topSellingProducts.map(item => item.nombre_producto);
            const productQuantities = topSellingProducts.map(item => item.cantidad_vendida);

            // Gráfica de Ventas Mensuales
            const ctxSales = document.getElementById('monthlySalesChart').getContext('2d');
            new Chart(ctxSales, {
                type: 'bar',
                data: {
                    labels: salesLabels,
                    datasets: [{
                        label: 'Ventas ($)',
                        data: salesTotals,
                        backgroundColor: 'rgba(0, 123, 255, 0.7)',
                        borderColor: 'rgba(0, 123, 255, 1)',
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
                                text: 'Total de Ventas ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO');
                                }
                            }
                        }
                    }
                }
            });

            // Gráfica de Compras Mensuales
            const ctxPurchases = document.getElementById('monthlyPurchasesChart').getContext('2d');
            new Chart(ctxPurchases, {
                type: 'bar',
                data: {
                    labels: purchasesLabels,
                    datasets: [{
                        label: 'Compras ($)',
                        data: purchasesTotals,
                        backgroundColor: 'rgba(40, 167, 69, 0.7)',
                        borderColor: 'rgba(40, 167, 69, 1)',
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
                                text: 'Total de Compras ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO');
                                }
                            }
                        }
                    }
                }
            });

            // Gráfica de Ventas vs. Compras (Tendencia Mensual)
            const ctxSalesVsPurchases = document.getElementById('salesVsPurchasesChart').getContext('2d');
            new Chart(ctxSalesVsPurchases, {
                type: 'line',
                data: {
                    labels: salesLabels, // Asumiendo que ambos tienen las mismas etiquetas de mes
                    datasets: [
                        {
                            label: 'Ventas ($)',
                            data: salesTotals,
                            borderColor: 'rgb(0, 123, 255)',
                            backgroundColor: 'rgba(0, 123, 255, 0.2)',
                            tension: 0.1,
                            fill: true
                        },
                        {
                            label: 'Compras ($)',
                            data: purchasesTotals,
                            borderColor: 'rgb(220, 53, 69)',
                            backgroundColor: 'rgba(220, 53, 69, 0.2)',
                            tension: 0.1,
                            fill: true
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Monto ($)'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Mes'
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO');
                                }
                            }
                        }
                    }
                }
            });

            // Gráfica de Top 5 Productos Más Vendidos
            const ctxTopProducts = document.getElementById('topSellingProductsChart').getContext('2d');
            new Chart(ctxTopProducts, {
                type: 'doughnut', // or 'bar'
                data: {
                    labels: productNames,
                    datasets: [{
                        label: 'Cantidad Vendida',
                        data: productQuantities,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.7)',
                            'rgba(54, 162, 235, 0.7)',
                            'rgba(255, 206, 86, 0.7)',
                            'rgba(75, 192, 192, 0.7)',
                            'rgba(153, 102, 255, 0.7)'
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
                    plugins: {
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
        </script>

    <?php endif; // Fin del if para rol admin ?>

    <hr class="my-5">
    <h2 class="mb-4 text-center text-dark">🔗 Accesos Rápidos a Módulos</h2>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4 mb-5">
        
        <div class="col">
            <div class="card h-100 shadow-lg border-primary">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="fas fa-cash-register fa-2x me-3"></i>
                    <h3 class="mb-0">Ventas</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="/views/ventas/lista_ventas.php" class="d-flex align-items-center text-decoration-none text-dark">
                                <i class="fas fa-list-alt me-2 text-primary"></i> Lista de Ventas
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="/views/ventas/nueva_venta.php" class="d-flex align-items-center text-decoration-none text-dark">
                                <i class="fas fa-plus-circle me-2 text-success"></i> Registrar Nueva Venta
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="/views/ventas/venta_chat.php" class="d-flex align-items-center text-decoration-none text-dark">
                                <i class="fas fa-plus-circle me-2 text-success"></i> Registrar Nueva Venta Chat
                            </a>
                        </li>                        
                        <?php if (isset($rol) && $rol === 'admin'): ?>
                            <li class="list-group-item">
                                <a href="/views/ventas/anular_venta.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-times-circle me-2 text-danger"></i> Anular Venta
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/inventario/ajustar_inventario.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-times-circle me-2 text-danger"></i> Ajustar Inventario
                                </a>
                            </li>
                            
                            
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <div class="col">
            <div class="card h-100 shadow-lg border-success">
                <div class="card-header bg-success text-white d-flex align-items-center">
                    <i class="fas fa-warehouse fa-2x me-3"></i>
                    <h3 class="mb-0">Inventario y Productos</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <a href="/views/inventario/lista_inventario.php" class="d-flex align-items-center text-decoration-none text-dark">
                                <i class="fas fa-boxes me-2 text-success"></i> Ver Inventario
                            </a>
                        </li>
                        <li class="list-group-item">
                            <a href="/views/productos/lista_productos.php" class="d-flex align-items-center text-decoration-none text-dark">
                                <i class="fas fa-cubes me-2 text-info"></i> Lista de Productos
                            </a>
                        </li>
                        <?php if (isset($rol) && $rol === 'admin'): ?>
                            <li class="list-group-item">
                                <a href="/views/productos/nuevo_producto.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-plus me-2 text-primary"></i> Crear Nuevo Producto
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/inventario/reporte_inventario.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-file-invoice me-2 text-secondary"></i> Reporte de Inventario
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/inventario/r_inventario.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-palette me-2 text-dark"></i> Inventario por Color
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
        
        <?php if (isset($rol) && $rol === 'admin'): ?>
            <div class="col">
                <div class="card h-100 shadow-lg border-danger">
                    <div class="card-header bg-danger text-white d-flex align-items-center">
                        <i class="fas fa-truck-loading fa-2x me-3"></i>
                        <h3 class="mb-0">Compras</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <a href="/views/compras/listar_compras.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-boxes me-2 text-danger"></i> Compras de Productos
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/compras/nueva_compra.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-plus-square me-2 text-info"></i> Registrar Nueva Compra
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/compras/listar_compras_servicios.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-wrench me-2 text-secondary"></i> Compras de Servicios
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/compras/nueva_compra_servicio.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-handshake me-2 text-success"></i> Registrar Compra de Servicio
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($rol) && $rol === 'admin'): ?>
            <div class="col">
                <div class="card h-100 shadow-lg border-info">
                    <div class="card-header bg-info text-white d-flex align-items-center">
                        <i class="fas fa-users-cog fa-2x me-3"></i>
                        <h3 class="mb-0">Usuarios</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <a href="/views/usuarios/lista_usuarios.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-users me-2 text-info"></i> Lista de Usuarios
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/usuarios/nuevo_usuario.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-user-plus me-2 text-success"></i> Crear Usuario
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($rol) && $rol === 'admin'): ?>
            <div class="col">
                <div class="card h-100 shadow-lg border-dark">
                    <div class="card-header bg-dark text-white d-flex align-items-center">
                        <i class="fas fa-chart-bar fa-2x me-3"></i>
                        <h3 class="mb-0">Reportes</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <a href="/views/reportes/reporte_ventas.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-file-upload me-2 text-dark"></i> Reporte de Ventas
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/reportes/reporte_compras.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-file-download me-2 text-secondary"></i> Reporte de Compras
                                </a>
                            </li>
                                 <li class="list-group-item">
                                <a href="/views/inventario/reporte_matriz.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-file-download me-2 text-secondary"></i> Reporte de Inventarios
                                </a>
                            </li>
                            </li>
                                 <li class="list-group-item">
                                <a href="/views/cambios/cambios.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-file-download me-2 text-secondary"></i> Cambios de Mcia
                                </a>
                            </li>
                            
                        
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($rol) && $rol === 'admin'): ?>
            <div class="col">
                <div class="card h-100 shadow-lg border-secondary">
                    <div class="card-header bg-secondary text-white d-flex align-items-center">
                        <i class="fas fa-cogs fa-2x me-3"></i>
                        <h3 class="mb-0">Configuración</h3>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <a href="/views/colores/lista_colores.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-palette me-2 text-primary"></i> Colores
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/tallas/lista_tallas.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-ruler me-2 text-success"></i> Tallas
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/proveedores/lista_proveedores.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-truck me-2 text-info"></i> Proveedores
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/clientes/lista_clientes.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-users me-2 text-warning"></i> Clientes
                                </a>
                            </li>
                            <li class="list-group-item">
                                <a href="/views/logs/lista_logs.php" class="d-flex align-items-center text-decoration-none text-dark">
                                    <i class="fas fa-history me-2 text-danger"></i> Registros (Logs)
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </div> <?php if (!isset($rol) || $rol !== 'admin'): ?>
        <div class="alert alert-info text-center" role="alert">
            <i class="fas fa-info-circle me-2"></i> Bienvenido al Dashboard. Usa el menú de navegación para acceder a las funcionalidades disponibles para tu rol.
        </div>
    <?php endif; ?>

    <form method="POST" action="/logout.php" class="text-center mt-5 mb-4">
        <button type="submit" class="btn btn-danger btn-lg shadow-sm">
            <i class="fas fa-sign-out-alt me-2"></i> Cerrar Sesión
        </button>
    </form>
</div> <?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts
if (!file_exists($footer_path)) {
    die("Error crítico: No se encontró el archivo footer.php en la ruta: " . $footer_path . ". Verifique la ruta y los permisos.");
}
include $footer_path;
?>