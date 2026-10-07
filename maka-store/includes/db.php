<?php

date_default_timezone_set('America/Bogota'); // Configurar zona horaria de Bogotá (Esto es PHP)

// Archivo db.php
$host = 'localhost'; // En Hostinger, 'localhost' es común para bases de datos internas
$db   = 'u144575388_makastore'; // TU NOMBRE DE BD REAL
$user = 'u144575388_makastore'; // TU USUARIO DE BD REAL
$pass = 'j*ZHj0:N1$'; // TU CONTRASEÑA DE BD REAL

try {
    // Crear la conexión a la base de datos
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4"; // Usar utf8mb4 para mejor compatibilidad de caracteres
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Establecer la zona horaria en MySQL para la conexión actual
    // Es crucial que esta zona horaria coincida con la de tu aplicación PHP y tus datos.
    // '-05:00' es UTC-5 para Bogotá
    
    $pdo->exec("SET time_zone = '-05:00'"); // Para America/Bogota (UTC-5)

    // Opcional: para depuración si no estás seguro de la hora en la DB
    // $stmt_tz = $pdo->query("SELECT NOW(), @@system_time_zone, @@global.time_zone, @@session.time_zone");
    // $tz_info = $stmt_tz->fetch(PDO::FETCH_ASSOC);
    // error_log("DEBUG DB Timezones: " . print_r($tz_info, true));
    
    error_log("DEBUG_DB: Conexión a la base de datos establecida exitosamente.");

} catch (PDOException $e) {
    // Si hay un error de conexión, detiene la ejecución y muestra el mensaje
    die("Error al conectar a la base de datos: " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
}

?>