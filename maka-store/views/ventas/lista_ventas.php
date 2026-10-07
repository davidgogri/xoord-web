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

// Consulta para obtener las últimas 10 ventas registradas
// Ahora usamos LIMIT 10 para restringir los resultados
$stmt = $pdo->query("
    SELECT
        v.id_venta,
        c.nombre AS cliente,
        v.total_venta,
        v.metodo_pago,
        v.fecha_venta
    FROM
        ventas v
    LEFT JOIN
        clientes c ON v.id_cliente = c.id_cliente
    ORDER BY
        v.fecha_venta DESC
    LIMIT 10
");

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-list-alt me-2"></i> Últimas 10 Ventas Registradas
        </h1>
        <p class="text-muted text-center">Visualiza un resumen de las ventas más recientes en tu sistema.</p>

        <?php if (count($ventas) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID Venta</th>
                            <th scope="col">Cliente</th>
                            <th scope="col">Total</th>
                            <th scope="col">Método de Pago</th>
                            <th scope="col">Fecha</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventas as $venta): ?>
                            <tr>
                                <td><?= htmlspecialchars($venta['id_venta']) ?></td>
                                <td><?= htmlspecialchars($venta['cliente'] ?? 'Sin cliente') ?></td>
                                <td>$<?= number_format($venta['total_venta'], 2) ?></td>
                                <td><?= htmlspecialchars($venta['metodo_pago']) ?></td>
                                <td><?= htmlspecialchars($venta['fecha_venta']) ?></td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No hay ventas registradas aún.
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center mt-4 mb-5">
            <a href="nueva_venta.php" class="btn btn-success btn-lg shadow-sm">
                <i class="fas fa-plus-circle me-2"></i> Registrar Nueva Venta
            </a>
        </div>
    </div>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap, etc.
include '../../partials/footer.php';
?>