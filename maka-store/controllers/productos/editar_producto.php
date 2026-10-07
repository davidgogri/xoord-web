<?php
require_once '../../../includes/db.php';

$id = $_POST['id_producto'];
$nombre = $_POST['nombre'];
$descripcion = $_POST['descripcion'];
$precio_compra = $_POST['precio_compra'];
$precio_venta = $_POST['precio_venta'];

$stmt = $pdo->prepare("UPDATE productos SET nombre=?, descripcion=?, precio_compra=?, precio_venta=? WHERE id_producto=?");
$stmt->execute([$nombre, $descripcion, $precio_compra, $precio_venta, $id]);

header("Location: ../../views/productos/lista_productos.php");