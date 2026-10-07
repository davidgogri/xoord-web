<?php
// views/ventas/venta_chat.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// Incluir la conexión a la base de datos y el header
require_once __DIR__ . '/../../includes/db.php';
include __DIR__ . '/../../partials/header.php';

// Obtener datos iniciales para usarlos en el JS
try {
    $stmt_pv = $pdo->query("SELECT id_punto_venta, nombre_punto FROM puntos_venta WHERE estado = 'activo' ORDER BY nombre_punto ASC");
    $puntos_venta = $stmt_pv->fetchAll(PDO::FETCH_ASSOC);

    $stmt_mp = $pdo->query("SELECT DISTINCT metodo_pago FROM ventas");
    $metodos_pago = $stmt_mp->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error al cargar datos en venta_chat.php: " . $e->getMessage());
    $puntos_venta = [];
    $metodos_pago = [];
}
?>

<div class="container mt-5">
    <div class="card shadow-sm">
        <div class="card-header bg-primary text-white text-center">
            <h5>Venta Rápida (Interfaz de Chat)</h5>
        </div>
        <div class="card-body" style="height: 60vh; overflow-y: auto; display: flex; flex-direction: column-reverse;" id="chat-messages">
            <div class="d-flex justify-content-end mb-2">
                <div class="p-2 bg-light rounded-3 shadow-sm" style="max-width: 70%;">¡Hola! Estoy aquí para ayudarte a registrar una nueva venta.</div>
            </div>
            <div class="d-flex justify-content-end mb-2">
                <div class="p-2 bg-light rounded-3 shadow-sm" style="max-width: 70%;">Empecemos. ¿Cuál es el nombre del cliente?</div>
            </div>
        </div>

        <div id="productos-resumen" class="mt-4 p-3" style="display:none; border-top: 1px solid #dee2e6;">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead class="table-primary">
                        <tr>
                            <th scope="col">Producto</th>
                            <th scope="col">Cantidad</th>
                            <th scope="col">Precio Unitario</th>
                            <th scope="col">Subtotal</th>
                            <th scope="col">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productos-table-body">
                        </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="text-end fw-bold">Total:</td>
                            <td id="total-venta" class="fw-bold">$0.00</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="card-footer bg-light">
            <div class="input-group">
                <input type="text" class="form-control" id="chat-input" placeholder="Escribe tu respuesta aquí...">
                <button class="btn btn-primary" type="button" id="chat-send">Enviar</button>
            </div>
        </div>
    </div>
</div>

<script>
    // Variables globales
    const chatMessages = document.getElementById('chat-messages');
    const chatInput = document.getElementById('chat-input');
    const chatSendBtn = document.getElementById('chat-send');
    const productosResumen = document.getElementById('productos-resumen');
    const productosTableBody = document.getElementById('productos-table-body');
    const totalVentaDisplay = document.getElementById('total-venta');
    
    let currentStep = 'cliente';
    const ventaData = {
        productos: [],
        searchResults: [], 
        currentProduct: null,
        availableColors: [],
        availableSizes: []
    };

    // Datos obtenidos del PHP
    const puntosVenta = <?php echo json_encode($puntos_venta); ?>;
    const metodosPago = <?php echo json_encode($metodos_pago); ?>;

    function addMessage(text, isUser = false) {
        const div = document.createElement('div');
        div.className = `d-flex ${isUser ? 'justify-content-end' : 'justify-content-start'} mb-2`;
        div.innerHTML = `<div class="p-2 rounded-3 shadow-sm" style="max-width: 70%; background-color: ${isUser ? '#d1e7dd' : '#f8f9fa'};">${text}</div>`;
        chatMessages.insertBefore(div, chatMessages.firstChild);
    }
    
    // Función para actualizar SOLO los totales de la tabla
    function updateTableTotals() {
        let total = 0;
        ventaData.productos.forEach(prod => {
            total += (prod.cantidad * prod.precio_venta);
        });
        totalVentaDisplay.textContent = `$${total.toFixed(2)}`;
    }

    // Esta función renderiza la tabla completa (útil al agregar/eliminar un producto)
    function renderProductosTable() {
        productosTableBody.innerHTML = '';
        
        if (ventaData.productos.length > 0) {
            productosResumen.style.display = 'block';
        } else {
            productosResumen.style.display = 'none';
        }

        ventaData.productos.forEach((prod, index) => {
            const subtotal = (prod.cantidad * prod.precio_venta);
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${prod.label} <br><small class="text-muted">${prod.nombre_color} / ${prod.nombre_talla}</small></td>
                <td>
                    <input type="number" class="form-control form-control-sm cantidad-input" min="1" max="${prod.max_cantidad}" value="${prod.cantidad}" data-index="${index}">
                </td>
                <td>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control form-control-sm precio-input" step="0.01" value="${prod.precio_venta}" data-index="${index}">
                    </div>
                </td>
                <td class="subtotal-cell">$${subtotal.toFixed(2)}</td>
                <td>
                    <button class="btn btn-danger btn-sm eliminar-producto-btn" data-index="${index}"><i class="fas fa-trash"></i></button>
                </td>
            `;
            productosTableBody.appendChild(row);
        });
        
        updateTableTotals(); // Llama a la nueva función para calcular el total
    }

    async function handleProductosStep(input) {
        const id_punto_venta = ventaData.id_punto_venta;

        if (!isNaN(input) && !isNaN(parseFloat(input))) {
            const selectionIndex = parseInt(input, 10) - 1;
            const selectedProduct = ventaData.searchResults[selectionIndex];
            if (selectedProduct) {
                handleProductSelection(selectedProduct);
            } else {
                addMessage('Selección no válida. Por favor, ingresa el número del producto.');
            }
        } else {
            addMessage(`Buscando "${input}"...`, true);
            try {
                const url = `../../controllers/ventas/buscar_productos.php?q=${encodeURIComponent(input)}&id_punto_venta=${id_punto_venta}`;
                const response = await fetch(url);
                const results = await response.json();
                
                if (results.error) {
                    addMessage(`Error: ${results.error}`);
                } else {
                    ventaData.searchResults = results;
                    let message = 'No se encontraron productos. Intenta con otro nombre.';
                    if (results.length > 0) {
                        message = 'Encontré estos productos. Por favor, selecciona uno por su número:';
                        results.forEach((prod, index) => {
                            message += `<br><strong>${index + 1}.</strong> ${prod.label} ($${parseFloat(prod.precio_venta).toFixed(2)})`;
                        });
                    }
                    addMessage(message);
                }
            } catch (error) {
                addMessage('Hubo un error al buscar productos.');
                console.error(error);
            }
        }
    }

    async function handleProductSelection(selectedProduct) {
        ventaData.currentProduct = selectedProduct;
        addMessage(`Seleccionaste: ${selectedProduct.label}.`);
        
        addMessage('Ahora, buscando colores disponibles...');
        
        try {
            const url = `../../controllers/ventas/buscar_colores_stock.php?id_producto=${selectedProduct.id_producto}&id_punto_venta=${ventaData.id_punto_venta}`;
            const response = await fetch(url);
            const results = await response.json();
            
            if (results.length > 0) {
                ventaData.availableColors = results;
                const colorOptions = results.map(c => `<span class="badge bg-secondary me-1 option-color" style="cursor:pointer;" data-id="${c.id_color}">${c.nombre_color}</span>`).join('');
                addMessage(`Selecciona el color del producto: ${colorOptions}`);
                currentStep = 'color_producto';
            } else {
                addMessage('No hay colores disponibles para este producto en este punto de venta.');
                currentStep = 'productos';
            }
        } catch (error) {
            addMessage('Hubo un error al buscar los colores.');
            console.error(error);
            currentStep = 'productos';
        }
    }

    async function handleColorSelection(id_color) {
        const selectedColor = ventaData.availableColors.find(c => c.id_color == id_color);
        if (!selectedColor) {
            addMessage('Selección de color no válida. Intenta de nuevo.');
            return;
        }

        ventaData.currentProduct.id_color = selectedColor.id_color;
        ventaData.currentProduct.nombre_color = selectedColor.nombre_color;
        addMessage(`Color seleccionado: ${selectedColor.nombre_color}`, true);

        addMessage('Ahora, buscando tallas disponibles...');

        try {
            const url = `../../controllers/ventas/buscar_tallas_stock.php?id_producto=${ventaData.currentProduct.id_producto}&id_punto_venta=${ventaData.id_punto_venta}&id_color=${id_color}`;
            const response = await fetch(url);
            const results = await response.json();
            
            if (results.length > 0) {
                ventaData.availableSizes = results;
                const tallaOptions = results.map(t => `<span class="badge bg-secondary me-1 option-talla" style="cursor:pointer;" data-id="${t.id_talla}" data-stock="${t.cantidad_stock}">${t.nombre_talla}</span>`).join('');
                addMessage(`Selecciona la talla del producto: ${tallaOptions}`);
                currentStep = 'talla_producto';
            } else {
                addMessage('No hay tallas disponibles para este color y producto.');
                currentStep = 'color_producto';
            }
        } catch (error) {
            addMessage('Hubo un error al buscar las tallas.');
            console.error(error);
            currentStep = 'color_producto';
        }
    }

    async function handleTallaSelection(id_talla) {
        const selectedTalla = ventaData.availableSizes.find(t => t.id_talla == id_talla);
        if (!selectedTalla) {
            addMessage('Selección de talla no válida. Intenta de nuevo.');
            return;
        }

        ventaData.currentProduct.id_talla = selectedTalla.id_talla;
        ventaData.currentProduct.nombre_talla = selectedTalla.nombre_talla;
        ventaData.currentProduct.max_cantidad = selectedTalla.cantidad_stock;
        addMessage(`Talla seleccionada: ${selectedTalla.nombre_talla}`, true);
        
        addMessage(`¿Qué cantidad deseas? (máximo: ${selectedTalla.cantidad_stock})`);
        currentStep = 'cantidad_producto';
    }


    // Función principal para manejar cada paso de la conversación
    async function handleStep(input) {
        if (currentStep !== 'productos' && currentStep !== 'cantidad_producto' && currentStep !== 'color_producto' && currentStep !== 'talla_producto' && currentStep !== 'confirmacion') {
            addMessage(input, true);
        }

        switch (currentStep) {
            case 'cliente':
                addMessage(`Buscando clientes que coincidan con "${input}"...`);
                try {
                    const url = `../../controllers/ventas/buscar_clientes.php?q=${encodeURIComponent(input)}`;
                    const response = await fetch(url);
                    const results = await response.json();
                    
                    if (results.length > 0) {
                        let message = 'Encontré estos clientes. Por favor, selecciona uno por su número:';
                        results.forEach((cliente, index) => {
                             message += `<br><strong>${index + 1}.</strong> <span class="client-option" style="cursor:pointer;" data-id="${cliente.id_cliente}">${cliente.nombre} ${cliente.apellido}</span>`;
                        });
                        ventaData.clienteResults = results;
                        addMessage(message);
                    } else {
                        addMessage('No se encontraron clientes con ese nombre.');
                        addMessage(`Puedes ingresar un nuevo nombre para crear un cliente nuevo o buscar de nuevo.`);
                    }
                } catch (error) {
                    addMessage('Hubo un error al buscar clientes.');
                    console.error(error);
                }
                break;
                
            case 'punto_venta':
                // Esta lógica se maneja con el evento de clic en los badges
                break;
                
            case 'productos':
                handleProductosStep(input);
                break;

            case 'cantidad_producto':
                const cantidad = parseInt(input, 10);
                if (isNaN(cantidad) || cantidad <= 0 || cantidad > ventaData.currentProduct.max_cantidad) {
                    addMessage(`Por favor, ingresa una cantidad válida (máximo: ${ventaData.currentProduct.max_cantidad}).`);
                    return;
                }
                ventaData.currentProduct.cantidad = cantidad;
                
                ventaData.productos.push(ventaData.currentProduct);
                ventaData.currentProduct = null;
                
                renderProductosTable();
                
                addMessage(`Se agregó el producto. ¿Deseas agregar otro? Responde "sí" o "no".`);
                currentStep = 'agregar_otro';
                break;

            case 'color_producto':
            case 'talla_producto':
                // Esta lógica se maneja con los manejadores de eventos de clic
                break;
                
            case 'agregar_otro':
                if (input.toLowerCase() === 'sí' || input.toLowerCase() === 'si') {
                    addMessage(`¡Genial! ¿Cuál es el nombre del siguiente producto?`);
                    currentStep = 'productos';
                } else if (input.toLowerCase() === 'no') {
                    addMessage(`Listo. Ahora, ¿cuál es el método de pago?`);
                    const mpOptions = metodosPago.map(mp => `<span class="badge bg-secondary me-1 mp-option" style="cursor:pointer;">${mp.metodo_pago}</span>`).join('');
                    addMessage(`Selecciona una de las opciones: ${mpOptions}`);
                    currentStep = 'metodo_pago';
                } else {
                    addMessage(`Respuesta no válida. Por favor, responde "sí" o "no".`);
                }
                break;

            case 'metodo_pago':
                // Esta lógica se maneja con el evento de clic en los badges
                break;

            case 'confirmacion':
                if (input.toLowerCase() === 'confirmar') {
                    addMessage('Confirmando venta...', true);
                    finalizarVenta();
                } else {
                    addMessage("Por favor, escribe 'confirmar' para finalizar la venta.");
                }
                break;
        }
    }

    // Funciones para finalizar la venta
    async function finalizarVenta() {
        const totalVenta = ventaData.productos.reduce((sum, prod) => sum + (prod.cantidad * prod.precio_venta), 0);
        
        const payload = {
            id_cliente: ventaData.id_cliente,
            id_punto_venta: ventaData.id_punto_venta,
            metodo_pago: ventaData.metodo_pago,
            total_venta: totalVenta,
            productos: ventaData.productos.map(p => ({
                id_producto: p.id_producto,
                cantidad: p.cantidad,
                id_color: p.id_color,
                id_talla: p.id_talla,
                precio_venta: p.precio_venta
            }))
        };

        try {
            const response = await fetch('../../controllers/ventas/registrar_venta_chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            const result = await response.json();

            if (result.success) {
                addMessage(`🎉 ¡Venta #${result.id_venta} registrada exitosamente!`);
                addMessage(`Puedes ver los detalles de la venta en el dashboard.`);
                chatInput.style.display = 'none';
                chatSendBtn.style.display = 'none';
                productosResumen.style.display = 'none';
            } else {
                addMessage(`❌ Error al registrar la venta: ${result.message}`);
                currentStep = 'confirmacion';
            }
        } catch (error) {
            addMessage(`❌ Hubo un error de conexión al intentar registrar la venta.`);
            console.error('Error:', error);
            currentStep = 'confirmacion';
        }
    }

    // Manejadores de eventos
    chatSendBtn.addEventListener('click', () => {
        const input = chatInput.value.trim();
        if (input) {
            handleStep(input);
            chatInput.value = '';
        }
    });

    chatInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            chatSendBtn.click();
        }
    });

    // Manejar clics en las opciones (badges)
    document.addEventListener('click', (e) => {
        if (e.target.classList.contains('client-option')) {
            const selectedId = e.target.dataset.id;
            const selectedName = e.target.textContent;
            
            ventaData.id_cliente = selectedId;
            ventaData.cliente_nombre = selectedName;
            addMessage(`Cliente seleccionado: ${selectedName}`, true);

            currentStep = 'punto_venta';
            const pvOptions = puntosVenta.map(pv => `<span class="badge bg-secondary me-1 pv-option" style="cursor:pointer;" data-id="${pv.id_punto_venta}">${pv.nombre_punto}</span>`).join('');
            addMessage(`Ahora, por favor, selecciona el punto de venta: ${pvOptions}`);
            
        } else if (e.target.classList.contains('pv-option')) {
            ventaData.id_punto_venta = e.target.dataset.id;
            const pvNombre = e.target.textContent;
            addMessage(`Punto de venta seleccionado: ${pvNombre}`, true);
            
            addMessage(`Excelente. ¿Cuál es el nombre del producto que quieres añadir a la venta?`);
            addMessage(`Puedes escribir el nombre y te daré opciones para que selecciones.`);
            currentStep = 'productos';
        } else if (e.target.classList.contains('option-color')) {
            handleColorSelection(e.target.dataset.id);
        } else if (e.target.classList.contains('option-talla')) {
            handleTallaSelection(e.target.dataset.id);
        } else if (e.target.classList.contains('mp-option')) {
            const metodoPago = e.target.textContent;
            ventaData.metodo_pago = metodoPago;
            addMessage(`Método de pago seleccionado: ${metodoPago}`, true);
            
            addMessage(`¡Venta lista para ser registrada! Presiona "Enviar" para finalizar.`, false);
            chatInput.placeholder = "Escribe 'confirmar' para finalizar...";
            currentStep = 'confirmacion';
        } else if (e.target.classList.contains('eliminar-producto-btn') || e.target.closest('.eliminar-producto-btn')) {
            const btn = e.target.closest('.eliminar-producto-btn');
            const index = btn.dataset.index;
            ventaData.productos.splice(index, 1);
            renderProductosTable();
        }
    });

    // CORRECCIÓN AQUÍ: Se cambió la lógica para no re-renderizar la tabla completa en cada caracter
    document.addEventListener('input', (e) => {
        if (e.target.classList.contains('cantidad-input')) {
            const index = e.target.dataset.index;
            const newCantidad = parseInt(e.target.value, 10);
            if (!isNaN(newCantidad) && newCantidad > 0 && newCantidad <= ventaData.productos[index].max_cantidad) {
                ventaData.productos[index].cantidad = newCantidad;
                const subtotalCell = e.target.closest('tr').querySelector('.subtotal-cell');
                subtotalCell.textContent = `$${(newCantidad * ventaData.productos[index].precio_venta).toFixed(2)}`;
                updateTableTotals();
            } else {
                e.target.value = ventaData.productos[index].cantidad; 
            }
        } else if (e.target.classList.contains('precio-input')) {
            const index = e.target.dataset.index;
            const newPrecio = parseFloat(e.target.value);
            if (!isNaN(newPrecio) && newPrecio >= 0) {
                ventaData.productos[index].precio_venta = newPrecio;
                const subtotalCell = e.target.closest('tr').querySelector('.subtotal-cell');
                subtotalCell.textContent = `$${(ventaData.productos[index].cantidad * newPrecio).toFixed(2)}`;
                updateTableTotals();
            } else {
                e.target.value = ventaData.productos[index].precio_venta;
            }
        }
    });
</script>

<?php include __DIR__ . '/../../partials/footer.php'; ?>