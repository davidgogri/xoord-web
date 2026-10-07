<?php
// Incluir el header, que maneja la sesión, conexión DB y el HTML inicial
// Asegúrate de que la ruta sea correcta desde 'views/inventario/'
include __DIR__ . '/../../partials/header.php'; 

// Las variables $pdo y $rol ya están disponibles desde partials/header.php

// Si el usuario no está logueado (redundante si header.php ya lo hace, pero seguro)
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Verificar si el usuario tiene permisos para ver el reporte
if ($rol !== 'admin') {
    // Redirigir o mostrar un mensaje de error amigable con Bootstrap
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger" role="alert">
            No tienes permisos para acceder a esta página.
            <a href="/dashboard.php" class="alert-link">Volver al Dashboard</a>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../partials/footer.php';
    exit; // Es importante salir después de incluir el footer y mostrar el mensaje
}

// Consulta SQL para obtener el inventario
try {
    $stmt = $pdo->query("SELECT Producto, Talla, Stock, Costo FROM v_Prod_Inventario");
    $inventario = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Mostrar un error más amigable en caso de problema de BD
    ?>
    <div class="container mt-5">
        <div class="alert alert-danger" role="alert">
            Error al consultar el inventario: <?php echo htmlspecialchars($e->getMessage()); ?>
        </div>
    </div>
    <?php
    include __DIR__ . '/../../partials/footer.php';
    exit;
}
?>

<h1 class="mb-4">Reporte de Inventario</h1>

<?php if (!empty($inventario)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Inventario Actual</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-bordered">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Talla</th>
                            <th class="text-end">Stock</th>
                            <th class="text-end">Costo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventario as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['Producto']); ?></td>
                                <td><?php echo htmlspecialchars($item['Talla']); ?></td>
                                <td class="text-end"><?php echo htmlspecialchars($item['Stock']); ?></td>
                                <td class="text-end">$<?php echo number_format($item['Costo'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info" role="alert">
        No hay datos disponibles en el inventario.
    </div>
<?php endif; ?>

<?php 
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap
include __DIR__ . '/../../partials/footer.php'; 
?>