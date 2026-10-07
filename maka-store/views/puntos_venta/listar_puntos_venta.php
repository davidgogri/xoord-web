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

// Filtro por texto (nombre, dirección, email, teléfono)
if (!empty($buscar_texto)) {
    $where_clauses[] = "(nombre_punto LIKE :buscar_texto OR direccion LIKE :buscar_texto2 OR email LIKE :buscar_texto3 OR telefono LIKE :buscar_texto4)";
    $params[':buscar_texto'] = '%' . $buscar_texto . '%';
    $params[':buscar_texto2'] = '%' . $buscar_texto . '%';
    $params[':buscar_texto3'] = '%' . $buscar_texto . '%';
    $params[':buscar_texto4'] = '%' . $buscar_texto . '%';
}

// Filtro por estado
if (!empty($filtro_estado)) {
    $where_clauses[] = "estado = :filtro_estado";
    $params[':filtro_estado'] = $filtro_estado;
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Consulta para obtener los puntos de venta
$sql = "
    SELECT
        id_punto_venta,
        nombre_punto,
        direccion,
        telefono,
        email,
        estado,
        fecha_registro
    FROM
        puntos_venta
    " . $where_sql . "
    ORDER BY
        nombre_punto ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$puntos_venta = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-store-alt me-2"></i> Listado de Puntos de Venta
        </h1>
        <p class="text-muted text-center">Administra tus puntos de venta o sucursales.</p>

        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle me-2"></i>
                <?= htmlspecialchars($_SESSION['success_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <?= htmlspecialchars($_SESSION['error_message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" action="listar_puntos_venta.php" class="row g-3 align-items-center">
                    <div class="col-md-5">
                        <label for="buscar_texto" class="visually-hidden">Buscar</label>
                        <input type="text" class="form-control" id="buscar_texto" name="buscar_texto"
                               placeholder="Buscar por nombre, dirección, email o teléfono..." value="<?= htmlspecialchars($buscar_texto) ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="filtro_estado" class="visually-hidden">Filtrar por Estado</label>
                        <select class="form-select" id="filtro_estado" name="filtro_estado">
                            <option value="">Todos los Estados</option>
                            <option value="activo" <?= ($filtro_estado === 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= ($filtro_estado === 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                    <div class="col-md-3 d-grid">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search me-2"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="d-flex justify-content-end mb-3">
            <a href="nuevo_punto_venta.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-2"></i> Agregar Nuevo Punto de Venta
            </a>
        </div>

        <?php if (count($puntos_venta) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Nombre</th>
                            <th scope="col">Dirección</th>
                            <th scope="col">Teléfono</th>
                            <th scope="col">Email</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Fecha Registro</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($puntos_venta as $punto): ?>
                            <tr>
                                <td><?= htmlspecialchars($punto['id_punto_venta']) ?></td>
                                <td><?= htmlspecialchars($punto['nombre_punto']) ?></td>
                                <td><?= htmlspecialchars($punto['direccion']) ?></td>
                                <td><?= htmlspecialchars($punto['telefono'] ?? 'N/A') ?></td>
                                <td><?= htmlspecialchars($punto['email'] ?? 'N/A') ?></td>
                                <td>
                                    <?php
                                        $badge_class = '';
                                        if ($punto['estado'] === 'activo') {
                                            $badge_class = 'bg-success';
                                        } elseif ($punto['estado'] === 'inactivo') {
                                            $badge_class = 'bg-danger';
                                        } else {
                                            $badge_class = 'bg-secondary';
                                        }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars(ucfirst($punto['estado'])) ?></span>
                                </td>
                                <td><?= htmlspecialchars($punto['fecha_registro']) ?></td>
                                </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No se encontraron puntos de venta que coincidan con la búsqueda.
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