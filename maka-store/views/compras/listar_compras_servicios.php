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
$buscar_concepto = $_GET['buscar_concepto'] ?? '';
$where_clause = '';
$params = [];

// Si hay un término de búsqueda, construir la cláusula WHERE
if (!empty($buscar_concepto)) {
    $where_clause = " WHERE cs.concepto LIKE :buscar_concepto";
    $params[':buscar_concepto'] = '%' . $buscar_concepto . '%';
}

// Consulta para obtener las compras de servicios
$sql = "
    SELECT
        cs.id_compra_servicio,
        cs.concepto,
        cs.monto,
        cs.metodo_pago,
        cs.fecha_compra,
        cs.descripcion,
        u.nombre AS usuario
    FROM
        compras_servicios cs
    JOIN
        usuarios u ON cs.id_usuario = u.id_usuario
    " . $where_clause . "
    ORDER BY
        cs.fecha_compra DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$compras_servicios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-receipt me-2"></i> Compras de Servicios Registradas
        </h1>
        <p class="text-muted text-center">Gestiona y consulta todos los registros de compras de servicios en tu sistema.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="listar_compras_servicios.php" class="row g-3 align-items-center">
                    <div class="col-md-8">
                        <label for="buscar_concepto" class="visually-hidden">Buscar Concepto</label>
                        <input type="text" class="form-control" id="buscar_concepto" name="buscar_concepto"
                               placeholder="Buscar por concepto de servicio..." value="<?= htmlspecialchars($buscar_concepto) ?>">
                    </div>
                    <div class="col-md-4 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="nueva_compra_servicio.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-2"></i> Registrar Nueva Compra
            </a>
        </div>

        <?php if (count($compras_servicios) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Concepto</th>
                            <th scope="col">Monto</th>
                            <th scope="col">Método de Pago</th>
                            <th scope="col">Fecha</th>
                            <th scope="col">Usuario</th>
                            <th scope="col">Descripción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras_servicios as $compra): ?>
                            <tr>
                                <td><?= htmlspecialchars($compra['id_compra_servicio']) ?></td>
                                <td><?= htmlspecialchars($compra['concepto']) ?></td>
                                <td>$<?= number_format($compra['monto'], 2) ?></td>
                                <td><?= htmlspecialchars($compra['metodo_pago']) ?></td>
                                <td><?= htmlspecialchars($compra['fecha_compra']) ?></td>
                                <td><?= htmlspecialchars($compra['usuario']) ?></td>
                                <td><?= htmlspecialchars($compra['descripcion'] ?? 'N/A') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No se encontraron compras de servicios que coincidan con la búsqueda o no hay registros.
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