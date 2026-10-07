<?php if (session_status() == PHP_SESSION_NONE) session_start(); ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Maka Store - Productos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Tu ruta al CSS -->
    <link rel="stylesheet" href="/css/styles.css">
</head>
<body>
<header>
    <h1>🛍️ Maka Store</h1>
    <p>Gestión Integral de Inventario y Ventas</p>
</header>

<nav>
    <a href="/dashboard.php">🏠 Inicio</a>
    <a href="/views/productos/lista_productos.php">📦 Productos</a>
    <a href="/views/clientes/lista_clientes.php">🧑‍💼 Clientes</a>
    <a href="/views/ventas/nueva_venta.php">🛒 Vender</a>
    <a href="/views/compras/nueva_compra.php">📥 Compras</a>
    <a href="/logout.php" style="color:#e53935;">🚪 Salir</a>
</nav>