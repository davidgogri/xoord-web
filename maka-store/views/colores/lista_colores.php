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

// Consulta para obtener los colores
$stmt = $pdo->query("SELECT id_color, nombre_color FROM colores ORDER BY nombre_color ASC");
$colores = $stmt->fetchAll(PDO::FETCH_ASSOC); // Obtener todos los resultados de una vez
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-palette me-2"></i> Listado de Colores
        </h1>
        <p class="text-muted text-center">Administra los colores disponibles para tus productos.</p>

        <div class="d-flex justify-content-end mb-3">
            <a href="nuevo_color.php" class="btn btn-success">
                <i class="fas fa-plus-circle me-2"></i> Agregar Nuevo Color
            </a>
        </div>

        <?php if (count($colores) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col">Color</th>
                            </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($colores as $color): // Usar foreach ya que ya se obtuvo todo con fetchAll ?>
                            <tr>
                                <td><?= htmlspecialchars($color['id_color']) ?></td>
                                <td><?= htmlspecialchars($color['nombre_color']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No hay colores registrados en el sistema.
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