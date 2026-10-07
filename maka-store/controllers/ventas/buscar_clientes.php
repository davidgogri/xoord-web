<?php
// controllers/ventas/buscar_clientes.php

// Activar la visualización de errores para depuración (IMPRESCINDIBLE MIENTRAS EL ERROR PERSISTA)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el archivo de conexión a la base de datos
// Asegúrate de que esta ruta es correcta y consistente con tu estructura.
require_once __DIR__ . '/../../includes/db.php';

// Establecer el encabezado para indicar que la respuesta será JSON.
// Esto debe ser lo primero que se envía al navegador, sin espacios ni caracteres antes de <?php
header('Content-Type: application/json');

$clientes_response = []; // Usar un nombre de variable diferente para evitar conflictos
$query = $_GET['q'] ?? ''; // Obtener la consulta de búsqueda

// Validar la longitud mínima de la consulta
if (strlen($query) < 2) { // Cambiado a 2 caracteres para una búsqueda más ágil
    echo json_encode([]); // Devolver un array vacío si la consulta es muy corta
    exit; // Terminar la ejecución para evitar cualquier salida adicional
}

try {
    // Preparar la consulta SQL para buscar clientes por nombre o apellido
    $stmt = $pdo->prepare("
        SELECT
            id_cliente,
            nombre,
            apellido,
            CONCAT(nombre, ' ', apellido) AS label -- Añadimos el alias 'label' para autocompletado
        FROM
            clientes
        WHERE
            LOWER(nombre) LIKE :query_nombre OR LOWER(apellido) LIKE :query_apellido
        ORDER BY
            nombre ASC
        LIMIT 10
    ");


    // Enlazar los parámetros a la consulta SQL
    $param = '%' . $query . '%';
    $stmt->bindValue(':query_nombre', $param, PDO::PARAM_STR);
    $stmt->bindValue(':query_apellido', $param, PDO::PARAM_STR);

    // Ejecutar la consulta
    $stmt->execute();

    // Recoger los resultados directamente, ya que 'label' ya está en el resultado
    $clientes_response = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enviar la respuesta JSON y terminar
    echo json_encode($clientes_response);
    exit;

} catch (PDOException $e) {
    // Capturar y loguear errores de la base de datos
    error_log("PDO Error en buscar_clientes.php: " . $e->getMessage());
    // Enviar un mensaje de error JSON al frontend
    echo json_encode(['error' => 'Error de base de datos al buscar clientes: ' . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    // Capturar cualquier otra excepción inesperada
    error_log("Error inesperado en buscar_clientes.php: " . $e->getMessage());
    echo json_encode(['error' => 'Error inesperado al buscar clientes: ' . $e->getMessage()]);
    exit;
}
?>