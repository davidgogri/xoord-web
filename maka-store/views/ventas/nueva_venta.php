<?php
// views/ventas/nueva_venta.php

// Habilitar la visualización de errores (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Iniciar sesión si no está iniciada. Asumimos que partials/header.php también lo hace.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirigir si el usuario no está logueado
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

// --- NUEVA LÓGICA DE GENERACIÓN DE TOKEN ---
// Genera un token CSRF único y lo almacena en la sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];
// --- FIN NUEVA LÓGICA DE GENERACIÓN DE TOKEN ---


// Incluir header.php. Asumimos que este archivo:
// - Inicia la sesión (session_start()) si no se hizo arriba
// - Realiza la conexión a la base de datos y la almacena en $pdo (require_once '../../includes/db.php';)
// - Contiene el HTML inicial, Bootstrap y Font Awesome CSS, y la etiqueta de apertura <body>
include __DIR__ . '/../../partials/header.php';

// --- Lógica para obtener datos iniciales de la base de datos ---

// Lógica para obtener las tallas disponibles
$tallas = [];
$error_tallas = null;
try {
    // Asegúrate de que $pdo esté disponible aquí gracias a header.php
    $stmt_tallas = $pdo->query("SELECT id_talla, nombre_talla FROM tallas ORDER BY nombre_talla ASC");
    $tallas = $stmt_tallas->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_tallas = "Error al cargar tallas: " . $e->getMessage();
    error_log("Error al cargar tallas en nueva_venta.php: " . $e->getMessage());
}

// Lógica para obtener los puntos de venta
$puntos_venta = [];
$error_puntos_venta = null;
try {
    $stmt_pv = $pdo->query("SELECT id_punto_venta, nombre_punto FROM puntos_venta WHERE estado = 'activo' ORDER BY nombre_punto ASC");
    $puntos_venta = $stmt_pv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_puntos_venta = "Error al cargar puntos de venta: " . $e->getMessage();
    error_log("Error al cargar puntos de venta en nueva_venta.php: " . $e->getMessage());
}

// --- HTML del Formulario de Venta ---
?>

<div class="container mt-4">
    <h1 class="mb-4 text-primary text-center"><i class="fas fa-cash-register me-2"></i>Nueva Venta</h1>

    <?php
    // Mostrar mensajes de éxito o error si existen
    if (isset($_SESSION['success_message'])) {
        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['success_message']);
    }
    if (isset($_SESSION['error_message'])) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $_SESSION['error_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
        unset($_SESSION['error_message']);
    }
    if ($error_tallas) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . $error_tallas . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    if ($error_puntos_venta) {
        echo '<div class="alert alert-warning alert-dismissible fade show" role="alert">' . $error_puntos_venta . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
    }
    ?>

    <form action="../../controllers/ventas/registrar_venta.php" method="POST">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Información de la Venta</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label for="clienteInput" class="form-label"><i class="fas fa-user me-2"></i>Cliente:</label>
                        <input type="text" class="form-control" id="clienteInput" placeholder="Buscar cliente..." autocomplete="off" required>
                        <input type="hidden" id="clienteId" name="id_cliente" required>
                        <div id="resultadosClientes" class="list-group position-absolute w-50 z-index-1000"></div>
                    </div>
                    <div class="col-md-6">
                        <label for="id_punto_venta" class="form-label"><i class="fas fa-store me-2"></i>Punto de Venta:</label>
                        <select class="form-select" id="id_punto_venta" name="id_punto_venta" required>
                            <option value="">Selecciona un punto de venta</option>
                            <?php foreach ($puntos_venta as $pv): ?>
                                <option value="<?php echo htmlspecialchars($pv['id_punto_venta']); ?>">
                                    <?php echo htmlspecialchars($pv['nombre_punto']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="metodo_pago" class="form-label"><i class="fas fa-credit-card me-2"></i>Método de Pago:</label>
                        <select class="form-select" id="metodo_pago" name="metodo_pago" required>
                            <option value="Efectivo">Efectivo</option>
                            <option value="Tarjeta de Credito">Tarjeta de Crédito</option>
                            <option value="Tarjeta de Debito">Tarjeta de Débito</option>
                            <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                            <option value="Otros">Otros</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="fecha_venta" class="form-label"><i class="fas fa-calendar-alt me-2"></i>Fecha de Venta:</label>
                        <input type="date" class="form-control" id="fecha_venta" name="fecha_venta" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="observaciones" class="form-label"><i class="fas fa-comments me-2"></i>Observaciones (opcional):</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="3"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-box me-2"></i>Productos</h5>
            </div>
            <div class="card-body">
                <div id="productosContainer">
                    <div class="producto-item border p-3 mb-3 rounded shadow-sm">
                        <div class="row g-3">
                            <input type="hidden" name="producto_id[]" class="productoId" required>
                            <input type="hidden" name="color_id[]" class="colorId" required>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-cube me-2"></i>Producto:</label>
                                <input type="text" class="form-control producto-input" placeholder="Buscar producto..." autocomplete="off" required>
                                <div class="resultadosProductos list-group position-absolute w-25 z-index-1000"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label>
                                <input type="text" class="form-control color-input" placeholder="Buscar color..." autocomplete="off" required>
                                <div class="resultadosColores list-group position-absolute w-25 z-index-1000"></div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label>
                                <select name="talla_id[]" class="form-select talla-select" required>
                                    <option value="">Selecciona talla</option>
                                    <?php foreach ($tallas as $talla): ?>
                                        <option value="<?php echo htmlspecialchars($talla['id_talla']); ?>">
                                            <?php echo htmlspecialchars($talla['nombre_talla']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Venta (unidad):</label>
                                <input type="number" step="0.01" name="precio_venta[]" class="form-control precio-venta-input" placeholder="0.00" min="0" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label>
                                <input type="number" name="cantidad[]" min="1" class="form-control cantidad-input" placeholder="Cantidad" required>
                                <small class="text-muted stock-disponible-mensaje mt-1" style="display: none;"></small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove(); calcularTotalVenta();">
                                <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                            </button>
                        </div>
                    </div>
                </div>
                <button type="button" id="btnAgregarProducto" class="btn btn-secondary btn-sm mt-3"><i class="fas fa-plus me-2"></i>Agregar Otro Producto</button>
            </div>
        </div>

        <div class="card mb-4 shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Resumen de Venta</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label for="total_venta_display" class="form-label fs-4"><i class="fas fa-money-bill-wave me-2"></i>Total de Venta:</label>
                    <input type="text" class="form-control form-control-lg text-end" id="total_venta_display" value="0.00" readonly>
                    <input type="hidden" name="total_venta" id="total_venta_hidden">
                </div>
                <button type="submit" class="btn btn-success btn-lg w-100"><i class="fas fa-check-circle me-2"></i>Registrar Venta</button>
            </div>
        </div>
    </form>
</div>

<?php
// Incluir el footer.php que contendrá el cierre de la etiqueta </body> y </html>
include __DIR__ . '/../../partials/footer.php';
?>

<script>
    // Variable global para almacenar el stock cargado por punto de venta
    let stockDisponible = {};

    // --- Función Genérica para Autocompletado ---
    function initAutocomplete(inputElement, resultsContainer, searchUrl, hiddenIdElement) {
        let currentRequest = null; // Para abortar solicitudes anteriores
        let selectedItemIndex = -1; // Para navegación con teclado

        inputElement.addEventListener('input', function() {
            const query = this.value.trim();
            resultsContainer.innerHTML = ''; // Limpiar resultados anteriores
            resultsContainer.style.display = 'none';
            hiddenIdElement.value = ''; // Limpiar ID oculto si cambia la búsqueda
            selectedItemIndex = -1; // Resetear índice de selección

            if (query.length < 2) { // Dispara la búsqueda solo si hay al menos 2 caracteres
                return;
            }

            // Abortar la solicitud anterior si aún está pendiente
            if (currentRequest) {
                currentRequest.abort();
            }

            currentRequest = new XMLHttpRequest();
            currentRequest.open('GET', searchUrl + '?q=' + encodeURIComponent(query));
            
            // Si es búsqueda de productos, añadir el id_punto_venta
            if (inputElement.classList.contains('producto-input')) {
                const id_punto_venta = document.getElementById('id_punto_venta').value;
                if (!id_punto_venta) {
                    resultsContainer.innerHTML = '<div class="list-group-item list-group-item-warning">Seleccione un Punto de Venta primero.</div>';
                    resultsContainer.style.display = 'block';
                    return;
                }
                currentRequest.open('GET', searchUrl + '?q=' + encodeURIComponent(query) + '&id_punto_venta=' + encodeURIComponent(id_punto_venta));
            } else {
                currentRequest.open('GET', searchUrl + '?q=' + encodeURIComponent(query));
            }


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
                                
                                let displayText = '';
                                let selectedId = '';

                                // PRIORIDAD 1: Si hay 'label' (preferido por jQuery UI Autocomplete, útil para nombres completos)
                                if (item.label) {
                                    displayText = item.label;
                                } 
                                // PRIORIDAD 2: Para clientes
                                else if (item.nombre_cliente) {
                                    displayText = item.nombre_cliente;
                                } 
                                // PRIORIDAD 3: Para colores
                                else if (item.nombre_color) {
                                    displayText = item.nombre_color;
                                }
                                // PRIORIDAD 4: Para productos (si no hay 'label' de descripción)
                                else if (item.nombre) { // 'nombre' para productos si no hay 'label'
                                    displayText = item.nombre;
                                } else if (item.descripcion) { // 'descripcion' para productos si es el alias
                                    displayText = item.descripcion;
                                }


                                // Asignar ID
                                if (item.id_producto) {
                                    selectedId = item.id_producto;
                                } else if (item.id_cliente) {
                                    selectedId = item.id_cliente;
                                } else if (item.id_color) {
                                    selectedId = item.id_color;
                                }

                                div.textContent = displayText;
                                div.dataset.id = selectedId;
                                // Almacenar el objeto completo si es necesario, ej. para precio_venta
                                div.dataset.item = JSON.stringify(item); 

                                div.addEventListener('click', function() {
                                    inputElement.value = this.textContent;
                                    hiddenIdElement.value = this.dataset.id;
                                    resultsContainer.innerHTML = '';
                                    resultsContainer.style.display = 'none';

                                    // Si es un producto, rellenar el precio de venta y disparar validación de stock
                                    if (inputElement.classList.contains('producto-input')) {
                                        const selectedProduct = JSON.parse(this.dataset.item);
                                        const precioVentaInput = inputElement.closest('.producto-item').querySelector('.precio-venta-input');
                                        if (precioVentaInput && selectedProduct.precio_venta) {
                                            precioVentaInput.value = selectedProduct.precio_venta;
                                        }
                                        // Disparar validación de stock al seleccionar producto
                                        const productoDiv = inputElement.closest('.producto-item');
                                        if (productoDiv) {
                                            validarCantidadProducto(productoDiv);
                                        }
                                    }
                                    
                                    // Si es color, dispara validación de stock
                                    if (inputElement.classList.contains('color-input')) {
                                        const productoDiv = inputElement.closest('.producto-item');
                                        if (productoDiv) {
                                            validarCantidadProducto(productoDiv);
                                        }
                                    }
                                    
                                    calcularTotalVenta(); // Recalcular total cada vez que un precio o cantidad cambie
                                });
                                resultsContainer.appendChild(div);
                            });
                            resultsContainer.style.display = 'block';
                            selectedItemIndex = -1; // Resetear índice de selección al cargar nuevos resultados
                        } else {
                            resultsContainer.innerHTML = '<div class="list-group-item">No se encontraron resultados.</div>';
                            resultsContainer.style.display = 'block';
                        }
                    } catch (e) {
                        console.error('Error al parsear datos JSON: ', e, 'Respuesta del servidor:', currentRequest.responseText);
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

        // --- Manejo de navegación con teclado ---
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
                updateSelection(items);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (selectedItemIndex > 0) {
                    selectedItemIndex--;
                } else {
                    selectedItemIndex = items.length - 1; // Ir al final
                }
                updateSelection(items);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                if (selectedItemIndex > -1 && items[selectedItemIndex]) {
                    items[selectedItemIndex].click(); // Simular clic
                }
            }
        });

        function updateSelection(items) {
            items.forEach((item, index) => {
                if (index === selectedItemIndex) {
                    item.classList.add('active');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('active');
                }
            });
        }

        // Ocultar resultados si se hace clic fuera
        document.addEventListener('click', function(event) {
            if (!inputElement.contains(event.target) && !resultsContainer.contains(event.target)) {
                resultsContainer.innerHTML = '';
                resultsContainer.style.display = 'none';
            }
        });
    }

    // --- Función para inicializar autocompletes para un bloque de producto ---
    function inicializarProductoVentaAutocompletes(productoDiv) {
        const productoInput = productoDiv.querySelector('.producto-input');
        const resultadosProductos = productoDiv.querySelector('.resultadosProductos');
        const productoIdHidden = productoDiv.querySelector('.productoId');
        initAutocomplete(productoInput, resultadosProductos, '../../controllers/ventas/buscar_productos.php', productoIdHidden);

        const colorInput = productoDiv.querySelector('.color-input');
        const resultadosColores = productoDiv.querySelector('.resultadosColores');
        const colorIdHidden = productoDiv.querySelector('.colorId');
        initAutocomplete(colorInput, resultadosColores, '../../controllers/ventas/buscar_colores.php', colorIdHidden);
    }

    // --- Función para cargar stock al cambiar el punto de venta ---
    function cargarStockPorPuntoVenta(id_punto_venta) {
        if (!id_punto_venta) {
            stockDisponible = {}; // Si no hay PV seleccionado, no hay stock disponible
            console.log('No hay Punto de Venta seleccionado, stock vacío.');
            // Limpiar mensajes de stock y cantidades
            document.querySelectorAll('.stock-disponible-mensaje').forEach(el => {
                el.style.display = 'none';
                el.textContent = '';
            });
            document.querySelectorAll('.cantidad-input').forEach(input => {
                input.value = '';
                input.readOnly = true; // Hacer readonly si no hay PV
            });
            return;
        }

        fetch(`../../controllers/ventas/get_stock_by_punto_venta.php?id_punto_venta=${id_punto_venta}`)
            .then(response => {
                if (!response.ok) {
                    // Si hay un error HTTP, lanzar una excepción para el catch
                    return response.text().then(text => { // Usar .text() para ver el error HTML/PHP
                        console.error('Respuesta no OK de get_stock_by_punto_venta.php:', text);
                        throw new Error('Error HTTP: ' + response.status + ' ' + response.statusText + ' - ' + (text.substring(0, 100) + (text.length > 100 ? '...' : '')));
                    });
                }
                return response.json(); // Intentar parsear como JSON
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error); // Si el JSON contiene un error
                }
                stockDisponible = data;
                console.log('Stock cargado para PV ' + id_punto_venta + ':', stockDisponible);
                // Actualizar validación de cantidad para todos los productos existentes
                document.querySelectorAll('.producto-item').forEach(productoDiv => {
                    validarCantidadProducto(productoDiv);
                });
            })
            .catch(error => {
                console.error('Error cargando stock:', error.message);
                alert('No se pudo cargar el stock para el punto de venta seleccionado: ' + error.message);
                stockDisponible = {}; // Vaciar stock si hay error
                // Limpiar mensajes de stock y cantidades si hubo un error al cargar
                document.querySelectorAll('.stock-disponible-mensaje').forEach(el => {
                    el.style.display = 'none';
                    el.textContent = '';
                });
                document.querySelectorAll('.cantidad-input').forEach(input => {
                    input.value = '';
                    input.readOnly = true; // Hacer readonly si hay error de stock
                });
            });
    }

    // --- Función para validar la cantidad y mostrar stock ---
    function validarCantidadProducto(productoDiv) {
        const productoId = productoDiv.querySelector('.productoId').value;
        const colorId = productoDiv.querySelector('.colorId').value;
        const tallaId = productoDiv.querySelector('.talla-select').value;
        const cantidadInput = productoDiv.querySelector('.cantidad-input');
        const stockMensaje = productoDiv.querySelector('.stock-disponible-mensaje');

        // Solo validar si los IDs están presentes
        if (!productoId || !colorId || !tallaId) {
            stockMensaje.style.display = 'none';
            cantidadInput.readOnly = true; // No permitir cantidad si no hay IDs válidos
            cantidadInput.value = '';
            return;
        }

        const stockKey = `${productoId}-${colorId}-${tallaId}`;
        const stock = stockDisponible[stockKey]; // Puede ser undefined si no hay stock

        if (typeof stock === 'number' && stock >= 0) { // Asegurarse de que el stock es un número no negativo
            stockMensaje.textContent = `Stock disponible: ${stock}`;
            stockMensaje.style.display = 'block';
            cantidadInput.max = stock; // Establecer el máximo para el input
            cantidadInput.readOnly = false; // Permitir edición

            // Validar la cantidad actual
            let cantidad = parseInt(cantidadInput.value);
            if (isNaN(cantidad) || cantidad <= 0) {
                // Si la cantidad no es válida o es 0/negativa, no hacer nada o establecer a 1 si es un nuevo campo
                if (!isNaN(cantidad) && cantidad <= 0) {
                     cantidadInput.value = ''; // Limpiar si es <= 0
                }
            } else if (cantidad > stock) {
                cantidadInput.value = stock; // Ajustar la cantidad si excede el stock
            }

            if (stock === 0) { // Si el stock es 0, no permitir introducir cantidad
                cantidadInput.value = '';
                cantidadInput.readOnly = true;
                stockMensaje.textContent = 'Stock agotado.';
            }

        } else {
            // Si el stock no es un número válido o es negativo (que no debería ocurrir con la consulta)
            // O si la clave no existe en stockDisponible (es decir, stock indefinido)
            stockMensaje.textContent = 'Stock no disponible para esta combinación.';
            stockMensaje.style.display = 'block';
            cantidadInput.max = ''; // Eliminar el máximo
            cantidadInput.value = ''; // Vaciar la cantidad
            cantidadInput.readOnly = true; // Hacer el campo solo lectura si no hay stock
        }
        calcularTotalVenta(); // Recalcular total después de validar cantidad
    }

    // --- Función para agregar un nuevo bloque de producto ---
    function agregarProducto() {
        const productosContainer = document.getElementById('productosContainer');
        const tallasOptions = `<?php
            $options = '';
            foreach ($tallas as $talla) {
                $options .= '<option value="' . htmlspecialchars($talla['id_talla']) . '">' . htmlspecialchars($talla['nombre_talla']) . '</option>';
            }
            echo $options;
        ?>`;

        const div = document.createElement('div');
        div.classList.add('producto-item', 'border', 'p-3', 'mb-3', 'rounded', 'shadow-sm');
        div.innerHTML = `
            <div class="row g-3">
                <input type="hidden" name="producto_id[]" class="productoId" required>
                <input type="hidden" name="color_id[]" class="colorId" required>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-cube me-2"></i>Producto:</label>
                    <input type="text" class="form-control producto-input" placeholder="Buscar producto..." autocomplete="off" required>
                    <div class="resultadosProductos list-group position-absolute w-25 z-index-1000"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label>
                    <input type="text" class="form-control color-input" placeholder="Buscar color..." autocomplete="off" required>
                    <div class="resultadosColores list-group position-absolute w-25 z-index-1000"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label>
                    <select name="talla_id[]" class="form-select talla-select" required>
                        <option value="">Selecciona talla</option>
                        ${tallasOptions}
                    </select>
                </div>
            </div>
            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Venta (unidad):</label>
                    <input type="number" step="0.01" name="precio_venta[]" class="form-control precio-venta-input" placeholder="0.00" min="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label>
                    <input type="number" name="cantidad[]" min="1" class="form-control cantidad-input" placeholder="Cantidad" required>
                    <small class="text-muted stock-disponible-mensaje mt-1" style="display: none;"></small>
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
                <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove(); calcularTotalVenta();">
                    <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                </button>
            </div>
        `;
        productosContainer.appendChild(div);
        inicializarProductoVentaAutocompletes(div);

        // Al agregar un nuevo producto, re-evaluar el stock si ya hay un punto de venta seleccionado
        const id_punto_venta_select = document.getElementById('id_punto_venta');
        if (id_punto_venta_select.value) {
            validarCantidadProducto(div); // Validar para el nuevo div
        } else {
            // Si no hay PV seleccionado, asegurar que el input de cantidad esté readonly
            div.querySelector('.cantidad-input').readOnly = true;
        }
        calcularTotalVenta(); // Recalcular total al agregar un nuevo producto
    }

    // --- Función para calcular el total de la venta ---
    function calcularTotalVenta() {
        let total = 0;
        document.querySelectorAll('.producto-item').forEach(productoDiv => {
            const precio = parseFloat(productoDiv.querySelector('.precio-venta-input').value) || 0;
            const cantidad = parseInt(productoDiv.querySelector('.cantidad-input').value) || 0;
            total += (precio * cantidad);
        });
        document.getElementById('total_venta_display').value = total.toFixed(2);
        document.getElementById('total_venta_hidden').value = total.toFixed(2);
    }


    // --- DOMContentLoaded: Se ejecuta cuando el HTML está completamente cargado ---
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar búsqueda para el campo de cliente principal
        const clienteInput = document.getElementById('clienteInput');
        const resultadosClientes = document.getElementById('resultadosClientes');
        const clienteId = document.getElementById('clienteId');
        initAutocomplete(clienteInput, resultadosClientes, '../../controllers/ventas/buscar_clientes.php', clienteId);

        // Inicializar autocompletes para los productos existentes (si los hay al cargar)
        document.querySelectorAll('.producto-item').forEach(inicializarProductoVentaAutocompletes);

        // Listener para el botón de agregar producto
        document.getElementById('btnAgregarProducto').addEventListener('click', agregarProducto);

        // Listener para el cambio del Punto de Venta
        const puntoVentaSelect = document.getElementById('id_punto_venta');
        puntoVentaSelect.addEventListener('change', function() {
            const selectedPuntoVentaId = this.value;
            cargarStockPorPuntoVenta(selectedPuntoVentaId);
        });

        // Event listeners para los cambios en productos, colores y tallas y cantidad
        // Se agregan al contenedor de productos y delegan el evento
        document.getElementById('productosContainer').addEventListener('change', function(event) {
            if (event.target.classList.contains('producto-input') ||
                event.target.classList.contains('color-input') ||
                event.target.classList.contains('talla-select')) {
                const productoDiv = event.target.closest('.producto-item');
                if (productoDiv) {
                    validarCantidadProducto(productoDiv);
                }
            }
            calcularTotalVenta(); // Recalcular total en cada cambio relevante
        });

        document.getElementById('productosContainer').addEventListener('input', function(event) {
            if (event.target.classList.contains('cantidad-input') || event.target.classList.contains('precio-venta-input')) {
                const productoDiv = event.target.closest('.producto-item');
                if (productoDiv) {
                    // Si cambia la cantidad, revalidar stock
                    if (event.target.classList.contains('cantidad-input')) {
                        validarCantidadProducto(productoDiv);
                    }
                    calcularTotalVenta(); // Recalcular total al cambiar precio o cantidad
                }
            }
        });

        // Inicializar el stock si ya hay un punto de venta seleccionado al cargar la página (útil si se recargó con un valor previo)
        if (puntoVentaSelect.value) {
            cargarStockPorPuntoVenta(puntoVentaSelect.value);
        } else {
             // Si no hay PV seleccionado al inicio, deshabilitar campos de cantidad de productos
             document.querySelectorAll('.cantidad-input').forEach(input => {
                input.readOnly = true;
            });
        }

        // Asegurarse de que la fecha de venta siempre tenga el valor actual si no está ya establecido
        const fechaVentaInput = document.getElementById('fecha_venta');
        if (!fechaVentaInput.value) {
            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0'); // Months start at 0!
            const dd = String(today.getDate()).padStart(2, '0');
            fechaVentaInput.value = `${yyyy}-${mm}-${dd}`;
        }
        calcularTotalVenta(); // Calcular el total inicial al cargar la página
    });
</script>