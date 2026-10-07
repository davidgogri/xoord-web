<?php
// views/cambios/cambios.php

// 1. Inicialización, Conexión y Seguridad
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

// Inclusión del header 
include __DIR__ . '/../../partials/header.php'; 

// 2. Lógica para obtener datos iniciales de la base de datos

// Obtener Tallas
$tallas = [];
try {
    // Asumimos que $pdo está disponible por la inclusión de header.php
    $stmt_tallas = $pdo->query("SELECT id_talla, nombre_talla FROM tallas ORDER BY nombre_talla ASC");
    $tallas = $stmt_tallas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar tallas en cambios.php: " . $e->getMessage());
}

// Obtener Puntos de Venta
$puntos_venta = [];
try {
    $stmt_pv = $pdo->query("SELECT id_punto_venta, nombre_punto FROM puntos_venta WHERE estado = 'activo' ORDER BY nombre_punto ASC");
    $puntos_venta = $stmt_pv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
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
                        <div class="resultadosProductos-entra resultadosProductos list-group position-absolute w-100 z-index-1000"></div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Color:</label>
                        <input type="text" class="form-control color-input-entra color-input" placeholder="Buscar color..." autocomplete="off" required>
                        <input type="hidden" name="id_color_entra" class="colorId-entra colorId" required>
                        <div class="resultadosColores-entra resultadosColores list-group position-absolute w-100 z-index-1000"></div>
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
    // FUNCIONES CLAVE
    // =================================================================================================
    
    let stockDisponible = {}; 

    // --- Función de utilería para navegación con teclado ---
    function updateSelection(items, selectedItemIndex) {
        items.forEach((item, index) => {
            if (index === selectedItemIndex) {
                item.classList.add('active');
                item.scrollIntoView({ block: 'nearest' });
            } else {
                item.classList.remove('active');
            }
        });
    }


    // --- Función Genérica para Autocompletado (CORREGIDA con navegación por teclado) ---
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
            
            let finalSearchUrl = searchUrl + '?q=' + encodeURIComponent(query);
            
            // Lógica para añadir id_punto_venta SOLO para la búsqueda de productos
            if (inputElement.classList.contains('producto-input-entra')) {
                const id_punto_venta = document.getElementById('id_punto_venta_cambio').value; 
                
                console.log('--- PRODUCTO AUTOCOMPLETE CHECK ---');
                console.log('Query de búsqueda:', query);
                console.log('ID del Punto de Venta del Cambio:', id_punto_venta);

                if (!id_punto_venta) {
                    resultsContainer.innerHTML = '<div class="list-group-item list-group-item-warning">Seleccione un Punto de Venta primero.</div>';
                    resultsContainer.style.display = 'block';
                    console.warn('Búsqueda bloqueada: PV no seleccionado.');
                    return; 
                }
                // Aquí es donde se agrega el parámetro que busca el stock
                finalSearchUrl += '&id_punto_venta=' + encodeURIComponent(id_punto_venta); 
            } 
            
            console.log('URL de la solicitud AJAX (FINAL):', finalSearchUrl); 
            
            currentRequest.open('GET', finalSearchUrl);
            
            currentRequest.onload = function() {
                if (currentRequest.status === 200) {
                    try {
                        const data = JSON.parse(currentRequest.responseText);
                        console.log('Respuesta JSON recibida:', data); 

                        if (data.error) {
                            console.error('Error del servidor:', data.error);
                            resultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error: ${data.error}</div>`;
                            resultsContainer.style.display = 'block';
                            return;
                        }

                        if (data && data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.classList.add('list-group-item', 'list-group-item-action');
                                
                                let displayText = item.nombre_color || item.nombre || item.descripcion || item.label || ''; 
                                let selectedId = item.id_producto || item.id_color || ''; 

                                // Asegurar que el label se use si existe (viene de buscar_productos.php)
                                if (item.label) {
                                    displayText = item.label;
                                }

                                div.textContent = displayText;
                                div.dataset.id = selectedId;
                                div.dataset.item = JSON.stringify(item);
                                
                                div.addEventListener('click', function() {
                                    inputElement.value = this.textContent;
                                    hiddenIdElement.value = this.dataset.id;
                                    resultsContainer.innerHTML = '';
                                    resultsContainer.style.display = 'none';

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
                    resultsContainer.innerHTML = `<div class="list-group-item list-group-item-danger">Error al cargar resultados. Código: ${currentRequest.status}.</div>`;
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

        // --- Manejo de navegación con teclado (FIX para foco) ---
        inputElement.addEventListener('keydown', function(e) {
            const items = Array.from(resultsContainer.children);
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (selectedItemIndex < items.length - 1) {
                    selectedItemIndex++;
                } else {
                    selectedItemIndex = 0; // Volver al inicio
                }
                updateSelection(items, selectedItemIndex);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selectedItemIndex > 0) {
                    selectedItemIndex--;
                } else {
                    selectedItemIndex = items.length - 1; // Ir al final
                }
                updateSelection(items, selectedItemIndex);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedItemIndex > -1 && items[selectedItemIndex]) {
                    items[selectedItemIndex].click(); // Simular clic
                }
            }
        });

        // Ocultar resultados si se hace clic fuera
        document.addEventListener('click', function(event) {
            if (!inputElement.contains(event.target) && !resultsContainer.contains(event.target)) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
            }
        });

    }

    // --- Función para cargar stock ---
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
                
                const productoDiv = document.getElementById('productoEntraContainer');
                if (productoDiv) {
                    validarCantidadProducto(productoDiv);
                }
                // Forzar la re-ejecución del autocompletado si ya hay un query para que use el nuevo stock
                const productoInput = document.querySelector('.producto-input-entra');
                if (productoInput.value.length >= 2) {
                    productoInput.dispatchEvent(new Event('input')); 
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

    // --- Función para validar la cantidad y mostrar stock ---
    function validarCantidadProducto(productoDiv) {
        const tipoOrigen = document.getElementById('tipoOrigen').value;
        const cantidadInput = productoDiv.querySelector('.cantidad-input');
        const stockMensaje = productoDiv.querySelector('.stock-disponible-mensaje');

        // Si es cambio con proveedor, no se valida stock
        if (tipoOrigen === 'compra') {
            stockMensaje.style.display = 'none';
            cantidadInput.readOnly = false;
            return;
        }

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
    // INICIALIZACIÓN DE LISTENERS
    // =================================================================================================

    document.addEventListener('DOMContentLoaded', function () {

        const btnBuscarOrigen = document.getElementById('btnBuscarOrigen');
        const resultadoDiv = document.getElementById('resultadoOrigen');
        const detallesCard = document.getElementById('detallesCambioCard');
        const productoEntraDiv = document.getElementById('productoEntraContainer');
        const pvCambioSelect = document.getElementById('id_punto_venta_cambio');
        
        // 1. Inicializar Autocomplete para el Producto que Entra (Reemplazo)
        function inicializarProductoEntraAutocompletes(container) {
            const productoInput = container.querySelector('.producto-input-entra');
            const resultadosProductos = container.querySelector('.resultadosProductos-entra');
            const productoIdHidden = container.querySelector('.productoId-entra');
            // La ruta es la misma de nueva_venta.txt:
            initAutocomplete(productoInput, resultadosProductos, '../../controllers/ventas/buscar_productos.php', productoIdHidden);

            const colorInput = container.querySelector('.color-input-entra');
            const resultadosColores = container.querySelector('.resultadosColores-entra');
            const colorIdHidden = container.querySelector('.colorId-entra');
            // La ruta es la misma de nueva_venta.txt:
            initAutocomplete(colorInput, resultadosColores, '../../controllers/ventas/buscar_colores.php', colorIdHidden);
        }
        inicializarProductoEntraAutocompletes(productoEntraDiv);


        // 2. Listener para la búsqueda de origen 
        if (btnBuscarOrigen) {
            btnBuscarOrigen.addEventListener('click', function(e) {
                e.preventDefault(); 
                
                const tipo = document.getElementById('tipoOrigen').value;
                const id = document.getElementById('idOrigen').value;
                
                resultadoDiv.innerHTML = '';
                detallesCard.style.display = 'none';

                if (!tipo || !id) {
                    resultadoDiv.innerHTML = '<div class="alert alert-warning">Debe seleccionar Tipo e ingresar ID.</div>';
                    return;
                }

                resultadoDiv.innerHTML = '<div class="text-center text-info"><i class="fas fa-spinner fa-spin me-2"></i> Buscando ' + tipo.toUpperCase() + ' ID ' + id + '...</div>';

                const searchUrl = `../../controllers/cambios/buscar_origen.php?tipo=${tipo}&id=${id}`; 

                fetch(searchUrl)
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => { 
                                throw new Error(`Error HTTP ${response.status}: Revise los logs del servidor para: ${text.substring(0, 100)}...`);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.error) {
                            resultadoDiv.innerHTML = `<div class="alert alert-danger">Error de base de datos al buscar transacción. Revise los logs del servidor.</div>`;
                            console.error('Error de API al buscar origen:', data.error);
                            return;
                        }
                        
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
        } 


        // 3. Listener para seleccionar el producto original (evento delegado)
        resultadoDiv.addEventListener('click', function(event) {
            if (event.target.classList.contains('btn-seleccionar-producto')) {
                const btn = event.target;
                
                document.getElementById('idProductoSale').value = btn.dataset.idProducto;
                document.getElementById('idColorSale').value = btn.dataset.idColor;
                document.getElementById('idTallaSale').value = btn.dataset.idTalla;

                document.getElementById('productoOriginalDisplay').textContent = btn.dataset.nombre;
                
                const cantidadInput = document.getElementById('cantidadSale');
                cantidadInput.max = btn.dataset.cantidadMax;
                cantidadInput.value = 1; 
                document.getElementById('maxCantidadMensaje').textContent = `Cantidad máxima a cambiar: ${btn.dataset.cantidadMax}`;

                detallesCard.style.display = 'block';

                pvCambioSelect.focus();
            }
        });

        // 4. Listener para el Punto de Venta (carga stock)
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