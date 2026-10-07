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
?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center">
            <i class="fas fa-plus-circle me-2"></i> Registrar Nuevo Color
        </h1>
        <p class="text-muted text-center">Introduce el nombre del nuevo color para agregarlo al sistema.</p>

        <div class="card shadow-lg mb-5 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-palette me-2"></i> Datos del Color</h5>
            </div>
            <div class="card-body">
                <form action="../../controllers/colores/registrar_color.php" method="POST">
                    <div class="mb-3">
                        <label for="nombre_color" class="form-label"><i class="fas fa-tag me-2"></i>Nombre del Color:</label>
                        <input type="text" class="form-control" id="nombre_color" name="nombre_color" placeholder="Ej. Rojo, Azul, Negro" required>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="lista_colores.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-alt-circle-left me-2"></i> Volver a la Lista
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Guardar Color
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap, etc.
include '../../partials/footer.php';
?>