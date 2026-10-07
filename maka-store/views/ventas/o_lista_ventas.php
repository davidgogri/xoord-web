<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../partials/menu.php';
require_once '../../includes/db.php';

// Consulta para obtener las ventas registradas
$stmt = $pdo->query("
    SELECT 
        v.id_venta,
        c.nombre AS cliente,
        v.total_venta,
        v.metodo_pago,
        v.fecha_venta
    FROM 
        ventas v
    LEFT JOIN 
        clientes c ON v.id_cliente = c.id_cliente
    ORDER BY 
        v.fecha_venta DESC
");

$ventas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Ventas</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> <!-- Favicon -->
    <style>
        /* Reset básico */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }

        h2 {
            margin-bottom: 20px;
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        table th {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }

        table tr:hover {
            background-color: #f1f1f1;
        }

        .btn-volver {
            display: inline-block;
            padding: 10px 15px;
            background-color: #dc3545;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 20px;
        }

        .btn-volver:hover {
            background-color: #c82333;
        }

        /* Responsive */
        @media (max-width: 600px) {
            table th, table td {
                font-size: 14px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <h2>Ventas Registradas</h2>

    <?php if (count($ventas) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Venta</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Método de Pago</th>
                    <th>Fecha</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ventas as $venta): ?>
                    <tr>
                        <td><?= htmlspecialchars($venta['id_venta']) ?></td>
                        <td><?= htmlspecialchars($venta['cliente'] ?? 'Sin cliente') ?></td>
                        <td>$<?= number_format($venta['total_venta'], 2) ?></td>
                        <td><?= htmlspecialchars($venta['metodo_pago']) ?></td>
                        <td><?= htmlspecialchars($venta['fecha_venta']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay ventas registradas.</p>
    <?php endif; ?>

    <form method="GET" action="../../views/ventas/nueva_venta.php">
        <button type="submit" class="btn-volver">Registrar Nueva Venta</button>
    </form>
</body>
</html>