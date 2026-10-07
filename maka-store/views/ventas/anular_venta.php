<?php
// views/ventas/anular_venta.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Revisa si ya hay una sesión iniciada. Si no, la inicia.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Incluir la conexión a la base de datos.
//    La ruta es __DIR__ (views/ventas) -> ../.. (maka-store) -> includes
require_once __DIR__ . '/../../includes/db.php';

// 2. Verificar que el usuario haya iniciado sesión
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = "Debe iniciar sesión para acceder a esta página.";
    header("Location: ../../login.php");
    exit;
}

// 3. Obtener el rol del usuario desde la base de datos usando el id de la sesión
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// 4. Validar que el usuario sea administrador
if (!$usuario || $usuario['rol'] !== 'admin') {
    $_SESSION['error_message'] = "Acceso denegado. Solo los administradores pueden anular ventas.";
    header("Location: ../../dashboard.php"); // Redireccionamos a una página de acceso, por ejemplo, el dashboard
    exit;
}

// Incluye el header
include __DIR__ . '/../../partials/header.php';

// Mostrar mensajes de éxito o error
if (isset($_SESSION['success_message'])) {
    echo '<div class="alert alert-success alert-dismissible fade show mt-4" role="alert">';
    echo $_SESSION['success_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['success_message']);
}
if (isset($_SESSION['error_message'])) {
    echo '<div class="alert alert-danger alert-dismissible fade show mt-4" role="alert">';
    echo $_SESSION['error_message'];
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
    unset($_SESSION['error_message']);
}
?>

<div class="container mt-5">
    <h2>Anular Venta</h2>
    <p>Solo los administradores pueden realizar esta acción. Ingresa el ID de la venta a anular para continuar.</p>
    
    <form action="../../controllers/ventas/anular_venta_controller.php" method="POST">
        <div class="input-group mb-3">
            <input type="number" class="form-control" name="id_venta" placeholder="ID de la Venta a Anular" required>
            <button class="btn btn-outline-secondary" type="submit" name="accion" value="buscar">
                <i class="fas fa-search"></i> Buscar Venta
            </button>
        </div>
    </form>

    <?php
    // Mostrar los detalles de la venta si se encontraron
    if (isset($_SESSION['venta_a_anular']) && is_array($_SESSION['venta_a_anular'])) {
        $venta = $_SESSION['venta_a_anular'];
        $detalles = $_SESSION['detalles_venta'];
        
        echo '<div class="card mt-4">';
        echo '<div class="card-header bg-danger text-white">Detalles de la Venta #' . htmlspecialchars($venta['id_venta']) . '</div>';
        echo '<div class="card-body">';
        echo '<p><strong>Cliente:</strong> ' . htmlspecialchars($venta['nombre_cliente']) . '</p>';
        echo '<p><strong>Fecha de Venta:</strong> ' . htmlspecialchars($venta['fecha_venta']) . '</p>';
        echo '<p><strong>Total:</strong> $' . number_format($venta['total_venta'], 2) . '</p>';
        echo '<p><strong>Punto de Venta:</strong> ' . htmlspecialchars($venta['nombre_punto']) . '</p>';
        echo '<p><strong>Estado:</strong> <span class="badge ' . ($venta['estado'] === 'Anulada' ? 'bg-warning text-dark' : 'bg-success') . '">' . htmlspecialchars($venta['estado']) . '</span></p>';

        if ($venta['estado'] === 'Anulada') {
            echo '<div class="alert alert-warning mt-3" role="alert">Esta venta ya ha sido anulada.</div>';
        } else {
            echo '<h5 class="mt-4">Productos en la venta:</h5>';
            echo '<ul class="list-group mb-3">';
            foreach ($detalles as $detalle) {
                echo '<li class="list-group-item">';
                echo '<strong>Producto:</strong> ' . htmlspecialchars($detalle['nombre_producto']) . '<br>';
                echo '<strong>Color:</strong> ' . htmlspecialchars($detalle['nombre_color']) . ' | ';
                echo '<strong>Talla:</strong> ' . htmlspecialchars($detalle['nombre_talla']) . ' | ';
                echo '<strong>Cantidad:</strong> ' . htmlspecialchars($detalle['cantidad']) . '<br>';
                echo '<strong>Precio Unitario:</strong> $' . number_format($detalle['precio_venta'], 2);
                echo '</li>';
            }
            echo '</ul>';
            
            // Formulario de confirmación para anular la venta
            echo '<form action="../../controllers/ventas/anular_venta_controller.php" method="POST" onsubmit="return confirm(\'¿Estás seguro de que quieres anular esta venta? Esta acción es irreversible y devolverá el stock al inventario.\');">';
            echo '<input type="hidden" name="id_venta_confirmar" value="' . htmlspecialchars($venta['id_venta']) . '">';
            echo '<button type="submit" name="accion" value="anular" class="btn btn-danger">';
            echo '<i class="fas fa-undo me-2"></i> Confirmar Anulación y Devolver Stock';
            echo '</button>';
            echo '</form>';
        }
        
        echo '</div>'; // card-body
        echo '</div>'; // card
        
        // Limpiar la información de la sesión para que no se muestre al recargar sin buscar
        unset($_SESSION['venta_a_anular']);
        unset($_SESSION['detalles_venta']);
    }
    ?>
</div>

<?php
// Incluye el footer.
include __DIR__ . '/../../partials/footer.php';
?>