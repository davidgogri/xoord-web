<?php
// public_html/maka-store/includes/db.php

date_default_timezone_set('America/Bogota');

$host = 'localhost'; 
$db   = 'u144575388_makastore'; 
$user = 'u144575388_makastore'; 
$pass = 'j*ZHj0:N1$'; 

try {
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $options);

    $pdo->exec("SET time_zone = '-05:00'"); 
    
} catch (PDOException $e) {
    // EN PRODUCCIÓN, SOLO REGISTRAR EL ERROR Y MOSTRAR UN MENSAJE GENÉRICO.
    // NO expongas los detalles del error de la DB al usuario final.
    error_log("Error al conectar a la base de datos: " . $e->getMessage() . " (Código: " . $e->getCode() . ")");
    die("Error interno del servidor. Por favor, inténtalo más tarde."); 
    // Este die() solo se ejecuta si la conexión a la DB falla.
}

// SIN ETIQUETA DE CIERRE `?>`