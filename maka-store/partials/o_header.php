<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

date_default_timezone_set('America/Bogota');

require_once __DIR__ . '/../includes/db.php';

if (!isset($_SESSION['usuario_id']) && basename($_SERVER['PHP_SELF']) !== 'login.php') {
    header("Location: /login.php");
    exit;
}

$rol = 'guest'; 
if (isset($_SESSION['usuario_id'])) {
    $stmt = $pdo->prepare("SELECT rol FROM usuarios WHERE id_usuario = ?");
    $stmt->execute([$_SESSION['usuario_id']]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
    $rol = $usuario['rol'] ?? 'usuario';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'Maka Store'; ?></title>
    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> 
    <style>
        body {
            padding-top: 56px;
        }
        .content-wrapper {
            padding: 20px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/dashboard.php">Maka Store</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto"> 
                    <?php if (isset($_SESSION['usuario_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="/dashboard.php">Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/views/ventas/') !== false) ? 'active' : ''; ?>" href="/views/ventas/lista_ventas.php">Ventas</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/views/inventario/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/productos/') !== false) ? 'active' : ''; ?>" href="/views/inventario/lista_inventario.php">Inventario</a>
                        </li>
                        <?php if ($rol === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], '/views/usuarios/') !== false) ? 'active' : ''; ?>" href="/views/usuarios/lista_usuarios.php">Usuarios</a>
                            </li>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['REQUEST_URI'], '/views/colores/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/tallas/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/proveedores/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/clientes/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/compras/') !== false || strpos($_SERVER['REQUEST_URI'], '/views/logs/') !== false) ? 'active' : ''; ?>" href="#" id="navbarDropdownConfig" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                    Configuración
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdownConfig">
                                    <li><a class="dropdown-item" href="/views/colores/lista_colores.php">Colores</a></li>
                                    <li><a class="dropdown-item" href="/views/tallas/lista_tallas.php">Tallas</a></li>
                                    <li><a class="dropdown-item" href="/views/proveedores/lista_proveedores.php">Proveedores</a></li>
                                    <li><a class="dropdown-item" href="/views/clientes/lista_clientes.php">Clientes</a></li>
                                    <li><a class="dropdown-item" href="/views/compras/listar_compras_servicios.php">Compras de Servicios</a></li>
                                    <li><a class="dropdown-item" href="/views/logs/lista_logs.php">Logs</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php">Cerrar Sesión</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">