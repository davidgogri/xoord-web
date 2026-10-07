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
//
// Verificar si el usuario tiene permisos de administrador
//function checkAuth($requiredRole) {
//    if ($_SESSION['rol'] !== $requiredRole) {
//        header("Location: ../../dashboard.php");
//        exit;
//    }
//}
//checkAuth('admin');
//?>
//
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Nuevo Usuario</title>
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
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group input:focus,
        .form-group select:focus {
            border-color: #007bff;
            outline: none;
        }

        .btn-registrar {
            display: inline-block;
            padding: 10px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }

        .btn-registrar:hover {
            background-color: #0056b3;
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
            form {
                padding: 15px;
            }

            .form-group {
                margin-bottom: 10px;
            }

            .form-group input,
            .form-group select {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <h2>Registrar Nuevo Usuario</h2>

    <form action="../../controllers/usuarios/registrar_usuario.php" method="POST">
        <!-- Nombre -->
        <div class="form-group">
            <label for="nombre">Nombre:</label>
            <input type="text" id="nombre" name="nombre" placeholder="Ingresa el nombre" required>
        </div>

        <!-- Apellido -->
        <div class="form-group">
            <label for="apellido">Apellido:</label>
            <input type="text" id="apellido" name="apellido" placeholder="Ingresa el apellido">
        </div>

        <!-- Correo Electrónico -->
        <div class="form-group">
            <label for="correo">Correo Electrónico:</label>
            <input type="email" id="correo" name="correo" placeholder="Ingresa el correo electrónico" required>
        </div>

        <!-- Contraseña -->
        <div class="form-group">
            <label for="contrasena">Contraseña:</label>
            <input type="password" id="contrasena" name="contrasena" placeholder="Ingresa una contraseña segura" required>
        </div>

        <!-- Rol -->
        <div class="form-group">
            <label for="rol">Rol:</label>
            <select id="rol" name="rol" required>
                <option value="" disabled selected>Selecciona un rol</option>
                <option value="admin">Administrador</option>
                <option value="vendedor">Vendedor</option>
            </select>
        </div>

        <!-- Botón Registrar -->
        <button type="submit" class="btn-registrar">Registrar Usuario</button>
    </form>

    <!-- Botón Volver al Dashboard -->
    <form method="GET" action="../../dashboard.php">
        <button type="submit" class="btn-volver">Volver al Dashboard</button>
    </form>
</body>
</html>