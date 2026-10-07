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
            <i class="fas fa-store me-2"></i> Registrar Nuevo Punto de Venta
        </h1>
        <p class="text-muted text-center">Completa los datos para añadir un nuevo punto de venta o sucursal.</p>

        <div class="card shadow-lg mb-5 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Información del Punto de Venta</h5>
            </div>
            <div class="card-body">
                <form action="../../controllers/puntos_venta/registrar_punto_venta.php" method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="nombre_punto" class="form-label"><i class="fas fa-building me-2"></i> Nombre del Punto de Venta:</label>
                            <input type="text" class="form-control" id="nombre_punto" name="nombre_punto" placeholder="Ej: Sucursal Centro, Bodega Principal" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="direccion" class="form-label"><i class="fas fa-map-marker-alt me-2"></i> Dirección:</label>
                            <input type="text" class="form-control" id="direccion" name="direccion" placeholder="Ej: Calle 10 # 5-20, Bogotá" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="telefono" class="form-label"><i class="fas fa-phone me-2"></i> Teléfono:</label>
                            <input type="tel" class="form-control" id="telefono" name="telefono" placeholder="Ej: +57 300 123 4567">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="email" class="form-label"><i class="fas fa-envelope me-2"></i> Email:</label>
                            <input type="email" class="form-control" id="email" name="email" placeholder="Ej: info@ejemplo.com">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="estado" class="form-label"><i class="fas fa-toggle-on me-2"></i> Estado:</label>
                        <select class="form-select" id="estado" name="estado" required>
                            <option value="activo" selected>Activo</option>
                            <option value="inactivo">Inactivo</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <a href="listar_puntos_venta.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-alt-circle-left me-2"></i> Volver a la Lista
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Guardar Punto de Venta
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