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

// Inicializar variables de búsqueda
$buscar_texto = $_GET['buscar_texto'] ?? '';
$filtro_estado = $_GET['filtro_estado'] ?? '';

$where_clauses = [];
$params = [];

// Filtro por texto (usuario o correo)
if (!empty($buscar_texto)) {
    $where_clauses[] = "(u.nombre LIKE :buscar_texto OR l.correo LIKE :buscar_texto_correo)";
    $params[':buscar_texto'] = '%' . $buscar_texto . '%';
    $params[':buscar_texto_correo'] = '%' . $buscar_texto . '%';
}

// Filtro por estado
if (!empty($filtro_estado)) {
    $where_clauses[] = "l.estado = :filtro_estado";
    $params[':filtro_estado'] = $filtro_estado;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Consulta para obtener los logs de ingresos
$sql = "
    SELECT
        l.id_log,
        u.nombre AS nombre_usuario,
        l.correo,
        l.fecha_ingreso,
        l.ip_address,
        l.navegador,
        l.estado
    FROM
        logs_ingresos l
    LEFT JOIN
        usuarios u ON l.id_usuario = u.id_usuario
    " . $where_sql . "
    ORDER BY
        l.fecha_ingreso DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-clipboard-list me-2"></i> Logs de Ingresos
        </h1>
        <p class="text-muted text-center">Revisa el historial de accesos al sistema.</p>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="lista_logs.php" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label for="buscar_texto" class="visually-hidden">Buscar</label>
                        <input type="text" class="form-control" id="buscar_texto" name="buscar_texto"
                               placeholder="Buscar por usuario o correo..." value="<?= htmlspecialchars($buscar_texto) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtro_estado" class="visually-hidden">Filtrar por Estado</label>
                        <select class="form-select" id="filtro_estado" name="filtro_estado">
                            <option value="">Todos los Estados</option>
                            <option value="exito" <?= ($filtro_estado === 'exito') ? 'selected' : '' ?>>Éxito</option>
                            <option value="fallo" <?= ($filtro_estado === 'fallo') ? 'selected' : '' ?>>Fallo</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <?php if (count($logs) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID Log</th>
                            <th scope="col">Usuario</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Fecha Ingreso</th>
                            <th scope="col">Dirección IP</th>
                            <th scope="col">Navegador</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr>
                                <td><?= htmlspecialchars($log['id_log']) ?></td>
                                <td><?= htmlspecialchars($log['nombre_usuario'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($log['correo']) ?></td>
                                <td><?= htmlspecialchars($log['fecha_ingreso']) ?></td>
                                <td><?= htmlspecialchars($log['ip_address']) ?></td>
                                <td><?= htmlspecialchars($log['navegador']) ?></td>
                                <td>
                                    <?php
                                        $badge_class = '';
                                        if ($log['estado'] === 'exito') {
                                            $badge_class = 'bg-success';
                                        } elseif ($log['estado'] === 'fallo') {
                                            $badge_class = 'bg-danger';
                                        } else {
                                            $badge_class = 'bg-secondary';
                                        }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars(ucfirst($log['estado'])) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No se encontraron registros de ingresos que coincidan con la búsqueda.
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