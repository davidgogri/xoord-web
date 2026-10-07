<?php
function obtenerProductos($pdo) {
    $stmt = $pdo->query("SELECT * FROM productos");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerClientes($pdo) {
    $stmt = $pdo->query("SELECT * FROM clientes");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerProveedores($pdo) {
    $stmt = $pdo->query("SELECT * FROM proveedores");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function obtenerUsuarios($pdo) {
    $stmt = $pdo->query("SELECT * FROM usuarios");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>