<?php
// controllers/ventas/anular_venta_controller.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Revisa que el usuario sea administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'admin') {
    $_SESSION['error_message'] = "Acceso denegado. Solo los administradores pueden anular ventas.";
    header("Location: ../../login.php");
    exit;
}

// Incluir la conexión a la base de datos
// La ruta es __DIR__ (controllers/ventas) -> ../.. (maka-store) -> includes
require_once __DIR__ . '/../../includes/db.php';

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
                pv.nombre_punto
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

        $pdo->beginTransaction();

        // 1. Verificar el estado actual de la venta para evitar doble anulación
        $stmt_estado = $pdo->prepare("SELECT tipo_venta FROM ventas WHERE id_venta = :id_venta");
        $stmt_estado->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_estado->execute();
        $venta_estado = $stmt_estado->fetch(PDO::FETCH_ASSOC);

        if ($venta_estado && $venta_estado['tipo_venta'] === 'Anulada') {
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

        // 3. Obtener el punto de venta de la venta
        $stmt_pv = $pdo->prepare("SELECT id_punto_venta FROM ventas WHERE id_venta = :id_venta");
        $stmt_pv->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_pv->execute();
        $id_punto_venta = $stmt_pv->fetchColumn();

        // 4. Devolver la mercancía al inventario
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
            $stmt_devolver_stock->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
            $stmt_devolver_stock->execute();
        }

        // 5. Actualizar el estado de la venta a 'Anulada'
        $stmt_anular = $pdo->prepare("
            UPDATE ventas
            SET tipo_venta = 'Anulada'
            WHERE id_venta = :id_venta
        ");
        $stmt_anular->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
        $stmt_anular->execute();

        $pdo->commit();

        $_SESSION['success_message'] = "Venta #" . htmlspecialchars($id_venta) . " anulada exitosamente. El inventario ha sido actualizado.";

    }
} catch (PDOException $e) {
    // En caso de error, deshacer la transacción
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error al anular la venta: " . $e->getMessage());
    $_SESSION['error_message'] = "Error de base de datos: " . $e->getMessage();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Error general al anular la venta: " . $e->getMessage());
    $_SESSION['error_message'] = "Error inesperado al anular la venta: " . $e->getMessage();
}

// Redireccionar siempre a la vista para mostrar el mensaje
header("Location: ../../views/ventas/anular_venta.php");
exit;
?>