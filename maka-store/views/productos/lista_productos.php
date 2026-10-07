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

// Consulta para obtener los productos registrados
$stmt = $pdo->query("
    SELECT 
        id_producto,
        nombre,
        precio_venta
    FROM 
        productos
    ORDER BY 
        id_producto DESC
");

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Listado de Productos</title>
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

        .btn-agregar {
            display: inline-block;
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin-bottom: 20px;
            text-decoration: none;
        }

        .btn-agregar:hover {
            background-color: #218838;
        }

        .btn-editar {
            display: inline-block;
            padding: 5px 10px;
            background-color: #ffc107;
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }

        .btn-editar:hover {
            background-color: #e0a800;
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
    <h2>Listado de Productos</h2>

    <!-- Botón para agregar nuevo producto -->
    <a href="nuevo_producto.php" class="btn-agregar">➕ Agregar Nuevo Producto</a>

    <?php if (count($productos) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Precio de Venta</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $producto): ?>
                    <tr>
                        <td><?= htmlspecialchars($producto['id_producto']) ?></td>
                        <td><?= htmlspecialchars($producto['nombre']) ?></td>
                        <td>$<?= number_format($producto['precio_venta'], 2) ?></td>
                        <td>
                            <a href="editar_producto.php?id=<?= $producto['id_producto'] ?>" class="btn-editar">Editar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay productos registrados.</p>
    <?php endif; ?>

    <?php include '../../partials/footer.php'; ?>
</body>
</html>