<?php
// public_html/maka-store/controllers/VentasController.php
// ¡Este es un archivo NUEVO en tu proyecto!
// VALIDADO con la estructura de tablas proporcionada.

class VentasController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Maneja la interacción del chatbot.
     * Es el punto de entrada para los mensajes del usuario desde el frontend.
     * @param string $userMessage El mensaje enviado por el usuario.
     */
    public function handleChatbotInteraction($userMessage) {
        $sessionId = session_id(); // Usamos el ID de sesión para mantener el contexto de la conversación
        $response = ['bot_message' => 'Lo siento, no entendí eso. ¿Podrías repetirlo?', 'action' => 'continue'];

        // Inicializar o recuperar el estado de la conversación para esta sesión
        if (!isset($_SESSION['chatbot_state'][$sessionId])) {
            $_SESSION['chatbot_state'][$sessionId] = [
                'step' => 'initial', // Paso inicial de la conversación
                'current_sale' => [
                    'id_usuario' => $_SESSION['usuario_id'] ?? null, // Asumimos que el usuario está logueado
                    'fecha_venta' => date('Y-m-d H:i:s') // Usar formato de fecha y hora
                ],
                'products_added' => [] // Array para almacenar los productos que el usuario va añadiendo
            ];
            // Si es un nuevo inicio (o el primer mensaje), forzar el paso inicial
            if ($userMessage !== 'INICIAR_CONVERSACION') { // 'INICIAR_CONVERSACION' es el trigger del JS
                 $userMessage = 'INICIAR_CONVERSACION'; // Aseguramos que el flujo inicie correctamente
            }
        }
        $state = &$_SESSION['chatbot_state'][$sessionId]; // Referencia al estado actual

        try {
            switch ($state['step']) {
                case 'initial':
                    // Mensaje de bienvenida para iniciar la conversación
                    $response['bot_message'] = "¿Con qué cliente estamos haciendo esta venta? Puedes ingresar el ID o el nombre completo del cliente.";
                    $state['step'] = 'get_client';
                    break;

                case 'get_client':
                    if (empty($userMessage)) {
                        $response['bot_message'] = "Por favor, ingresa el ID o el nombre del cliente.";
                        break;
                    }
                    $client = $this->findClientByNameOrId($userMessage); // Llama a la función auxiliar
                    if ($client) {
                        $state['current_sale']['id_cliente'] = $client['id_cliente'];
                        $response['bot_message'] = "Perfecto, ¿es {$client['nombre']} {$client['apellido']}? (Sí/No)";
                        $state['step'] = 'confirm_client';
                    } else {
                        $response['bot_message'] = "No pude encontrar ese cliente. Por favor, intenta de nuevo con el ID o el nombre completo (Ej: Juan Perez).";
                    }
                    break;

                case 'confirm_client':
                    $userMessageLower = strtolower(trim($userMessage));
                    if ($userMessageLower === 'sí' || $userMessageLower === 'si') {
                        $response['bot_message'] = "¿Cuál es el punto de venta para esta transacción? Ingresa el nombre (Ej: Tienda Principal, Online).";
                        $state['step'] = 'get_punto_venta';
                    } else {
                        $response['bot_message'] = "Ok, por favor ingresa el cliente correcto.";
                        $state['step'] = 'get_client';
                    }
                    break;

                case 'get_punto_venta':
                    if (empty($userMessage)) {
                        $response['bot_message'] = "Por favor, ingresa el nombre del punto de venta.";
                        break;
                    }
                    $punto_venta = $this->findPuntoVentaByName($userMessage); // Llama a la función auxiliar
                    if ($punto_venta) {
                        $state['current_sale']['id_punto_venta'] = $punto_venta['id_punto_venta'];
                        $response['bot_message'] = "¿Qué producto deseas añadir? Ingresa el nombre o SKU.";
                        $state['step'] = 'add_product';
                    } else {
                        $response['bot_message'] = "Punto de venta no encontrado o inactivo. Intenta de nuevo. (Ej: Tienda Principal)";
                    }
                    break;

                case 'add_product':
                    if (empty($userMessage)) {
                        $response['bot_message'] = "Por favor, ingresa el nombre o SKU del producto.";
                        break;
                    }
                    $product = $this->findProductByNameOrSku($userMessage); // Llama a la función auxiliar
                    if ($product) {
                        $state['current_product_adding'] = $product; // Guardar el producto temporalmente
                        // Mostrar info adicional del producto
                        $response['bot_message'] = "Producto '{$product['nombre']}' (SKU: {$product['sku']}) encontrado, precio: $" . number_format($product['precio_venta'], 2) . ". ¿Qué cantidad, color y talla deseas? (Ej: 2, rojo, M)";
                        $state['step'] = 'get_product_details';
                    } else {
                        $response['bot_message'] = "Producto no encontrado. Intenta de nuevo con otro nombre o SKU.";
                    }
                    break;

                case 'get_product_details':
                    if (empty($userMessage)) {
                        $response['bot_message'] = "Por favor, ingresa la cantidad, color y talla (Ej: 2, rojo, M).";
                        break;
                    }
                    $details = $this->parseProductDetails($userMessage); // Función auxiliar para parsear
                    if ($details && $details['cantidad'] > 0) {
                        $color = $this->findColorByName($details['color']); // Llama a la función auxiliar
                        $talla = $this->findTallaByName($details['talla']); // Llama a la función auxiliar

                        if ($color && $talla) {
                            $id_producto = $state['current_product_adding']['id_producto'];
                            $id_color = $color['id_color'];
                            $id_talla = $talla['id_talla'];
                            $cantidad_solicitada = $details['cantidad'];
                            $id_punto_venta_actual = $state['current_sale']['id_punto_venta'];

                            // Validar stock
                            $stock_info = $this->getAvailableStock($id_producto, $id_color, $id_talla, $id_punto_venta_actual); // Llama a la función auxiliar

                            if ($stock_info !== false && $stock_info >= $cantidad_solicitada) {
                                // Añadir el producto al array de productos de la venta
                                $state['products_added'][] = [
                                    'id_producto' => $id_producto,
                                    'id_color' => $id_color,
                                    'id_talla' => $id_talla,
                                    'cantidad' => $cantidad_solicitada,
                                    'precio_venta' => $state['current_product_adding']['precio_venta'],
                                    'subtotal' => $cantidad_solicitada * $state['current_product_adding']['precio_venta']
                                ];
                                $response['bot_message'] = "Se agregaron {$cantidad_solicitada} de {$state['current_product_adding']['nombre']} ({$color['nombre_color']}, {$talla['nombre_talla']}). ¿Deseas añadir otro producto o finalizar la adición de productos?";
                                $state['step'] = 'add_another_product_or_finish';
                                unset($state['current_product_adding']); // Limpiar producto temporal
                            } else {
                                $response['bot_message'] = "Stock insuficiente para esa cantidad o combinación en el punto de venta '{$this->getPuntoVentaName($id_punto_venta_actual)}'. Disponible: " . ($stock_info ?? 0) . ". Intenta de nuevo.";
                            }
                        } else {
                            $response['bot_message'] = "Color ('{$details['color']}') o talla ('{$details['talla']}') no válidos. Intenta de nuevo. (Ej: 2, rojo, M)";
                        }
                    } else {
                        $response['bot_message'] = "Formato incorrecto o cantidad inválida. Por favor, ingresa cantidad, color y talla separados por comas. (Ej: 2, rojo, M)";
                    }
                    break;

                case 'add_another_product_or_finish':
                    $userMessageLower = strtolower(trim($userMessage));
                    if (in_array($userMessageLower, ['finalizar', 'finalizar venta', 'no'])) {
                        if (empty($state['products_added'])) {
                            $response['bot_message'] = "Debes añadir al menos un producto para finalizar la venta. ¿Qué producto deseas añadir?";
                            $state['step'] = 'add_product';
                        } else {
                            $response['bot_message'] = "¿Cuál es el método de pago? (Efectivo, Tarjeta de Crédito, Tarjeta de Débito, Transferencia Bancaria, Otros)";
                            $state['step'] = 'get_payment_method';
                        }
                    } else if (in_array($userMessageLower, ['si', 'sí', 'otro producto'])) {
                        $response['bot_message'] = "¿Qué otro producto deseas añadir? Ingresa el nombre o SKU.";
                        $state['step'] = 'add_product';
                    } else {
                        $response['bot_message'] = "No entiendo. ¿Deseas 'añadir otro producto' o 'finalizar'?";
                    }
                    break;

                case 'get_payment_method':
                    $validMethods = ['efectivo', 'tarjeta de credito', 'tarjeta de debito', 'transferencia bancaria', 'otros'];
                    $userMessageLower = strtolower(trim($userMessage));
                    if (in_array($userMessageLower, $validMethods)) {
                        $state['current_sale']['metodo_pago'] = ucfirst($userMessageLower); // Almacenar el método de pago
                        $response['bot_message'] = "¿Hay alguna observación adicional para esta venta? (Di 'no' si no hay)";
                        $state['step'] = 'get_observations';
                    } else {
                        $response['bot_message'] = "Método de pago no válido. Por favor, elige entre: Efectivo, Tarjeta de Crédito, Tarjeta de Débito, Transferencia Bancaria, Otros.";
                    }
                    break;

                case 'get_observations':
                    $state['current_sale']['observaciones'] = (strtolower(trim($userMessage)) === 'no') ? '' : $userMessage;

                    // Calcular el total de la venta
                    $total_venta = 0;
                    foreach ($state['products_added'] as $item) {
                        $total_venta += $item['subtotal'];
                    }
                    $state['current_sale']['total_venta'] = $total_venta;

                    // Preparar el resumen
                    $summary = "Resumen de la Venta:\n";
                    $clientName = $this->getClientName($state['current_sale']['id_cliente']) ?: 'Desconocido';
                    $puntoVentaName = $this->getPuntoVentaName($state['current_sale']['id_punto_venta']) ?: 'Desconocido';

                    $summary .= "Cliente: " . $clientName . "\n";
                    $summary .= "Punto de Venta: " . $puntoVentaName . "\n";
                    $summary .= "Productos:\n";
                    foreach ($state['products_added'] as $p) {
                        $product_name = $this->getProductName($p['id_producto']) ?: 'Producto Desconocido';
                        $color_name = $this->getColorName($p['id_color']) ?: 'Color Desconocido';
                        $talla_name = $this->getTallaName($p['id_talla']) ?: 'Talla Desconocida';
                        $summary .= "- {$p['cantidad']}x {$product_name} ({$color_name}, {$talla_name}) @ $" . number_format($p['precio_venta'], 2) . "\n";
                    }
                    $summary .= "Total: $" . number_format($total_venta, 2) . "\n";
                    $summary .= "Método de Pago: {$state['current_sale']['metodo_pago']}\n";
                    $summary .= "Observaciones: " . ($state['current_sale']['observaciones'] ?: 'Ninguna') . "\n\n";
                    $response['bot_message'] = $summary . "¿Confirmas esta venta? (Sí/No)";
                    $state['step'] = 'confirm_sale';
                    break;

                case 'confirm_sale':
                    $userMessageLower = strtolower(trim($userMessage));
                    if ($userMessageLower === 'sí' || $userMessageLower === 'si') {
                        // Intentar registrar la venta
                        $ventaRegistrada = $this->registrarVentaProgrammatically($state['current_sale'], $state['products_added']);

                        if ($ventaRegistrada) {
                            $response['bot_message'] = "¡Venta registrada con éxito! ID: {$ventaRegistrada['id_venta']}. ¿Deseas realizar otra venta? (Sí/No)";
                            $response['action'] = 'end'; // Señal para que el frontend pueda ofrecer reiniciar
                        } else {
                            // Si el método devuelve false (no lanza excepción), significa que hubo un problema
                            $response['bot_message'] = "Ocurrió un error al registrar la venta. Por favor, revisa los datos e intenta de nuevo.";
                            $response['action'] = 'error'; // Podría ser un error recuperable o de datos
                        }
                    } else {
                        $response['bot_message'] = "Venta cancelada. ¿Deseas iniciar otra venta? (Sí/No)";
                        $response['action'] = 'end'; // No se registró, pero la conversación de esta venta terminó
                    }
                    break;

                default:
                    // Si llegamos a un estado desconocido, reiniciamos la conversación
                    $response['bot_message'] = "Lo siento, hubo un error o la conversación se reinició. ¿Deseas iniciar una nueva venta? (Sí/No)";
                    $response['action'] = 'restart';
                    unset($_SESSION['chatbot_state'][$sessionId]); // Limpiar el estado
                    break;
            }
        } catch (Exception $e) {
            // Manejo de excepciones (ej. stock insuficiente, error de DB)
            error_log("Error en el chatbot de ventas (" . $state['step'] . "): " . $e->getMessage());
            $response['bot_message'] = "Ha ocurrido un error inesperado: " . $e->getMessage() . ". La conversación se reiniciará. Por favor, disculpa las molestias.";
            $response['action'] = 'error';
            unset($_SESSION['chatbot_state'][$sessionId]); // Limpiar el estado de la conversación
        }

        // Si la acción es 'end', 'restart' o 'error', limpia el estado para la próxima interacción
        // EXCEPTUANDO el caso de 'confirm_sale' cuando el usuario dice 'no', para que pregunte de nuevo
        if ($response['action'] === 'end' || $response['action'] === 'restart' || $response['action'] === 'error' ||
            ($state['step'] === 'confirm_sale' && (strtolower(trim($userMessage)) === 'no' || strtolower(trim($userMessage)) === 'no')) )
        {
            unset($_SESSION['chatbot_state'][$sessionId]);
        }

        // Enviar la respuesta como JSON
        echo json_encode($response);
        exit; // Terminar la ejecución aquí
    }

    /**
     * Función auxiliar para parsear los detalles del producto (cantidad, color, talla) de un mensaje de texto.
     * Ejemplo: "2, rojo, M"
     * @param string $message
     * @return array|false Con los detalles o false si el formato es incorrecto.
     */
    private function parseProductDetails($message) {
        $parts = explode(',', $message);
        if (count($parts) === 3) {
            return [
                'cantidad' => (int) trim($parts[0]),
                'color' => trim($parts[1]),
                'talla' => trim($parts[2])
            ];
        }
        return false;
    }

    /**
     * Registra la venta y sus detalles, y actualiza el inventario.
     * Esta es la lógica central de registro de venta, similar a lo que tendrías en registrar_venta.php
     * @param array $saleData Datos de la venta principal (id_cliente, id_usuario, id_punto_venta, etc.).
     * @param array $productDetails Array de productos y sus detalles (id_producto, id_color, id_talla, cantidad, precio_venta, subtotal).
     * @return array|false Un array con 'id_venta' si tiene éxito, o false si falla (idealmente, lanza excepciones).
     * @throws Exception Si hay un error de stock o base de datos que deba detener la transacción.
     */
    private function registrarVentaProgrammatically($saleData, $productDetails) {
        $this->pdo->beginTransaction();
        try {
            // 1. Verificar stock para todos los productos con bloqueo pesimista
            $stmt_check_stock = $this->pdo->prepare("
                SELECT cantidad_stock
                FROM inventario
                WHERE id_producto = :id_producto
                  AND id_color = :id_color
                  AND id_talla = :id_talla
                  AND id_punto_venta = :id_punto_venta
                FOR UPDATE
            ");

            foreach ($productDetails as $item) {
                $stmt_check_stock->bindParam(':id_producto', $item['id_producto'], PDO::PARAM_INT);
                $stmt_check_stock->bindParam(':id_color', $item['id_color'], PDO::PARAM_INT);
                $stmt_check_stock->bindParam(':id_talla', $item['id_talla'], PDO::PARAM_INT);
                $stmt_check_stock->bindParam(':id_punto_venta', $saleData['id_punto_venta'], PDO::PARAM_INT);
                $stmt_check_stock->execute();
                $stock_row = $stmt_check_stock->fetch(PDO::FETCH_ASSOC);

                if (!$stock_row || $stock_row['cantidad_stock'] < $item['cantidad']) {
                    $product_name = $this->getProductName($item['id_producto']) ?: 'Producto Desconocido';
                    $color_name = $this->getColorName($item['id_color']) ?: 'Color Desconocido';
                    $talla_name = $this->getTallaName($item['id_talla']) ?: 'Talla Desconocida';
                    throw new Exception("Stock insuficiente para {$product_name} ({$color_name}, {$talla_name}). Disponible: " . ($stock_row ? $stock_row['cantidad_stock'] : 0) . ", Solicitado: " . $item['cantidad']);
                }
            }

            // 2. Insertar la venta principal
            $stmt_venta = $this->pdo->prepare("
                INSERT INTO ventas (id_cliente, id_usuario, id_punto_venta, fecha_venta, total_venta, metodo_pago, observaciones)
                VALUES (:id_cliente, :id_usuario, :id_punto_venta, :fecha_venta, :total_venta, :metodo_pago, :observaciones)
            ");
            $stmt_venta->bindParam(':id_cliente', $saleData['id_cliente'], PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_usuario', $saleData['id_usuario'], PDO::PARAM_INT);
            $stmt_venta->bindParam(':id_punto_venta', $saleData['id_punto_venta'], PDO::PARAM_INT);
            $stmt_venta->bindParam(':fecha_venta', $saleData['fecha_venta'], PDO::PARAM_STR);
            $stmt_venta->bindParam(':total_venta', $saleData['total_venta']);
            $stmt_venta->bindParam(':metodo_pago', $saleData['metodo_pago'], PDO::PARAM_STR);
            $stmt_venta->bindParam(':observaciones', $saleData['observaciones'], PDO::PARAM_STR);
            $stmt_venta->execute();
            $id_venta = $this->pdo->lastInsertId();

            // 3. Insertar detalles de venta y actualizar inventario
            $stmt_detalle = $this->pdo->prepare("
                INSERT INTO detalles_venta (id_venta, id_producto, id_color, id_talla, cantidad, precio_venta, subtotal)
                VALUES (:id_venta, :id_producto, :id_color, :id_talla, :cantidad, :precio_venta, :subtotal)
            ");
            $stmt_update_stock = $this->pdo->prepare("
                UPDATE inventario
                SET cantidad_stock = cantidad_stock - :cantidad_vendida
                WHERE id_producto = :id_producto
                  AND id_color = :id_color
                  AND id_talla = :id_talla
                  AND id_punto_venta = :id_punto_venta
            ");

            foreach ($productDetails as $item) {
                $stmt_detalle->bindParam(':id_venta', $id_venta, PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_producto', $item['id_producto'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_color', $item['id_color'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':id_talla', $item['id_talla'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':cantidad', $item['cantidad'], PDO::PARAM_INT);
                $stmt_detalle->bindParam(':precio_venta', $item['precio_venta']);
                $stmt_detalle->bindParam(':subtotal', $item['subtotal']);
                $stmt_detalle->execute();

                $stmt_update_stock->bindParam(':cantidad_vendida', $item['cantidad'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_producto', $item['id_producto'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_color', $item['id_color'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_talla', $item['id_talla'], PDO::PARAM_INT);
                $stmt_update_stock->bindParam(':id_punto_venta', $saleData['id_punto_venta'], PDO::PARAM_INT);
                $stmt_update_stock->execute();
            }

            $this->pdo->commit();
            return ['id_venta' => $id_venta];
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Error al registrar venta programáticamente: " . $e->getMessage());
            throw $e;
        }
    }

    // --- Funciones de Acceso a Datos (Reemplazan a los Modelos) ---
    // Estas funciones acceden directamente a la base de datos usando $this->pdo.

    private function findClientByNameOrId($query) {
        // Intenta buscar por ID
        if (is_numeric($query)) {
            $stmt = $this->pdo->prepare("SELECT id_cliente, nombre, apellido FROM clientes WHERE id_cliente = :id");
            $stmt->bindParam(':id', $query, PDO::PARAM_INT);
            $stmt->execute();
            $client = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($client) return $client;
        }

        // Si no se encontró por ID o no era numérico, busca por nombre/apellido
        // Usamos CONCAT para buscar en nombre y apellido a la vez
        $search = '%' . $query . '%';
        $stmt = $this->pdo->prepare("SELECT id_cliente, nombre, apellido FROM clientes WHERE CONCAT(nombre, ' ', apellido) LIKE :search OR nombre LIKE :search OR apellido LIKE :search LIMIT 1");
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getClientName($id_cliente) {
        $stmt = $this->pdo->prepare("SELECT nombre, apellido FROM clientes WHERE id_cliente = :id_cliente");
        $stmt->bindParam(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->execute();
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        return $client ? $client['nombre'] . ' ' . $client['apellido'] : null;
    }

    private function findProductByNameOrSku($query) {
        $search = '%' . $query . '%';
        $stmt = $this->pdo->prepare("SELECT id_producto, nombre, sku, precio_venta FROM productos WHERE nombre LIKE :search OR sku LIKE :search LIMIT 1");
        $stmt->bindParam(':search', $search, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getProductName($id_producto) {
        $stmt = $this->pdo->prepare("SELECT nombre FROM productos WHERE id_producto = :id_producto");
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
        return $product ? $product['nombre'] : null;
    }

    private function findColorByName($name) {
        $stmt = $this->pdo->prepare("SELECT id_color, nombre_color FROM colores WHERE nombre_color = :name LIMIT 1");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getColorName($id_color) {
        $stmt = $this->pdo->prepare("SELECT nombre_color FROM colores WHERE id_color = :id_color");
        $stmt->bindParam(':id_color', $id_color, PDO::PARAM_INT);
        $stmt->execute();
        $color = $stmt->fetch(PDO::FETCH_ASSOC);
        return $color ? $color['nombre_color'] : null;
    }

    private function findTallaByName($name) {
        $stmt = $this->pdo->prepare("SELECT id_talla, nombre_talla FROM tallas WHERE nombre_talla = :name LIMIT 1");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getTallaName($id_talla) {
        $stmt = $this->pdo->prepare("SELECT nombre_talla FROM tallas WHERE id_talla = :id_talla");
        $stmt->bindParam(':id_talla', $id_talla, PDO::PARAM_INT);
        $stmt->execute();
        $talla = $stmt->fetch(PDO::FETCH_ASSOC);
        return $talla ? $talla['nombre_talla'] : null;
    }

    private function findPuntoVentaByName($name) {
        // CORREGIDO: Usar 'nombre_punto' en lugar de 'nombre_punto_venta'
        $stmt = $this->pdo->prepare("SELECT id_punto_venta, nombre_punto FROM puntos_venta WHERE nombre_punto = :name AND estado = 'activo' LIMIT 1");
        $stmt->bindParam(':name', $name, PDO::PARAM_STR);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getPuntoVentaName($id_punto_venta) {
        // CORREGIDO: Usar 'nombre_punto' en lugar de 'nombre_punto_venta'
        $stmt = $this->pdo->prepare("SELECT nombre_punto FROM puntos_venta WHERE id_punto_venta = :id_punto_venta");
        $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
        $stmt->execute();
        $pv = $stmt->fetch(PDO::FETCH_ASSOC);
        return $pv ? $pv['nombre_punto'] : null;
    }

    private function getAvailableStock($id_producto, $id_color, $id_talla, $id_punto_venta) {
        $stmt = $this->pdo->prepare("
            SELECT cantidad_stock
            FROM inventario
            WHERE id_producto = :id_producto
              AND id_color = :id_color
              AND id_talla = :id_talla
              AND id_punto_venta = :id_punto_venta
            LIMIT 1
        ");
        $stmt->bindParam(':id_producto', $id_producto, PDO::PARAM_INT);
        $stmt->bindParam(':id_color', $id_color, PDO::PARAM_INT);
        $stmt->bindParam(':id_talla', $id_talla, PDO::PARAM_INT);
        $stmt->bindParam(':id_punto_venta', $id_punto_venta, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? (int)$result['cantidad_stock'] : 0;
    }

    // Puedes añadir otros métodos aquí si VentasController maneja otras lógicas no relacionadas con el chatbot.
}