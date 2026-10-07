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

// Consulta para obtener los usuarios
$stmt = $pdo->query("
    SELECT
        id_usuario,
        CONCAT(nombre, ' ', apellido) AS nombre_completo,
        correo,
        rol,
        fecha_registro,
        estado
    FROM
        usuarios
    ORDER BY
        fecha_registro DESC
");

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-users me-2"></i> Gestión de Usuarios
        </h1>
        <p class="text-muted text-center">Aquí puedes ver la lista de usuarios registrados en el sistema.</p>

        <?php if (count($usuarios) > 0): ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover shadow-sm rounded overflow-hidden">
                    <thead class="bg-primary text-white">
                        <tr>
                            <th scope="col">ID Usuario</th>
                            <th scope="col">Nombre Completo</th>
                            <th scope="col">Correo</th>
                            <th scope="col">Rol</th>
                            <th scope="col">Fecha Registro</th>
                            <th scope="col">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr>
                                <td><?= htmlspecialchars($usuario['id_usuario']) ?></td>
                                <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                                <td><?= htmlspecialchars($usuario['correo']) ?></td>
                                <td><?= htmlspecialchars($usuario['rol']) ?></td>
                                <td><?= htmlspecialchars($usuario['fecha_registro']) ?></td>
                                <td>
                                    <?php
                                        // Muestra el estado con un badge de Bootstrap
                                        $badge_class = '';
                                        switch ($usuario['estado']) {
                                            case 'activo':
                                                $badge_class = 'bg-success';
                                                break;
                                            case 'inactivo':
                                                $badge_class = 'bg-danger';
                                                break;
                                            case 'pendiente':
                                                $badge_class = 'bg-warning text-dark';
                                                break;
                                            default:
                                                $badge_class = 'bg-secondary';
                                                break;
                                        }
                                    ?>
                                    <span class="badge <?= $badge_class ?>"><?= htmlspecialchars(ucfirst($usuario['estado'])) ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="alert alert-info text-center mt-5" role="alert">
                <i class="fas fa-info-circle me-2"></i> No hay usuarios registrados en el sistema.
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