<?php
header('Content-Type: application/json');

// Asegúrate de incluir tu archivo de conexión a la base de datos
require_once '../../config/database.php';
// Incluye los modelos necesarios
require_once '../../models/Compra.php';
require_once '../../models/DetalleCompra.php';
require_once '../../models/Inventario.php';

// Inicializar la conexión a la base de datos
$database = new Database();
$db = $database->getConnection();

// Inicializar los modelos
$compra = new Compra($db);
$detalleCompra = new DetalleCompra($db);
$inventario = new Inventario($db);

// Obtener los datos JSON de la solicitud
$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->id_proveedor) &&
    !empty($data->id_usuario) &&
    !empty($data->metodo_pago) &&
    !empty($data->id_punto_venta) &&
    !empty($data->productos) &&
    is_array($data->productos)
) {
    try {
        // Iniciar una transacción para asegurar la atomicidad de las operaciones
        $db->beginTransaction();

        // 1. Insertar en la tabla `compras`
        $compra->id_proveedor = $data->id_proveedor;
        $compra->id_usuario = $data->id_usuario;
        $compra->metodo_pago = $data->metodo_pago;
        $compra->id_punto_venta = $data->id_punto_venta;
        $compra->total_compra = 0; // Se actualizará después de procesar los detalles

        if ($compra->create()) {
            $id_compra = $db->lastInsertId();
            $total_compra_calculado = 0;

            // 2. Insertar en la tabla `detalles_compra` y actualizar `inventario`
            foreach ($data->productos as $producto_detalle) {
                if (
                    !empty($producto_detalle->id_producto) &&
                    !empty($producto_detalle->id_color) &&
                    !empty($producto_detalle->id_talla) &&
                    !empty($producto_detalle->cantidad) &&
                    !empty($producto_detalle->precio_compra)
                ) {
                    $cantidad = (int) $producto_detalle->cantidad;
                    $precio_compra_unitario = (float) $producto_detalle->precio_compra;
                    $subtotal = $cantidad * $precio_compra_unitario;

                    // Insertar detalle de compra
                    $detalleCompra->id_compra = $id_compra;
                    $detalleCompra->id_producto = $producto_detalle->id_producto;
                    $detalleCompra->id_color = $producto_detalle->id_color;
                    $detalleCompra->id_talla = $producto_detalle->id_talla;
                    $detalleCompra->cantidad = $cantidad;
                    $detalleCompra->precio_compra = $precio_compra_unitario;
                    $detalleCompra->subtotal = $subtotal;

                    if (!$detalleCompra->create()) {
                        throw new Exception("Error al insertar detalle de compra para producto ID: " . $producto_detalle->id_producto);
                    }

                    // Actualizar o insertar en `inventario`
                    $inventario->id_producto = $producto_detalle->id_producto;
                    $inventario->id_color = $producto_detalle->id_color;
                    $inventario->id_talla = $producto_detalle->id_talla;
                    $inventario->id_punto_venta = $data->id_punto_venta; // El inventario se actualiza en el punto de venta de la compra

                    if ($inventario->exists()) {
                        // Si el producto ya existe en inventario para ese color, talla y punto de venta
                        if (!$inventario->updateStock($cantidad)) { // Aumentar stock
                            throw new Exception("Error al actualizar inventario para producto ID: " . $producto_detalle->id_producto);
                        }
                    } else {
                        // Si no existe, crearlo
                        $inventario->cantidad_stock = $cantidad;
                        if (!$inventario->create()) {
                            throw new Exception("Error al crear nuevo registro de inventario para producto ID: " . $producto_detalle->id_producto);
                        }
                    }

                    $total_compra_calculado += $subtotal;
                } else {
                    throw new Exception("Datos incompletos para un detalle de producto.");
                }
            }

            // 3. Actualizar el `total_compra` en la tabla `compras`
            $compra->id_compra = $id_compra;
            $compra->total_compra = $total_compra_calculado;

            if ($compra->updateTotal()) { // Necesitas un método updateTotal en tu modelo Compra
                $db->commit();
                echo json_encode(array("success" => true, "message" => "Compra registrada con éxito.", "id_compra" => $id_compra));
            } else {
                throw new Exception("Error al actualizar el total de la compra.");
            }

        } else {
            throw new Exception("Error al crear la compra principal.");
        }

    } catch (Exception $e) {
        $db->rollBack(); // Revertir la transacción si algo sale mal
        echo json_encode(array("success" => false, "message" => $e->getMessage()));
    }

} else {
    echo json_encode(array("success" => false, "message" => "Datos incompletos para registrar la compra."));
}
?>