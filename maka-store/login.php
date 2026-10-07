<?php
session_start();

// Si el usuario ya está logueado, redirigir al dashboard
if (isset($_SESSION['usuario_id'])) {
    header("Location: /dashboard.php");
    exit;
}

// Incluye tu conexión a la base de datos (ruta relativa a login.php)
require_once 'includes/db.php';

$errorMessage = ''; // Variable para almacenar mensajes de error

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    // Capturar información adicional para el log
    $ip_address = $_SERVER['REMOTE_ADDR'];
    $navegador = $_SERVER['HTTP_USER_AGENT'];

    // Verificar si el correo existe en la base de datos
    $stmt = $pdo->prepare("SELECT id_usuario, contrasena FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($contrasena, $usuario['contrasena'])) {
        // Inicio de sesión exitoso
        $_SESSION['usuario_id'] = $usuario['id_usuario'];

        // Registrar el log de ingreso exitoso
        $fecha_ingreso = date('Y-m-d H:i:s');
        $estado = 'exitoso';
        $stmt = $pdo->prepare("
            INSERT INTO logs_ingresos (id_usuario, correo, fecha_ingreso, ip_address, navegador, estado)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$usuario['id_usuario'], $correo, $fecha_ingreso, $ip_address, $navegador, $estado]);

        header("Location: /dashboard.php"); // Redirigir al dashboard
        exit;
    } else {
        // Inicio de sesión fallido
        $fecha_ingreso = date('Y-m-d H:i:s');
        $estado = 'fallido';

        // Registrar el log de ingreso fallido
        $id_usuario_log = $usuario ? $usuario['id_usuario'] : NULL;
        $stmt = $pdo->prepare("
            INSERT INTO logs_ingresos (id_usuario, correo, fecha_ingreso, ip_address, navegador, estado)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$id_usuario_log, $correo, $fecha_ingreso, $ip_address, $navegador, $estado]);

        $errorMessage = "Correo electrónico o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesión - Maka Store</title>

    <link href="/assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="/favicon.ico"> <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            background-color: #fff;
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2 class="text-center mb-4">Iniciar Sesión</h2>

        <?php if ($errorMessage): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="correo" class="form-label">Correo Electrónico:</label>
                <input type="email" class="form-control" id="correo" name="correo" placeholder="Ingresa tu correo electrónico" required>
            </div>
            <div class="mb-3">
                <label for="contrasena" class="form-label">Contraseña:</label>
                <input type="password" class="form-control" id="contrasena" name="contrasena" placeholder="Ingresa tu contraseña" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mb-3">Iniciar Sesión</button>
        </form>
        <div class="text-center">
            <a href="/views/recuperacion/recuperar_contrasena.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>

    <script src="/assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>