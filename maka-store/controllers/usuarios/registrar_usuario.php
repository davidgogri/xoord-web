<?php
session_start();
require_once '../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $correo = $_POST['correo'];
    $contrasena = password_hash($_POST['contrasena'], PASSWORD_BCRYPT); // Hashear la contraseña
    $rol = $_POST['rol'];
    $fecha_registro = date('Y-m-d H:i:s'); // Fecha actual
    $estado = 'activo'; // Estado predeterminado

    try {
        // Verificar si el correo ya está registrado
        $stmt = $pdo->prepare("SELECT id_usuario FROM usuarios WHERE correo = ?");
        $stmt->execute([$correo]);
        $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario_existente) {
            echo "El correo electrónico ya está registrado.";
            exit;
        }

        // Insertar el nuevo usuario
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, apellido, correo, contrasena, rol, fecha_registro, estado)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$nombre, $apellido, $correo, $contrasena, $rol, $fecha_registro, $estado]);

        // Redirigir al listado de usuarios
        header("Location: ../../views/usuarios/lista_usuarios.php");
        exit;

    } catch (PDOException $e) {
        echo "Error al registrar el usuario: " . $e->getMessage();
    }
}
?>