<?php
// Activar visualización de errores para depuración (quitar en producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Configura la zona horaria a 'America/Bogota'
date_default_timezone_set('America/Bogota');

// Si el usuario no está logueado, redirigir al login
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Incluir el archivo de conexión a la base de datos
try {
    require_once __DIR__ . '/../../includes/db.php';
} catch (Exception $e) {
    die("Error al incluir db.php: " . $e->getMessage());
}

// Consulta SQL para obtener el inventario
try {
    $stmt = $pdo->query("SELECT Producto, Color, Talla, Stock, Costo FROM v_Inventario");
    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al consultar el inventario: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Inventario</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> <!-- Favicon -->
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            padding: 20px;
        }
        h1 {
            margin-bottom: 20px;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        table th {
            background-color: #f2f2f2;
        }
        .no-results {
            color: #888;
            text-align: center;
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Cabecera -->
    <?php include __DIR__ . '/../../partials/menu.php'; ?>

    <!-- Contenido principal -->
    <div class="contenido">
        <h1>Consulta de Inventario</h1>

        <?php if (!empty($inventario)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Producto</th>
                        <th>Color</th>
                        <th>Talla</th>
                        <th>Stock</th>
                        <th>Costo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventario as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['Producto']); ?></td>
                            <td><?php echo htmlspecialchars($item['Color']); ?></td>
                            <td><?php echo htmlspecialchars($item['Talla']); ?></td>
                            <td><?php echo htmlspecialchars($item['Stock']); ?></td>
                            <td>$<?php echo number_format($item['Costo'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="no-results">No hay datos disponibles en el inventario.</p>
        <?php endif; ?>
    </div>
</body>
</html>