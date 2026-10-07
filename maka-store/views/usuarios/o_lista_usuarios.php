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

// Consulta para obtener los usuarios
$stmt = $pdo->query("
    SELECT 
        id_usuario,
        CONCAT(nombre, ' ', apellido) AS nombre_completo,
        correo,
        rol,
        fecha_registro,
        estado
    FROM 
        usuarios
    ORDER BY 
        fecha_registro DESC
");

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Usuarios</title>
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
    <h2>Lista de Usuarios</h2>

    <?php if (count($usuarios) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>ID Usuario</th>
                    <th>Nombre Completo</th>
                    <th>Correo</th>
                    <th>Rol</th>
                    <th>Fecha Registro</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                    <tr>
                        <td><?= htmlspecialchars($usuario['id_usuario']) ?></td>
                        <td><?= htmlspecialchars($usuario['nombre_completo']) ?></td>
                        <td><?= htmlspecialchars($usuario['correo']) ?></td>
                        <td><?= htmlspecialchars($usuario['rol']) ?></td>
                        <td><?= htmlspecialchars($usuario['fecha_registro']) ?></td>
                        <td><?= htmlspecialchars($usuario['estado']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>No hay usuarios registrados.</p>
    <?php endif; ?>

    <form method="GET" action="../../dashboard.php">
        <button type="submit" class="btn-volver">Volver al Dashboard</button>
    </form>
</body>
</html>