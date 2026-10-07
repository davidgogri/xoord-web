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

// Consulta para obtener las compras registradas
$stmt = $pdo->query("
    SELECT 
        c.id_compra,
        p.nombre_empresa AS proveedor,
        c.total_compra,
        c.metodo_pago,
        c.fecha_compra
    FROM 
        compras c
    JOIN 
        proveedores p ON c.id_proveedor = p.id_proveedor
    ORDER BY 
        c.fecha_compra DESC
");

$compras = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listar Compras</title>
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

        /* Menu */
        .cabecera nav a {
            color: white !important; /* Forzamos el color blanco */
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        .cabecera nav a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <h2>Listar Compras</h2>

    <?php if (count($compras) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Compra</th>
                    <th>Proveedor</th>
                    <th>Total Compra</th>
                    <th>Método de Pago</th>
                    <th>Fecha Compra</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($compras as $compra): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($compra['id_compra']); ?></td>
                        <td><?php echo htmlspecialchars($compra['proveedor']); ?></td>
                        <td>$<?php echo number_format($compra['total_compra'], 2); ?></td>
                        <td><?php echo htmlspecialchars($compra['metodo_pago']); ?></td>
                        <td><?php echo htmlspecialchars($compra['fecha_compra']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay compras registradas.</p>
    <?php endif; ?>

    <form method="GET" action="../../views/compras/nueva_compra.php">
        <button type="submit" class="btn-volver">Registrar Nueva Compra</button>
    </form>
</body>
</html>