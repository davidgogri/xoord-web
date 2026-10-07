<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../includes/db.php';

// Obtener el rol del usuario
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if ($usuario['rol'] !== 'admin') {
    header("Location: ../../views/dashboard.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anular Venta</title>
    <style>
        /* Estilos omitidos por brevedad */
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f4f4f4;
            color: #333;
        }
        h2 {
            color: #d9534f;
            text-align: center;
            margin-bottom: 30px;
        }
        form {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Para incluir padding y borde en el ancho total */
            font-size: 16px;
        }
        .btn-anular-venta {
            background-color: #d9534f;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: background-color 0.3s ease;
        }
        .btn-anular-venta:hover {
            background-color: #c9302c;
        }
        .message {
            margin-top: 20px;
            padding: 15px;
            border-radius: 4px;
            text-align: center;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <h2>Anular Venta</h2>
    <form id="formAnularVenta" action="../../controllers/ventas/anular_venta.php" method="POST">
        <div class="form-group">
            <label for="id_venta">Selecciona la venta a anular:</label>
            <select name="id_venta" id="id_venta" required>
                <option value="" disabled selected>Seleccionar venta</option>
                <?php
                try {
                    $query = "
                        SELECT v.id_venta, c.nombre AS nombre_cliente, v.total_venta, v.fecha_venta
                        FROM ventas v
                        JOIN clientes c ON v.id_cliente = c.id_cliente
                        WHERE v.tipo_venta = 'Venta'
                        ORDER BY v.fecha_venta DESC, v.id_venta DESC
                    ";
                    $stmt = $pdo->query($query);
                    if ($stmt->rowCount() === 0) {
                        echo "<option value='' disabled>No hay ventas disponibles para anular</option>";
                    } else {
                        while ($venta = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            // Corrección en la interpolación de la variable para mostrar el total correctamente
                            echo "<option value='{$venta['id_venta']}'>Venta #{$venta['id_venta']} - Cliente: {$venta['nombre_cliente']} - Total: \${$venta['total_venta']} - Fecha: {$venta['fecha_venta']}</option>"; //
                        }
                    }
                } catch (Exception $e) {
                    echo "<option value='' disabled>Error al cargar las ventas: " . htmlspecialchars($e->getMessage()) . "</option>"; //
                }
                ?>
            </select>
        </div>
        <button type="submit" class="btn-anular-venta">Anular Venta</button>
    </form>
    <?php
    // Mostrar mensajes de éxito o error si vienen de la redirección
    if (isset($_SESSION['message'])) {
        $message_class = ($_SESSION['message_type'] == 'success') ? 'success' : 'error';
        echo "<div class='message {$message_class}'>" . htmlspecialchars($_SESSION['message']) . "</div>";
        unset($_SESSION['message']); // Limpiar el mensaje después de mostrarlo
        unset($_SESSION['message_type']);
    }
    ?>
</body>
</html>