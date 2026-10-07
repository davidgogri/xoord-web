<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

include '../../partials/menu.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Compra de Servicio</title>
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

        form {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .form-group {
            margin-bottom: 15px;
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-group button {
            margin-top: 10px;
            padding: 10px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }

        .form-group button:hover {
            background-color: #218838;
        }

        /* Responsive */
        @media (max-width: 600px) {
            form {
                padding: 15px;
            }
            .form-group {
                margin-bottom: 10px;
            }
            .form-group input,
            .form-group select,
            .form-group textarea {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <h2>Registrar Compra de Servicio</h2>
    <form action="../../controllers/compras/registrar_compra_servicio.php" method="POST">
        <!-- Concepto -->
        <div class="form-group">
            <label for="concepto">Concepto:</label>
            <input type="text" name="concepto" placeholder="Ejemplo: Arrendamiento, Salarios, Mantenimiento" required>
        </div>
        <!-- Monto -->
        <div class="form-group">
            <label for="monto">Monto:</label>
            <input type="number" step="0.01" name="monto" placeholder="Monto total" required>
        </div>
        <!-- Método de Pago -->
        <div class="form-group">
            <label for="metodo_pago">Método de Pago:</label>
            <select name="metodo_pago" required>
                <option value="" disabled selected>Seleccionar método de pago</option>
                <option value="Efectivo">Efectivo</option>
                <option value="Transferencia">Transferencia</option>
                <option value="Nequi">Nequi</option>
                <option value="Daviplata">Daviplata</option>
            </select>
        </div>
        <!-- Fecha -->
        <div class="form-group">
            <label for="fecha_compra">Fecha:</label>
            <input type="date" name="fecha_compra" required>
        </div>
        <!-- Descripción -->
        <div class="form-group">
            <label for="descripcion">Descripción (opcional):</label>
            <textarea name="descripcion" rows="3" placeholder="Detalles adicionales"></textarea>
        </div>
        <!-- Botón de registro -->
        <button type="submit" class="btn-registrar-compra">Registrar Compra</button>
    </form>
</body>
</html>