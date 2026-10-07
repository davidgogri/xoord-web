<?php
// controllers/ventas/anular_venta_controller.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Incluir la conexión a la base de datos
//    Ruta: desde 'controllers/ventas' salimos dos niveles y entramos a 'includes'
require_once __DIR__ . '/../../includes/db.php';

// 2. Obtener el rol del usuario desde la base de datos
//    Esto es crucial para que la validación funcione con tu setup actual de login.php
if (!isset($_SESSION['usuario_id'])) {
    $_SESSION['error_message'] = "Debe iniciar sesión para registrar una venta.";
    header("Location: ../../login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// 3. Validar que el usuario sea administrador
if (!$usuario || $usuario['rol'] !== 'admin') {
    $_SESSION['error_message'] = "Acceso denegado. Solo los administradores pueden anular ventas.";
    header("Location: ../../dashboard.php");
    exit;
}

// 4. Procesar la solicitud del formulario
$accion = $_POST['accion'] ?? '';

try {
    // Manejar la acción de 'buscar'
    if ($accion === 'buscar') {
        $id_venta = filter_input(INPUT_POST, 'id_venta', FILTER_VALIDATE_INT);
        if (!$id_venta) {
            $_SESSION['error_message'] = "El ID de la venta no es válido.";
            header("Location: ../../views/ventas/anular_venta.php");
            exit;
        }

        // Consultar los detalles de la venta (incluyendo cliente y punto de venta)
        $stmt = $pdo->prepare("
            SELECT
                v.id_venta,
                v.fecha_venta,
                v.total_venta,
                v.tipo_venta as estado,
                c.nombre as nombre_cliente,
                c.apellido as apellido_cliente,
                pv.nombre_punto,
                pv.id_punto_venta
            FROM ventas v
            JOIN clientes c ON v.id_cliente = c.id_cliente
            JOIN puntos_venta pv ON v.id_punto_venta = pv.id_punto_venta
            WHERE v.id_venta = :id_venta
        ");
        $stmt->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            $_SESSION['error_message'] = "Venta con ID " . htmlspecialchars($id_venta) . " no encontrada.";
            header("Location: ../../views/ventas/anular_venta.php");
            exit;
        }

        // Consultar los detalles de los productos en la venta
        $stmt_detalles = $pdo->prepare("
            SELECT
                dv.id_producto,
                dv.id_color,
                dv.id_talla,
                dv.cantidad,
                dv.precio_venta,
                p.nombre as nombre_producto,
                co.nombre_color,
                t.nombre_talla
            FROM detalles_venta dv
            JOIN productos p ON dv.id_producto = p.id_producto
            JOIN colores co ON dv.id_color = co.id_color
            JOIN tallas t ON dv.id_talla = t.id_talla
            WHERE dv.id_venta = :id_venta
        ");
        $stmt_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

        // Guardar los datos en la sesión para que la vista los muestre
        $venta['nombre_cliente'] = trim($venta['nombre_cliente'] . ' ' . $venta['apellido_cliente']);
        $_SESSION['venta_a_anular'] = $venta;
        $_SESSION['detalles_venta'] = $detalles;

        $_SESSION['success_message'] = "Detalles de la venta #" . htmlspecialchars($id_venta) . " cargados correctamente. Revísalos y confirma la anulación.";

    } elseif ($accion === 'anular') {
        // Manejar la acción de 'anular'
        $id_venta = filter_input(INPUT_POST, 'id_venta_confirmar', FILTER_VALIDATE_INT);
        if (!$id_venta) {
            $_SESSION['error_message'] = "El ID de la venta no es válido.";
            header("Location: ../../views/ventas/anular_venta.php");
            exit;
        }

        // Iniciar una transacción para asegurar la integridad de los datos
        $pdo->beginTransaction();

        // 1. Verificar el estado actual de la venta para evitar doble anulación
        $stmt_estado = $pdo->prepare("SELECT tipo_venta, id_punto_venta FROM ventas WHERE id_venta = :id_venta");
        $stmt_estado->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_estado->execute();
        $venta_info = $stmt_estado->fetch(PDO::FETCH_ASSOC);

        if (!$venta_info) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Venta no encontrada.";
            header("Location: ../../views/ventas/anular_venta.php");
            exit;
        }
        
        if ($venta_info['tipo_venta'] === 'Anulada') {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Esta venta ya ha sido anulada previamente.";
            header("Location: ../../views/ventas/anular_venta.php");
            exit;
        }

        // 2. Obtener los detalles de la venta para devolver el stock
        $stmt_detalles = $pdo->prepare("
            SELECT
                id_producto,
                id_color,
                id_talla,
                cantidad
            FROM detalles_venta
            WHERE id_venta = :id_venta
        ");
        $stmt_detalles->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
        
        // 3. Devolver la mercancía al inventario
        $stmt_devolver_stock = $pdo->prepare("
            UPDATE inventario
            SET cantidad_stock = cantidad_stock + :cantidad
            WHERE id_producto = :id_producto
            AND id_color = :id_color
            AND id_talla = :id_talla
            AND id_punto_venta = :id_punto_venta
        ");

        foreach ($detalles as $detalle) {
            $stmt_devolver_stock->bindParam(':cantidad', $detalle['cantidad'], PDO::PARAM_INT);
            $stmt_devolver_stock->bindParam(':id_producto', $detalle['id_producto'], PDO::PARAM_INT);
            $stmt_devolver_stock->bindParam(':id_color', $detalle['id_color'], PDO::PARAM_INT);
            $stmt_devolver_stock->bindParam(':id_talla', $detalle['id_talla'], PDO::PARAM_INT);
            $stmt_devolver_stock->bindParam(':id_punto_venta', $venta_info['id_punto_venta'], PDO::PARAM_INT);
            $stmt_devolver_stock->execute();
        }

        // 4. Actualizar el estado de la venta a 'Anulada'
        $stmt_anular = $pdo->prepare("
            UPDATE ventas
            SET tipo_venta = 'Anulada'
            WHERE id_venta = :id_venta
        ");
        $stmt_anular->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_anular->execute();

        // Si todo salió bien, confirmar la transacción
        $pdo->commit();

        $_SESSION['success_message'] = "Venta #" . htmlspecialchars($id_venta) . " anulada exitosamente. El inventario ha sido actualizado.";

    } else {
        $_SESSION['error_message'] = "Acción no válida.";
    }
} catch (PDOException $e) {
    // Si la transacción está activa, deshacerla en caso de error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error de base de datos al anular la venta: " . $e->getMessage());
    $_SESSION['error_message'] = "Error de base de datos al anular la venta: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general al anular la venta: " . $e->getMessage());
    $_SESSION['error_message'] = "Error inesperado: " . $e->getMessage();
}

// Redireccionar siempre a la vista para mostrar el mensaje
header("Location: ../../views/ventas/anular_venta.php");
exit;
?>