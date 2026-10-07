<?php
// controllers/cambios/buscar_origen.php

// ** RUTA CRÍTICA **: Desde controllers/cambios/ hasta maka-store/includes/db.php
require_once __DIR__ . '/../../includes/db.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? '';
$id = (int)($_GET['id'] ?? 0);

if (!in_array($tipo, ['venta', 'compra']) || $id <= 0) {
    echo json_encode(['error' => 'Parámetros de búsqueda inválidos.']);
    exit;
}

try {
    $resultado = [];

    if ($tipo === 'compra') {
        // CORRECCIÓN: Usar 'nombre_empresa' de la tabla 'proveedores'
        $sql_compra = "
            SELECT 
                c.id_compra, c.total_compra, c.fecha_compra,
                p.nombre_empresa AS nombre_relacionado, 
                p.id_proveedor
            FROM compras c
            JOIN proveedores p ON c.id_proveedor = p.id_proveedor
            WHERE c.id_compra = :id_compra
        ";
        $stmt = $pdo->prepare($sql_compra);
        $stmt->bindParam(':id_compra', $id, PDO::PARAM_INT);
        $stmt->execute();
        $compra = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$compra) {
            echo json_encode(['error' => "Compra ID $id no encontrada o no existe."]);
            exit;
        }

        $resultado = [
            'id_transaccion' => $compra['id_compra'],
            'tipo' => 'compra',
            'total' => $compra['total_compra'],
            'nombre_relacionado' => $compra['nombre_relacionado'],
            'detalles' => []
        ];

        // Buscar los detalles de la compra (productos)
        $sql_detalles = "
            SELECT 
                dc.id_producto, dc.id_color, dc.id_talla, dc.cantidad,
                pr.nombre AS nombre_producto,
                co.nombre_color,
                ta.nombre_talla
            FROM detalles_compra dc
            JOIN productos pr ON dc.id_producto = pr.id_producto
            LEFT JOIN colores co ON dc.id_color = co.id_color
            LEFT JOIN tallas ta ON dc.id_talla = ta.id_talla
            WHERE dc.id_compra = :id_compra
        ";
        $stmt_detalles = $pdo->prepare($sql_detalles);
        $stmt_detalles->bindParam(':id_compra', $id, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);
        
        // ** VERIFICACIÓN **: Si no hay detalles, se puede devolver un error.
        if (empty($detalles)) {
             echo json_encode(['error' => "La Compra ID $id no tiene productos detallados."]);
             exit;
        }

        $resultado['detalles'] = $detalles;
        
    } elseif ($tipo === 'venta') {
        // Lógica para buscar Venta (usando 'clientes')
        // El nombre de la columna en 'clientes' es 'nombre_cliente'
        $sql_venta = "
            SELECT 
                v.id_venta, v.total_venta, v.fecha_venta,
                c.nombre_cliente AS nombre_relacionado,
                c.id_cliente
            FROM ventas v
            JOIN clientes c ON v.id_cliente = c.id_cliente
            WHERE v.id_venta = :id_venta
        ";
        $stmt = $pdo->prepare($sql_venta);
        $stmt->bindParam(':id_venta', $id, PDO::PARAM_INT);
        $stmt->execute();
        $venta = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$venta) {
            echo json_encode(['error' => "Venta ID $id no encontrada o no existe."]);
            exit;
        }

        $resultado = [
            'id_transaccion' => $venta['id_venta'],
            'tipo' => 'venta',
            'total' => $venta['total_venta'],
            'nombre_relacionado' => $venta['nombre_relacionado'],
            'detalles' => []
        ];

        // Buscar los detalles de la venta (productos)
        $sql_detalles = "
            SELECT 
                dv.id_producto, dv.id_color, dv.id_talla, dv.cantidad,
                pr.nombre AS nombre_producto,
                co.nombre_color,
                ta.nombre_talla
            FROM detalles_venta dv
            JOIN productos pr ON dv.id_producto = pr.id_producto
            LEFT JOIN colores co ON dv.id_color = co.id_color
            LEFT JOIN tallas ta ON dv.id_talla = ta.id_talla
            WHERE dv.id_venta = :id_venta
        ";
        $stmt_detalles = $pdo->prepare($sql_detalles);
        $stmt_detalles->bindParam(':id_venta', $id, PDO::PARAM_INT);
        $stmt_detalles->execute();
        $detalles = $stmt_detalles->fetchAll(PDO::FETCH_ASSOC);

        if (empty($detalles)) {
             echo json_encode(['error' => "La Venta ID $id no tiene productos detallados."]);
             exit;
        }
        
        $resultado['detalles'] = $detalles;
    }
    
    // Devolver Resultado Exitoso
    echo json_encode($resultado);

} catch (PDOException $e) {
    // Manejo de Error de Base de Datos
    error_log("Error de BD en buscar_origen.php: " . $e->getMessage(), 0);
    echo json_encode(['error' => 'Error de base de datos al buscar transacción. Revise los logs del servidor. (Detalles: ' . $e->getMessage() . ')']);
} catch (Exception $e) {
    error_log("Error general en buscar_origen.php: " . $e->getMessage(), 0);
    echo json_encode(['error' => 'Error inesperado al buscar transacción.']);
}
?>