<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Configurar zona horaria
date_default_timezone_set('America/Bogota');

// Ruta absoluta para incluir db.php
require_once __DIR__ . '/../includes/db.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: /login.php");
    exit;
}

// Obtener información del usuario actual
$stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
$stmt->execute([$_SESSION['usuario_id']]);
$usuario = $stmt->fetch(PDO::FETCH_ASSOC);
$rol = $usuario['rol'] ?? 'usuario';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menú</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> <!-- Favicon -->
    <style>
        /* Estilos generales */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
        }

        .cabecera {
            background-color: #007bff;
            color: white;
            padding: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .cabecera h1 {
            margin: 0;
            font-size: 24px;
        }

        .cabecera nav a {
            color: white;
            text-decoration: none;
            margin-left: 15px;
            font-weight: bold;
        }

        .cabecera nav a:hover {
            text-decoration: underline;
        }

        /* Botón de cerrar sesión */
        .logout-btn {
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

        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <!-- Cabecera -->
    <div class="cabecera">
        <h1>Maka Store</h1>
        <nav>
            <?php if ($rol === 'admin'): ?>
                <a href="/dashboard.php">Dashboard</a>
                <a href="/views/ventas/lista_ventas.php">Ventas</a>
                <a href="/views/inventario/lista_inventario.php">Inventario</a>
                <a href="/views/usuarios/lista_usuarios.php">Usuarios</a>
                <a href="/views/colores/lista_colores.php">Configuración</a>
            <?php else: ?>
                <a href="/views/ventas/lista_ventas.php">Ventas</a>
                <a href="/views/inventario/lista_inventario.php">Inventario</a>
            <?php endif; ?>
        </nav>
    </div>
</body>
</html>