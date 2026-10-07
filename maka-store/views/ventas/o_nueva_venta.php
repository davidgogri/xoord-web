<?php
// Habilitar la visualización de errores (para depuración)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ESTE ES EL CAMBIO CLAVE: Incluir header.php usando __DIR__ y asumiendo que header.php
// ya inicia la sesión (session_start()) y conecta a la base de datos (db.php)
include __DIR__ . '/../../partials/header.php';

// Verificar la sesión del usuario.
// session_start() y la conexión a DB ($pdo) deberían ser manejados por header.php.
if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../../login.php");
    exit;
}

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

?>

    <div class="container mt-4">
        <h1 class="mb-4 text-primary text-center"><i class="fas fa-cash-register me-2"></i> Registrar Nueva Venta</h1>
        <p class="text-muted text-center">Completa el formulario para registrar una nueva transacción de venta.</p>

        <div class="card shadow-lg mb-5 border-primary">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i> Detalles de la Venta</h5>
            </div>
            <div class="card-body">
                <form id="formVenta" action="/controllers/ventas/registrar_venta.php" method="POST">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3 position-relative">
                            <label for="clienteInput" class="form-label"><i class="fas fa-user me-2"></i>Cliente:</label>
                            <input type="text" id="clienteInput" class="form-control" placeholder="Escribe al menos 3 letras del cliente..." autocomplete="off" required>
                            <input type="hidden" name="id_cliente" id="clienteId">
                            <div id="resultadosClientes" class="autocomplete list-group mt-1 position-absolute w-100 shadow-sm"></div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="metodo_pago" class="form-label"><i class="fas fa-wallet me-2"></i>Método de Pago:</label>
                            <select name="metodo_pago" id="metodo_pago" class="form-select" required>
                                <option value="" disabled selected>Seleccionar método de pago</option>
                                <option value="Efectivo">Efectivo</option>
                                <option value="Tarjeta">Tarjeta</option>
                                <option value="Nequi">Nequi</option>
                                <option value="Daviplata">Daviplata</option>
                            </select>
                        </div>
                    </div>

                    <hr class="my-5">

                    <h4 class="mb-4 text-primary"><i class="fas fa-boxes me-2"></i> Productos de la Venta</h4>
                    <div id="productos" class="row">
                        <div class="col-12 mb-4 producto-item">
                            <div class="card bg-light border shadow-sm">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6 position-relative">
                                            <label class="form-label"><i class="fas fa-tag me-2"></i>Producto:</label>
                                            <input type="text" class="form-control productoInput" placeholder="Escribe al menos 3 letras del producto..." autocomplete="off" required>
                                            <input type="hidden" name="producto_id[]" class="productoId">
                                            <div class="resultadosProductos autocomplete list-group mt-1 position-absolute w-100 shadow-sm"></div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Venta (unidad):</label>
                                            <input type="number" name="precio_venta[]" class="form-control precioVenta" step="0.01" placeholder="Precio sugerido" required>
                                        </div>

                                        <div class="col-md-4 position-relative">
                                            <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label>
                                            <input type="text" class="form-control colorInput" placeholder="Escribe al menos 3 letras del color..." autocomplete="off" required>
                                            <input type="hidden" name="color_id[]" class="colorId">
                                            <div class="resultadosColores autocomplete list-group mt-1 position-absolute w-100 shadow-sm"></div>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label>
                                            <select name="talla_id[]" class="form-select talla-select" required>
                                                <option value="">Seleccionar talla</option>
                                                <?php if (!isset($error_tallas)): ?>
                                                    <?php foreach ($tallas as $talla): ?>
                                                        <option value="<?php echo htmlspecialchars($talla['id_talla']); ?>">
                                                            <?php echo htmlspecialchars($talla['nombre_talla']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <option value="" disabled><?php echo htmlspecialchars($error_tallas); ?></option>
                                                <?php endif; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label>
                                            <input type="number" name="cantidad[]" min="1" class="form-control" placeholder="Cantidad" required>
                                        </div>
                                    </div>
                                    <div class="d-flex justify-content-end mt-3">
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove()">
                                            <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <button type="button" class="btn btn-outline-primary" id="btnAgregarProducto">
                            <i class="fas fa-plus-circle me-2"></i> Agregar Otro Producto
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-check-circle me-2"></i> Registrar Venta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap, etc.
include '../../partials/footer.php';
?>

<script>
    // --- Variables JS desde PHP (Tallas) ---
    // Esta variable se usa para pre-cargar las opciones de talla en los productos añadidos dinámicamente
    const tallasOptions = `
        <?php if (isset($error_tallas)): ?>
            <option value="" disabled><?php echo htmlspecialchars($error_tallas); ?></option>
        <?php else: ?>
            <?php foreach ($tallas as $talla): ?>
                <option value="<?php echo htmlspecialchars($talla['id_talla']); ?>">
                    <?php echo htmlspecialchars($talla['nombre_talla']); ?>
                </option>
            <?php endforeach; ?>
        <?php endif; ?>
    `;

    // --- Función genérica para Autocompletado (adaptada a Bootstrap) ---
    function initAutocomplete(inputElement, resultContainer, endpoint, hiddenField, extraField = null) {
        let currentTimeout = null;

        inputElement.addEventListener('input', function () {
            const query = this.value.trim();

            if (currentTimeout) {
                clearTimeout(currentTimeout);
            }

            if (query.length >= 3) {
                currentTimeout = setTimeout(() => {
                    fetch(`${endpoint}?q=${encodeURIComponent(query)}`)
                        .then(res => {
                            if (!res.ok) {
                                console.error(`HTTP error! status: ${res.status} for endpoint: ${endpoint}`);
                                throw new Error(`HTTP error! status: ${res.status}`);
                            }
                            return res.json();
                        })
                        .then(data => {
                            resultContainer.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(item => {
                                    const div = document.createElement('div');
                                    div.classList.add('list-group-item', 'list-group-item-action', 'py-2');

                                    let displayText = '';
                                    let selectedId = '';

                                    // Determinar el texto a mostrar y el ID a guardar según el endpoint
                                    if (endpoint.includes('buscar_clientes.php')) {
                                        displayText = item.nombre_cliente || 'Cliente sin nombre';
                                        selectedId = item.id_cliente;
                                    } else if (endpoint.includes('buscar_productos.php')) {
                                        displayText = item.nombre || 'Producto sin nombre';
                                        selectedId = item.id_producto;
                                    } else if (endpoint.includes('buscar_colores.php')) {
                                        displayText = item.nombre_color || 'Color sin nombre';
                                        selectedId = item.id_color;
                                    } else {
                                        // Fallback genérico por si se usa con otros endpoints
                                        displayText = item.nombre || item.nombre_color || item.nombre_cliente || 'N/A';
                                        selectedId = item.id_producto || item.id_color || item.id_cliente;
                                    }

                                    div.textContent = displayText;
                                    div.dataset.id = selectedId;

                                    div.onclick = () => {
                                        inputElement.value = displayText;
                                        hiddenField.value = selectedId;
                                        console.log(`Seleccionado: ${displayText}, ID: ${selectedId}, Hidden field value: ${hiddenField.name}=${hiddenField.value}`);

                                        // Si es un producto, rellenar el precio de venta sugerido
                                        if (endpoint.includes('buscar_productos.php') && extraField && item.precio_venta) {
                                            extraField.value = parseFloat(item.precio_venta).toFixed(2);
                                        }

                                        resultContainer.innerHTML = '';
                                        resultContainer.style.display = 'none';
                                    };
                                    resultContainer.appendChild(div);
                                });
                                resultContainer.style.display = 'block';
                            } else {
                                resultContainer.innerHTML = '<div class="list-group-item text-muted py-2">No se encontraron resultados</div>';
                                resultContainer.style.display = 'block';
                            }
                        })
                        .catch(err => {
                            console.error('Error en la búsqueda AJAX:', err);
                            resultContainer.innerHTML = '<div class="list-group-item text-danger py-2">Error al cargar datos.</div>';
                            resultContainer.style.display = 'block';
                            hiddenField.value = '';
                            if (extraField) {
                                extraField.value = '';
                            }
                        });
                }, 300);
            } else {
                resultContainer.innerHTML = '';
                resultContainer.style.display = 'none';
                hiddenField.value = '';
                if (extraField) {
                    extraField.value = '';
                }
            }
        });

        // Limpiar resultados y ocultar cuando se pierde el foco del input
        inputElement.addEventListener('blur', function() {
            setTimeout(() => {
                // Solo ocultar si el foco no se ha movido a un elemento dentro del contenedor de resultados
                if (!resultContainer.contains(document.activeElement) && document.activeElement !== inputElement) {
                    resultContainer.innerHTML = '';
                    resultContainer.style.display = 'none';
                }
            }, 150);
        });

        // Limpiar el hiddenField si el input visible se edita manualmente después de una selección
        inputElement.addEventListener('change', function() {
            const currentInputValue = this.value.trim();
            // Buscar si el valor actual coincide con un resultado previamente seleccionado
            const matchingResultDiv = resultContainer.querySelector(`div[data-id="${hiddenField.value}"]`);
            if (hiddenField.value && (!matchingResultDiv || matchingResultDiv.textContent.trim() !== currentInputValue)) {
                hiddenField.value = '';
                console.log(`Hidden field ${hiddenField.name} cleared. Input changed to: ${currentInputValue}`);
                if (endpoint.includes('buscar_productos.php') && extraField) {
                     extraField.value = '';
                }
            }
            // Si el input se vacía, también se debe vaciar el hiddenField
            if (currentInputValue === '') {
                hiddenField.value = '';
                console.log(`Hidden field ${hiddenField.name} cleared because input is empty.`);
                if (endpoint.includes('buscar_productos.php') && extraField) {
                     extraField.value = '';
                }
            }
        });

        resultContainer.style.display = 'none';
    }

    // --- Función para inicializar los autocompletes y tallas de un bloque de producto de venta ---
    function inicializarProductoVentaAutocompletes(productoDiv) {
        const productoInput = productoDiv.querySelector('.productoInput');
        const resultadosProductos = productoDiv.querySelector('.resultadosProductos');
        const productoId = productoDiv.querySelector('.productoId');
        const precioVentaField = productoDiv.querySelector('.precioVenta');

        initAutocomplete(
            productoInput,
            resultadosProductos,
            '/controllers/ventas/buscar_productos.php', // Ruta absoluta para AJAX
            productoId,
            precioVentaField
        );

        const colorInput = productoDiv.querySelector('.colorInput');
        const resultadosColores = productoDiv.querySelector('.resultadosColores');
        const colorId = productoDiv.querySelector('.colorId');

        initAutocomplete(
            colorInput,
            resultadosColores,
            '/controllers/ventas/buscar_colores.php', // Ruta absoluta para AJAX
            colorId
        );

        const tallaSelect = productoDiv.querySelector('.talla-select');
        if (tallaSelect && tallaSelect.options.length <= 1) { // Asegura no duplicar si ya tiene opciones
            tallaSelect.insertAdjacentHTML('beforeend', tallasOptions);
        }
    }

    // --- Función para agregar un nuevo bloque de producto de venta dinámicamente ---
    function agregarProducto() {
        const productosContainer = document.getElementById('productos');
        const div = document.createElement('div');
        div.className = 'col-12 mb-4 producto-item'; // Clases de Bootstrap

        div.innerHTML = `
            <div class="card bg-light border shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label class="form-label"><i class="fas fa-tag me-2"></i>Producto:</label>
                            <input type="text" class="form-control productoInput" placeholder="Escribe al menos 3 letras del producto..." autocomplete="off" required>
                            <input type="hidden" name="producto_id[]" class="productoId">
                            <div class="resultadosProductos autocomplete list-group mt-1 position-absolute w-100 shadow-sm"></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Venta (unidad):</label>
                            <input type="number" name="precio_venta[]" class="form-control precioVenta" step="0.01" placeholder="Precio sugerido" required>
                        </div>
                        <div class="col-md-4 position-relative">
                            <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label>
                            <input type="text" class="form-control colorInput" placeholder="Escribe al menos 3 letras del color..." autocomplete="off" required>
                            <input type="hidden" name="color_id[]" class="colorId">
                            <div class="resultadosColores autocomplete list-group mt-1 position-absolute w-100 shadow-sm"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label>
                            <select name="talla_id[]" class="form-select talla-select" required>
                                <option value="">Seleccionar talla</option>
                                ${tallasOptions}
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label>
                            <input type="number" name="cantidad[]" min="1" class="form-control" placeholder="Cantidad" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove()">
                            <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                        </button>
                    </div>
                </div>
            </div>
        `;
        productosContainer.appendChild(div);
        inicializarProductoVentaAutocompletes(div);
    }

    // --- DOMContentLoaded: Se ejecuta cuando el HTML está completamente cargado ---
    document.addEventListener('DOMContentLoaded', function () {
        // Inicializar búsqueda para el campo de cliente principal
        const clienteInput = document.getElementById('clienteInput');
        const resultadosClientes = document.getElementById('resultadosClientes');
        const clienteId = document.getElementById('clienteId');
        initAutocomplete(clienteInput, resultadosClientes, '/controllers/ventas/buscar_clientes.php', clienteId);

        // Inicializar autocompletes para el primer bloque de producto que ya está en el HTML
        document.querySelectorAll('.producto-item').forEach(inicializarProductoVentaAutocompletes);

        // Asignar la función agregarProducto al botón principal "Agregar Otro Producto"
        document.getElementById('btnAgregarProducto').addEventListener('click', agregarProducto);
    });
</script>