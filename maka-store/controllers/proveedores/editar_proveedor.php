<?php
require_once '../../../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id_proveedor'];
    $nombre_empresa = $_POST['nombre_empresa'];
    $contacto = $_POST['contacto'];
    $telefono = $_POST['telefono'];
    $email = $_POST['email'];
    $direccion = $_POST['direccion'];
    $productos_que_proveen = $_POST['productos_que_proveen'];

    try {
        $stmt = $pdo->prepare("
            UPDATE proveedores SET 
                nombre_empresa = ?, contacto = ?, telefono = ?, email = ?, 
                direccion = ?, productos_que_proveen = ?
            WHERE id_proveedor = ?
        ");
        $stmt->execute([
            $nombre_empresa, $contacto, $telefono, $email, $direccion, $productos_que_proveen, $id
        ]);

        header("Location: ../../proveedores/lista_proveedores.php");
        exit;
    } catch (PDOException $e) {
        die("Error al actualizar proveedor: " . $e->getMessage());
    }
}
?>