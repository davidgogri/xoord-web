<?php
// views/cambios/cambios.php

// 1. Inicialización, Conexión y Seguridad (Patrón de nueva_venta.txt)
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

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Inclusión del header (asumiendo que contiene la conexión a BD $pdo)
include __DIR__ . '/../../partials/header.php'; 

// 2. Lógica para obtener datos iniciales de la base de datos

// Obtener Tallas
$tallas = [];
try {
    // Asumiendo que $pdo está disponible por la inclusión del header
    $stmt_tallas = $pdo->query("SELECT id_talla, nombre_talla FROM tallas ORDER BY nombre_talla ASC");
    $tallas = $stmt_tallas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de error si es necesario
    error_log("Error al cargar tallas en cambios.php: " . $e->getMessage());
}

// Obtener Puntos de Venta
$puntos_venta = [];
try {
    $stmt_pv = $pdo->query("SELECT id_punto_venta, nombre_punto FROM puntos_venta WHERE estado = 'activo' ORDER BY nombre_punto ASC");
    $puntos_venta = $stmt_pv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejo de error si es necesario
    error_log("Error al cargar puntos de venta en cambios.php: " . $e->getMessage());
}

?>

<div class="container mt-4">
    <h1 class="mb-4 text-primary text-center"><i class="fas fa-exchange-alt me-2"></i>Gestión de Cambios de Mercancía</h1>

    <?php
    // Mostrar mensajes de sesión (éxito o error)
    if (isset($_SESSION['message'])):
        $alert_class = ($_SESSION['message_type'] ?? 'info') === 'success' ? 'alert-success' : 'alert-danger';
    ?>
    <div class="alert <?= $alert_class ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($_SESSION['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    endif;
    ?>

    <form action="../../controllers/cambios/gestionar_cambio.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-search me-2"></i>1. Buscar Venta/Compra Original</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="tipoOrigen" class="form-label">Tipo de Transacción:</label>
                        <select class="form-select" id="tipoOrigen" name="tipo_origen" required>
                            <option value="">Seleccione</option>
                            <option value="venta">Venta</option>
                            <option value="compra">Compra (Cambio con Proveedor)</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="idOrigen" class="form-label">ID de Venta/Compra:</label>
                        <input type="number" class="form-control" id="idOrigen" name="id_origen" placeholder="Ej: 1234" required>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" id="btnBuscarOrigen" class="btn btn-info w-100"><i class="fas fa-search me-2"></i>Buscar</button>
                    </div>
                </div>
                <div id="resultadoOrigen" class="mt-3"></div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm" id="detallesCambioCard" style="display: none;">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>2. Especificar el Cambio</h5>
            </div>
            <div class="card-body">
                <input type="hidden" id="idProductoSale" name="id_producto_sale" required>
                <input type="hidden" id="idColorSale" name="id_color_sale" required>
                <input type="hidden" id="idTallaSale" name="id_talla_sale" required>
                
                <p class="fs-5">Producto Original a Cambiar (Sale del detalle original): <span id="productoOriginalDisplay" class="fw-bold text-danger"></span></p>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="id_punto_venta_cambio" class="form-label"><i class="fas fa-store me-2"></i>Punto de Venta del Cambio:</label>
                        <select class="form-select" id="id_punto_venta_cambio" name="id_punto_venta" required>
                            <option value="">Selecciona un punto de venta</option>
                            <?php foreach ($puntos_venta as $pv): ?>
                                <option value="<?php echo htmlspecialchars($pv['id_punto_venta']); ?>">
                                    <?php echo htmlspecialchars($pv['nombre_punto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="cantidadSale" class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad a Devolver/Sacar:</label>
                        <input type="number" class="form-control" id="cantidadSale" name="cantidad_sale" min="1" required>
                        <small class="text-danger" id="maxCantidadMensaje">Debe ser menor o igual a la cantidad original.</small>
                    </div>
                </div>
                <hr>
                
                <h6 class="mt-4"><i class="fas fa-arrow-right me-2"></i>Producto Nuevo a Entregar/Recibir:</h6>
                <div class="row g-3 producto-item-entra producto-item" id="productoEntraContainer">
                    <div class="col-md-3">
                        <label class="form-label">Producto:</label>
                        <input type="text" class="form-control producto-input-entra producto-input" placeholder="Buscar producto..." autocomplete="off" required>
                        <input type="hidden" name="id_producto_entra" class="productoId-entra productoId" required>
                        <div class="resultadosProductos-entra resultadosProductos list-group position-absolute w-25 z-index-1000"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color:</label>
                        <input type="text" class="form-control color-input-entra color-input" placeholder="Buscar color..." autocomplete="off" required>
                        <input type="hidden" name="id_color_entra" class="colorId-entra colorId" required>
                        <div class="resultadosColores-entra resultadosColores list-group position-absolute w-25 z-index-1000"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Talla:</label>
                        <select name="id_talla_entra" class="form-select talla-select-entra talla-select" required>
                            <option value="">Selecciona talla</option>
                            <?php foreach ($tallas as $talla): ?>
                                <option value="<?php echo htmlspecialchars($talla['id_talla']); ?>">
                                    <?php echo htmlspecialchars($talla['nombre_talla']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="cantidadEntra" class="form-label">Cantidad a Entregar/Recibir:</label>
                        <input type="number" class="form-control cantidad-input-entra cantidad-input" id="cantidadEntra" name="cantidad_entra" min="1" required>
                        <small class="text-muted stock-disponible-mensaje mt-1" style="display: none;"></small>
                    </div>
                </div>
                <div class="col-12 mt-3">
                    <label for="observaciones" class="form-label"><i class="fas fa-comments me-2"></i>Observaciones del Cambio:</label>
                    <textarea class="form-control" id="observaciones" name="observaciones" rows="3" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-success btn-lg w-100 mt-4" id="btnRegistrarCambio"><i class="fas fa-check-circle me-2"></i>Registrar Cambio</button>
            </div>
        </div>
    </form>
</div>

<?php 
include __DIR__ . '/../../partials/footer.php'; 
?>

<script>
    // =================================================================================================
    // FUNCIONES REQUERIDAS DE nueva_venta.txt (Ajustadas para el entorno de cambios.php)
    // =================================================================================================
    
    let stockDisponible = {}; // Variable global para almacenar el stock cargado por punto de venta

    // --- Función Genérica para Autocompletado (CORREGIDA) ---
    function initAutocomplete(inputElement, resultsContainer, searchUrl, hiddenIdElement) {
        let currentRequest = null;
        let selectedItemIndex = -1;

        inputElement.addEventListener('input', function() {
            const query = this.value.trim();
            resultsContainer.innerHTML = '';
            resultsContainer.style.display = 'none';
            hiddenIdElement.value = '';
            selectedItemIndex = -1;

            if (query.length < 2) {
                return;
            }

            if (currentRequest) {
                currentRequest.abort();
            }

            currentRequest = new XMLHttpRequest();
            
            // 1. Iniciar con la URL base pasada como argumento
            let finalSearchUrl = searchUrl + '?q=' + encodeURIComponent(query);
            
            // 2. CORRECCIÓN: Añadir id_punto_venta solo si es el campo de producto
            if (inputElement.classList.contains('producto-input')) {
                const id_punto_venta = document.getElementById('id_punto_venta_cambio').value; // USAR ID DEL CAMBIO
                if (!id_punto_venta) {
                    resultsContainer.innerHTML = '<div class="list-group-item list-group-item-warning">Seleccione un Punto de Venta primero.</div>';
                    resultsContainer.style.display = 'block';
                    return;
                }
                finalSearchUrl += '&id_punto_venta=' + encodeURIComponent(id_punto_venta); 
            } 
            
            currentRequest.open('GET', finalSearchUrl);
            
            currentRequest.onload = function() {
                if (currentRequest.status === 200) {
                    try {
                        const data = JSON.parse(currentRequest.responseText);
                        if (data.error) {
                            console.error('Error del servidor:', data.error);
                            resultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error: ${data.error}</div>`;
                            resultsContainer.style.display = 'block';
                            return;
                        }

                        if (data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.classList.add('list-group-item', 'list-group-item-action');
                                
                                // Asignar el texto a mostrar: prioriza nombre_color, luego nombre, luego descripcion
                                let displayText = item.nombre_color || item.nombre || item.descripcion || ''; 
                                // Asignar ID: prioriza id_producto, luego id_color
                                let selectedId = item.id_producto || item.id_color || ''; 

                                div.textContent = displayText;
                                div.dataset.id = selectedId;
                                div.dataset.item = JSON.stringify(item);
                                
                                div.addEventListener('click', function() {
                                    inputElement.value = this.textContent;
                                    hiddenIdElement.value = this.dataset.id;
                                    resultsContainer.innerHTML = '';
                                    resultsContainer.style.display = 'none';

                                    // Si es un producto o color, dispara validación de stock
                                    const productoDiv = inputElement.closest('.producto-item');
                                    if (productoDiv && typeof validarCantidadProducto === 'function') {
                                        validarCantidadProducto(productoDiv);
                                    }
                                });
                                resultsContainer.appendChild(div);
                            });
                            resultsContainer.style.display = 'block';
                            selectedItemIndex = -1;
                        } else {
                            resultsContainer.innerHTML = '<div class="list-group-item">No se encontraron resultados.</div>';
                            resultsContainer.style.display = 'block';
                        }
                    } catch (e) {
                        console.error('Error al parsear datos JSON: ', e, 'Respuesta del servidor:', currentRequest.responseText.substring(0, 100) + '...');
                        resultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error al procesar datos: ${e.message}</div>`;
                        resultsContainer.style.display = 'block';
                    }
                } else {
                    console.error('Error en la solicitud AJAX:', currentRequest.status, currentRequest.statusText);
                    resultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error al cargar resultados. Código: ${currentRequest.status}</div>`;
                    resultsContainer.style.display = 'block';
                }
            };
            currentRequest.onerror = function() {
                console.error('Error de red o CORS al cargar datos.');
                resultsContainer.innerHTML = '<div class="list-group-item list-group-item-danger">Error de red al cargar resultados.</div>';
                resultsContainer.style.display = 'block';
            };
            currentRequest.send();
        });
        // ... (Se omite el manejo de teclado para brevedad, pero debe estar en su archivo)
    }

    // --- Función para cargar stock (Copia de nueva_venta.txt) ---
    function cargarStockPorPuntoVenta(id_punto_venta) {
        if (!id_punto_venta) {
            stockDisponible = {};
            console.log('No hay Punto de Venta seleccionado, stock vacío.');
            document.querySelectorAll('.stock-disponible-mensaje').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            document.querySelectorAll('.cantidad-input').forEach(input => {
                input.readOnly = true;
            });
            return;
        }

        fetch(`../../controllers/ventas/get_stock_by_punto_venta.php?id_punto_venta=${id_punto_venta}`)
            .then(response => {
                if (!response.ok) {
                    return response.text().then(text => { 
                        throw new Error('Error HTTP: ' + response.status + ' ' + response.statusText + ' - ' + (text.substring(0, 100) + (text.length > 100 ? '...' : '')));
                    });
                }
                return response.json(); 
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                stockDisponible = data;
                console.log('Stock cargado para PV ' + id_punto_venta + ':', stockDisponible);
                // Actualizar validación de cantidad para el único producto de reemplazo
                const productoDiv = document.getElementById('productoEntraContainer');
                if (productoDiv) {
                    validarCantidadProducto(productoDiv);
                }
            })
            .catch(error => {
                console.error('Error cargando stock:', error.message);
                alert('No se pudo cargar el stock para el punto de venta seleccionado: ' + error.message);
                stockDisponible = {};
                document.querySelectorAll('.stock-disponible-mensaje').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    input.readOnly = true;
                });
            });
    }

    // --- Función para validar la cantidad y mostrar stock (Copia de nueva_venta.txt) ---
    function validarCantidadProducto(productoDiv) {
        const tipoOrigen = document.getElementById('tipoOrigen').value;
        const cantidadInput = productoDiv.querySelector('.cantidad-input');
        const stockMensaje = productoDiv.querySelector('.stock-disponible-mensaje');

        // Si el origen es una COMPRA, el producto ENTRA a stock (+), NO necesitamos validar stock.
        if (tipoOrigen === 'compra') {
            stockMensaje.style.display = 'none';
            cantidadInput.readOnly = false;
            return;
        }

        // --- Lógica de Validación si es VENTA (el producto ENTRA SALE de stock) ---
        const productoId = productoDiv.querySelector('.productoId').value;
        const colorId = productoDiv.querySelector('.colorId').value;
        const tallaId = productoDiv.querySelector('.talla-select').value;
        
        if (!productoId || !colorId || !tallaId) {
            stockMensaje.style.display = 'none';
            cantidadInput.readOnly = true;
            cantidadInput.value = '';
            return;
        }

        const stockKey = `${productoId}-${colorId}-${tallaId}`;
        const stock = stockDisponible[stockKey];

        if (typeof stock === 'number' && stock >= 0) { 
            stockMensaje.textContent = `Stock disponible: ${stock}`;
            stockMensaje.style.display = 'block';
            cantidadInput.max = stock;
            cantidadInput.readOnly = false;

            let cantidad = parseInt(cantidadInput.value);
            if (isNaN(cantidad) || cantidad <= 0) {
                if (!isNaN(cantidad) && cantidad <= 0) {
                     cantidadInput.value = '';
                }
            } else if (cantidad > stock) {
                cantidadInput.value = stock;
            }

            if (stock === 0) {
                cantidadInput.value = '';
                cantidadInput.readOnly = true;
                stockMensaje.textContent = 'Stock agotado.';
            }

        } else {
            stockMensaje.textContent = 'Stock no disponible para esta combinación.';
            stockMensaje.style.display = 'block';
            cantidadInput.max = '';
            cantidadInput.value = '';
            cantidadInput.readOnly = true;
        }
    }


    // =================================================================================================
    // LÓGICA ESPECÍFICA DEL FORMULARIO DE CAMBIOS
    // =================================================================================================

    document.addEventListener('DOMContentLoaded', function () {

        const btnBuscarOrigen = document.getElementById('btnBuscarOrigen');
        const tipoOrigenSelect = document.getElementById('tipoOrigen');
        const idOrigenInput = document.getElementById('idOrigen');
        const resultadoDiv = document.getElementById('resultadoOrigen');
        const detallesCard = document.getElementById('detallesCambioCard');
        const productoEntraDiv = document.getElementById('productoEntraContainer');
        const pvCambioSelect = document.getElementById('id_punto_venta_cambio');
        
        // 1. Inicializar Autocomplete para el Producto que Entra (Reemplazo)
        function inicializarProductoEntraAutocompletes(container) {
            const productoInput = container.querySelector('.producto-input-entra');
            const resultadosProductos = container.querySelector('.resultadosProductos-entra');
            const productoIdHidden = container.querySelector('.productoId-entra');
            initAutocomplete(productoInput, resultadosProductos, '../../controllers/ventas/buscar_productos.php', productoIdHidden);

            const colorInput = container.querySelector('.color-input-entra');
            const resultadosColores = container.querySelector('.resultadosColores-entra');
            const colorIdHidden = container.querySelector('.colorId-entra');
            initAutocomplete(colorInput, resultadosColores, '../../controllers/ventas/buscar_colores.php', colorIdHidden);
        }
        inicializarProductoEntraAutocompletes(productoEntraDiv);


        // 2. Listener para la búsqueda de origen (Venta/Compra)
        if (btnBuscarOrigen) {
            btnBuscarOrigen.addEventListener('click', function(e) {
                e.preventDefault(); 

                console.log("CLIC DETECTADO EN EL BOTÓN BUSCAR."); 
                
                const tipo = tipoOrigenSelect.value;
                const id = idOrigenInput.value;
                
                // Limpiar y ocultar detalles en cada nueva búsqueda
                resultadoDiv.innerHTML = '';
                detallesCard.style.display = 'none';

                if (!tipo || !id) {
                    resultadoDiv.innerHTML = '<div class="alert alert-warning">Debe seleccionar Tipo e ingresar ID.</div>';
                    return;
                }

                // Mostrar estado de carga
                resultadoDiv.innerHTML = '<div class="text-center text-info"><i class="fas fa-spinner fa-spin me-2"></i> Buscando ' + tipo.toUpperCase() + ' ID ' + id + '...</div>';

                // ** RUTA AJUSTADA: '../../controllers/cambios/buscar_origen.php' **
                const searchUrl = `../../controllers/cambios/buscar_origen.php?tipo=${tipo}&id=${id}`; 
                console.log("URL de búsqueda:", searchUrl); 

                fetch(searchUrl)
                    .then(response => {
                        if (!response.ok) {
                            // Si hay error HTTP (404, 500), intentar leer el error como texto
                            return response.text().then(text => { 
                                throw new Error(`Error HTTP ${response.status}: Revise los logs del servidor para: ${text.substring(0, 100)}...`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            resultadoDiv.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                            return;
                        }
                        
                        // --- MUESTRA DE RESULTADOS ---
                        let html = `<div class="alert alert-success">Transacción encontrada: ${tipo.toUpperCase()} ID ${id}.`;
                        html += `<br>Relacionado: <strong>${data.nombre_relacionado}</strong> | Total: <strong>$${parseFloat(data.total).toFixed(2)}</strong></div>`;
                        
                        html += '<h6 class="mt-3">Seleccione el producto del detalle original que desea CAMBIAR:</h6>';
                        html += '<table class="table table-sm table-striped"><thead><tr><th>Producto</th><th>Color</th><th>Talla</th><th>Cant. Original</th><th>Acción</th></tr></thead><tbody>';
                        
                        data.detalles.forEach(detalle => {
                            const productoNombre = `${detalle.nombre_producto} - ${detalle.nombre_color} - ${detalle.nombre_talla}`;
                            const boton = `<button type="button" class="btn btn-sm btn-primary btn-seleccionar-producto" 
                                                    data-id-producto="${detalle.id_producto}" 
                                                    data-id-color="${detalle.id_color}" 
                                                    data-id-talla="${detalle.id_talla}" 
                                                    data-cantidad-max="${detalle.cantidad}" 
                                                    data-nombre="${productoNombre}">
                                                Seleccionar
                                            </button>`;
                            html += `<tr><td>${detalle.nombre_producto}</td><td>${detalle.nombre_color}</td><td>${detalle.nombre_talla}</td><td>${detalle.cantidad}</td><td>${boton}</td></tr>`;
                        });
                        
                        html += '</tbody></table>';
                        resultadoDiv.innerHTML = html;
                    })
                    .catch(error => {
                        console.error('Error FATAL en la llamada AJAX o en la promesa:', error);
                        resultadoDiv.innerHTML = `<div class="alert alert-danger">Error de comunicación: ${error.message}</div>`;
                    });
            });
        } else {
             console.error("Error: El botón btnBuscarOrigen no fue encontrado en el DOM.");
        }


        // 3. Listener para seleccionar el producto original (evento delegado)
        resultadoDiv.addEventListener('click', function(event) {
            if (event.target.classList.contains('btn-seleccionar-producto')) {
                const btn = event.target;
                
                // 1. Rellenar campos ocultos de lo que 'SALE' del detalle original
                document.getElementById('idProductoSale').value = btn.dataset.idProducto;
                document.getElementById('idColorSale').value = btn.dataset.idColor;
                document.getElementById('idTallaSale').value = btn.dataset.idTalla;

                // 2. Mostrar la información y configurar cantidad máxima
                document.getElementById('productoOriginalDisplay').textContent = btn.dataset.nombre;
                
                const cantidadInput = document.getElementById('cantidadSale');
                cantidadInput.max = btn.dataset.cantidadMax;
                cantidadInput.value = 1; 
                document.getElementById('maxCantidadMensaje').textContent = `Cantidad máxima a cambiar: ${btn.dataset.cantidadMax}`;

                // 3. Mostrar la tarjeta de detalles del cambio
                detallesCard.style.display = 'block';

                // 4. Enfocar el punto de venta para forzar la carga de stock
                pvCambioSelect.focus();
            }
        });

        // 4. Listener para el Punto de Venta (carga stock para el producto que ENTRA/SALE)
        pvCambioSelect.addEventListener('change', function() {
            if (typeof cargarStockPorPuntoVenta === 'function') {
                cargarStockPorPuntoVenta(this.value);
            }
        });
        
        // 5. Listeners para validar stock del producto que ENTRA (si es VENTA)
        productoEntraDiv.addEventListener('change', function(event) {
            if (event.target.classList.contains('producto-input') ||
                event.target.classList.contains('color-input') ||
                event.target.classList.contains('talla-select')) {
                
                if (typeof validarCantidadProducto === 'function') {
                    validarCantidadProducto(productoEntraDiv);
                }
            }
        });

        productoEntraDiv.addEventListener('input', function(event) {
            if (event.target.classList.contains('cantidad-input')) {
                if (typeof validarCantidadProducto === 'function') {
                    validarCantidadProducto(productoEntraDiv);
                }
            }
        });
        
        // 6. Asegurar que los inputs de cantidad sean readonly si no hay PV al inicio
        if (!pvCambioSelect.value) {
            document.querySelectorAll('.cantidad-input').forEach(input => {
                input.readOnly = true;
            });
        }
    });
</script>