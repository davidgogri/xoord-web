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
            <i class="fas fa-file-invoice-dollar me-2"></i> Registrar Compra de Servicio
        </h1>
        <p class="text-muted text-center">Completa los datos para registrar un nuevo gasto o compra de servicio.</p>

        <div class="card shadow-lg mb-5 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i> Detalles de la Compra</h5>
            </div>
            <div class="card-body">
                <form action="../../controllers/compras/registrar_compra_servicio.php" method="POST">
                    <div class="mb-3">
                        <label for="concepto" class="form-label"><i class="fas fa-tag me-2"></i> Concepto:</label>
                        <input type="text" class="form-control" id="concepto" name="concepto" placeholder="Ejemplo: Arrendamiento, Salarios, Mantenimiento" required>
                    </div>
                    <div class="mb-3">
                        <label for="monto" class="form-label"><i class="fas fa-dollar-sign me-2"></i> Monto:</label>
                        <input type="number" step="0.01" class="form-control" id="monto" name="monto" placeholder="Monto total" required>
                    </div>
                    <div class="mb-3">
                        <label for="metodo_pago" class="form-label"><i class="fas fa-credit-card me-2"></i> Método de Pago:</label>
                        <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                            <option value="" disabled selected>Seleccionar método de pago</option>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Transferencia">Transferencia</option>
                            <option value="Nequi">Nequi</option>
                            <option value="Daviplata">Daviplata</option>
                            <option value="Tarjeta de Credito">Tarjeta de Crédito</option>
                            <option value="Tarjeta de Debito">Tarjeta de Débito</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="fecha_compra" class="form-label"><i class="fas fa-calendar-alt me-2"></i> Fecha:</label>
                        <input type="date" class="form-control" id="fecha_compra" name="fecha_compra" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label"><i class="fas fa-align-left me-2"></i> Descripción (opcional):</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3" placeholder="Detalles adicionales sobre la compra del servicio"></textarea>
                    </div>
                    <div class="d-flex justify-content-between mt-4">
                        <a href="listar_compras_servicios.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-alt-circle-left me-2"></i> Volver a la Lista
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i> Registrar Compra
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