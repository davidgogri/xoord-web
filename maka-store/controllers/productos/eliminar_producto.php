<?php
require_once '../../../includes/db.php';

$id = $_GET['id'];
$pdo->prepare("DELETE FROM productos WHERE id_producto = ?")->execute([$id]);

header("Location: ../../views/productos/lista_productos.php");