<?php
// acceso_denegado.php

// Inicia la sesión para acceder a las variables de usuario
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Denegado</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body, html {
            height: 100%;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f8f9fa;
        }
        .denied-container {
            text-align: center;
            padding: 40px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0,0,0,.1);
        }
        .denied-icon {
            font-size: 80px;
            color: #dc3545; /* Rojo de Bootstrap */
            margin-bottom: 20px;
        }
        .denied-container h1 {
            color: #343a40;
            font-weight: bold;
        }
        .denied-container p {
            color: #6c757d;
        }
    </style>
</head>
<body>

<div class="denied-container">
    <div class="denied-icon">
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" fill="currentColor" class="bi bi-x-circle-fill" viewBox="0 0 16 16">
            <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zM5.354 4.646a.5.5 0 1 0-.708.708L7.293 8l-2.647 2.646a.5.5 0 0 0 .708.708L8 8.707l2.646 2.647a.5.5 0 0 0 .708-.708L8.707 8l2.647-2.646a.5.5 0 0 0-.708-.708L8 7.293 5.354 4.646z"/>
        </svg>
    </div>
    <h1 class="display-4">Acceso Denegado</h1>
    <p>No tienes los permisos necesarios para ver esta página.</p>
    <a href="dashboard.php" class="btn btn-primary mt-3">Volver al Dashboard</a>
</div>

</body>
</html>