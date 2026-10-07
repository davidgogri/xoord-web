<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de Productos - Maka Store</title>
    <style>
        body { font-family: Arial; padding: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { padding: 10px; border: 1px solid #ccc; text-align: left; }
        a { margin-right: 10px; }
    </style>
</head>
<body>
    <h2>Productos</h2>
    <a href="nuevo_producto.php">Agregar Nuevo Producto</a>
    <table>
        <tr>
            <th>ID</th><th>Nombre</th><th>Precio Venta</th><th>Acciones</th>
        </tr>
        <?php
        require_once '../../includes/db.php';
        require_once '../../includes/functions.php';

        foreach(obtenerProductos($pdo) as $p): ?>
        <tr>
            <td><?= $p['id_producto'] ?></td>
            <td><?= htmlspecialchars($p['nombre']) ?></td>
            <td><?= number_format($p['precio_venta'], 2) ?></td>
            <td>
                <a href="editar_producto.php?id=<?= $p['id_producto'] ?>">Editar</a>
                <a href="../../controllers/productos/eliminar_producto.php?id=<?= $p['id_producto'] ?>" onclick="return confirm('¿Eliminar?')">Eliminar</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>