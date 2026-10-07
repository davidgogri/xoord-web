<?php
// Habilitar la visualización de errores para depuración (desactivar en un entorno de producción)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Incluir el header.php. Asumimos que este archivo:
// - Inicia la sesión (session_start())
// - Realiza la conexión a la base de datos y la almacena en $pdo (require_once '../../includes/db.php';)
// - Contiene el HTML inicial y la inclusión de Bootstrap y Font Awesome CSS
//
// NOTA IMPORTANTE PARA LAS RUTAS:
// Si 'maka-store' es la raíz de tu sitio (public_html para el subdominio),
// y tu archivo nueva_compra.php está en 'maka-store/views/compras/nueva_compra.php',
// entonces para ir a 'maka-store/partials/header.php' necesitas '../../partials/header.php'.
include __DIR__ . '/../../partials/header.php'; //

// Verificar la sesión del usuario
if (!isset($_SESSION['usuario_id'])) { //
    header("Location: ../../login.php"); // Asumiendo login.php está en maka-store/login.php
    exit; //
}

// --- Consulta para obtener los proveedores activos ---
$proveedores = []; // Inicializar como array vacío
try {
    $stmt = $pdo->query("SELECT id_proveedor, nombre_empresa FROM proveedores WHERE estado = 'activo' ORDER BY nombre_empresa ASC"); //
    $proveedores = $stmt->fetchAll(PDO::FETCH_ASSOC); //
} catch (PDOException $e) {
    // Manejo de error si la consulta falla
    $error_proveedores = "Error al cargar proveedores: " . $e->getMessage(); //
    error_log("Error al cargar proveedores: " . $e->getMessage()); // Loguear el error en el servidor
}

// --- Consulta para obtener todas las tallas disponibles ---
$tallas = []; // Inicializar como array vacío
try {
    $stmtTallas = $pdo->query("SELECT id_talla, nombre_talla FROM tallas ORDER BY nombre_talla ASC"); //
    $tallas = $stmtTallas->fetchAll(PDO::FETCH_ASSOC); //
} catch (PDOException $e) {
    // Manejo de error si la consulta falla
    $error_tallas = "Error al cargar tallas: " . $e->getMessage(); //
    error_log("Error al cargar tallas: " . $e->getMessage()); // Loguear el error en el servidor
}

// --- NUEVO: Consulta para obtener los puntos de venta activos ---
$puntos_venta = [];
try {
    $stmtPuntosVenta = $pdo->query("SELECT id_punto_venta, nombre_punto as nombre FROM puntos_venta WHERE estado = 'activo' ORDER BY nombre ASC");
    $puntos_venta = $stmtPuntosVenta->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_puntos_venta = "Error al cargar puntos de venta: " . $e->getMessage();
    error_log("Error al cargar puntos de venta: " . $e->getMessage());
}

?>

<div class="container mt-4">
    <h1 class="mb-4 text-primary text-center"><i class="fas fa-box-open me-2"></i> Registrar Nueva Compra</h1> <p class="text-muted text-center">Completa el formulario para añadir nuevos productos al inventario.</p> <div class="card shadow-lg mb-5 border-success"> <div class="card-header bg-success text-white"> <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i> Detalles Generales de la Compra</h5> </div>
        <div class="card-body"> <form id="formCompra" action="../../controllers/compras/registrar_compra.php" method="POST"> <div class="row g-3"> <div class="col-md-6 mb-3"> <label for="proveedor" class="form-label"><i class="fas fa-truck me-2"></i>Proveedor:</label> <select name="id_proveedor" id="proveedor" class="form-select" required> <option value="" disabled selected>Seleccionar proveedor</option> <?php if (isset($error_proveedores)): ?> <option value="" disabled><?php echo htmlspecialchars($error_proveedores); ?></option> <?php else: ?> <?php foreach ($proveedores as $proveedor): ?> <option value="<?php echo htmlspecialchars($proveedor['id_proveedor']); ?>"> <?php echo htmlspecialchars($proveedor['nombre_empresa']); ?> </option>
                                <?php endforeach; ?> <?php endif; ?> </select>
                    </div>

                    <div class="col-md-6 mb-3">
                        <label for="punto_venta" class="form-label"><i class="fas fa-store me-2"></i>Punto de Venta:</label>
                        <select name="id_punto_venta" id="punto_venta" class="form-select" required>
                            <option value="" disabled selected>Seleccionar punto de venta</option>
                            <?php if (isset($error_puntos_venta)): ?>
                                <option value="" disabled><?php echo htmlspecialchars($error_puntos_venta); ?></option>
                            <?php else: ?>
                                <?php foreach ($puntos_venta as $punto): ?>
                                    <option value="<?php echo htmlspecialchars($punto['id_punto_venta']); ?>">
                                        <?php echo htmlspecialchars($punto['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div class="col-md-6 mb-3"> <label for="metodo_pago" class="form-label"><i class="fas fa-wallet me-2"></i>Método de Pago:</label> <select name="metodo_pago" id="metodo_pago" class="form-select" required> <option value="" disabled selected>Seleccionar método de pago</option> <option value="Efectivo">Efectivo</option> <option value="Transferencia">Transferencia</option> <option value="Crédito">Crédito</option> </select>
                    </div>
                </div>

                <hr class="my-5"> <h4 class="mb-4 text-success"><i class="fas fa-cubes me-2"></i> Productos de la Compra</h4> <div id="productos" class="row"> <div class="col-12 mb-4 producto-item"> <div class="card bg-light border shadow-sm"> <div class="card-body"> <div class="row g-3"> <div class="col-md-6 position-relative"> <label class="form-label"><i class="fas fa-tag me-2"></i>Producto:</label> <input type="text" class="form-control productoInput" placeholder="Escribe al menos 3 letras del producto..." autocomplete="off" required> <input type="hidden" name="producto_id[]" class="productoId"> <div class="resultadosProductos autocomplete list-group mt-1 position-absolute w-100 z-index-1050 shadow-sm"></div> </div>

                                    <div class="col-md-6 position-relative"> <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label> <input type="text" class="form-control colorInput" placeholder="Escribe al menos 3 letras del color..." autocomplete="off" required> <input type="hidden" name="color_id[]" class="colorId"> <div class="resultadosColores autocomplete list-group mt-1 position-absolute w-100 z-index-1050 shadow-sm"></div> </div>

                                    <div class="col-md-4"> <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label> <select name="talla_id[]" class="form-select talla-select" required> <option value="">Seleccionar talla</option> </select>
                                    </div>

                                    <div class="col-md-4"> <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label> <input type="number" step="1" name="cantidad[]" min="1" class="form-control" placeholder="Cantidad" required> </div>

                                    <div class="col-md-4"> <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Compra (unidad):</label> <input type="number" step="0.01" name="precio_compra[]" class="form-control" placeholder="0.00" required> </div>
                                </div>
                                <div class="d-flex justify-content-end mt-3"> <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove()"> <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between mt-4"> <button type="button" class="btn btn-outline-success" id="btnAgregarProducto"> <i class="fas fa-plus-circle me-2"></i> Agregar Otro Producto
                    </button>
                    <button type="submit" class="btn btn-success"> <i class="fas fa-check-circle me-2"></i> Registrar Compra
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Incluir el footer para cerrar las etiquetas HTML y cargar los scripts de Bootstrap, etc.
// Si 'maka-store' es la raíz de tu sitio (public_html para el subdominio),
// y tu archivo nueva_compra.php está en 'maka-store/views/compras/nueva_compra.php',
// entonces para ir a 'maka-store/partials/footer.php' necesitas '../../partials/footer.php'.
include __DIR__ . '/../../partials/footer.php'; //
?>

<script>
    // --- Variables JS desde PHP (Tallas) ---
    // Esta variable se genera una sola vez en el servidor al cargar la página
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
    `; //

    // --- Función genérica para Autocompletado ---
    function initAutocomplete(inputElement, resultContainer, endpoint, hiddenField) { //
        let currentTimeout = null; //

        inputElement.addEventListener('input', function () { //
            const query = this.value.trim(); //

            if (currentTimeout) { //
                clearTimeout(currentTimeout); //
            }

            if (query.length >= 3) { //
                currentTimeout = setTimeout(() => { //
                    fetch(`${endpoint}?q=${encodeURIComponent(query)}`) //
                        .then(res => { //
                            if (!res.ok) { //
                                // Si la respuesta no es OK (ej. 404, 500), lanzamos un error para el catch
                                console.error(`HTTP error! status: ${res.status} for endpoint: ${endpoint}`); //
                                throw new Error(`HTTP error! status: ${res.status}`); //
                            }
                            return res.json(); //
                        })
                        .then(data => { //
                            resultContainer.innerHTML = ''; // Limpiar resultados anteriores
                            if (data.length > 0) { //
                                data.forEach(item => { //
                                    const div = document.createElement('div'); //
                                    // Clases de Bootstrap para estilo de lista y hover
                                    div.classList.add('list-group-item', 'list-group-item-action', 'py-2'); //

                                    // Determinar qué propiedades usar basado en el endpoint
                                    let displayText = ''; //
                                    let selectedId = ''; //

                                    if (endpoint.includes('buscar_productos.php')) { //
                                        displayText = item.nombre || 'Producto sin nombre'; //
                                        selectedId = item.id_producto; //
                                    } else if (endpoint.includes('buscar_colores.php')) { //
                                        displayText = item.nombre_color || 'Color sin nombre'; //
                                        selectedId = item.id_color; //
                                    } else {
                                        // Fallback por si acaso el endpoint no coincide con los esperados
                                        displayText = item.nombre || item.nombre_color || 'N/A'; //
                                        selectedId = item.id_producto || item.id_color; //
                                    }

                                    div.textContent = displayText; //
                                    // Añadir un data-id al div para referencia, útil para la lógica de limpieza
                                    div.dataset.id = selectedId; //

                                    div.onclick = () => { //
                                        inputElement.value = displayText; // Actualiza el campo visible
                                        hiddenField.value = selectedId;   // Actualiza el campo oculto con el ID
                                        console.log(`Seleccionado: ${displayText}, ID: ${selectedId}, Hidden field value: ${hiddenField.name}=${hiddenField.value}`); // Depuración
                                        resultContainer.innerHTML = '';      // Limpia los resultados
                                        resultContainer.style.display = 'none'; // Oculta el contenedor de resultados
                                    };
                                    resultContainer.appendChild(div); //
                                });
                                resultContainer.style.display = 'block'; // Muestra el contenedor si hay resultados
                            } else {
                                resultContainer.innerHTML = '<div class="list-group-item text-muted py-2">No se encontraron resultados</div>'; //
                                resultContainer.style.display = 'block'; // Muestra el mensaje de "no resultados"
                            }
                        })
                        .catch(err => { //
                            console.error('Error en la búsqueda AJAX:', err); //
                            // Muestra un mensaje de error genérico al usuario
                            resultContainer.innerHTML = '<div class="list-group-item text-danger py-2">Error al cargar datos.</div>'; //
                            resultContainer.style.display = 'block'; //
                            // Asegurarse de limpiar el campo oculto si hubo un error de carga
                            hiddenField.value = ''; //
                        });
                }, 300); // Pequeño retraso para evitar muchas peticiones mientras el usuario escribe
            } else {
                resultContainer.innerHTML = ''; //
                resultContainer.style.display = 'none'; // Oculta si la query es muy corta
                hiddenField.value = ''; // Limpiar el ID si el input se vacía (menos de 3 caracteres)
            }
        });

        // Ocultar resultados si se hace clic fuera del input/resultados
        inputElement.addEventListener('blur', function() { //
            setTimeout(() => { //
                // Verificar si el foco está en el contenedor de resultados o en un elemento dentro de él
                if (!resultContainer.contains(document.activeElement) && document.activeElement !== inputElement) { //
                    resultContainer.innerHTML = ''; //
                    resultContainer.style.display = 'none'; //
                }
            }, 150); // Pequeño retraso para permitir clics en los resultados antes de ocultar
        });

        // Asegurarse de limpiar el campo oculto si el texto visible cambia y no es un valor válido
        // Esto es útil si el usuario escribe algo y luego no selecciona de la lista o edita lo seleccionado
        inputElement.addEventListener('change', function() { //
            const currentInputValue = this.value.trim(); //
            // Buscar si el valor actual del input coincide con algún texto de los resultados cargados previamente
            // que tenga el mismo data-id que el hiddenField.value.
            // Si el hiddenField tiene un valor, pero el input ya no coincide con el texto original de ese ID,
            // o si el input está vacío, limpiar el hiddenField.
            const matchingResultDiv = resultContainer.querySelector(`div[data-id="${hiddenField.value}"]`); //
            if (hiddenField.value && (!matchingResultDiv || matchingResultDiv.textContent.trim() !== currentInputValue)) { //
                hiddenField.value = ''; //
                console.log(`Hidden field ${hiddenField.name} cleared. Input changed to: ${currentInputValue}`); // Depuración
            }
            // Si el input está vacío, siempre limpia el hidden field
            if (currentInputValue === '') { //
                hiddenField.value = ''; //
                console.log(`Hidden field ${hiddenField.name} cleared because input is empty.`); //
            }
        });

        // Asegúrate de que el contenedor de resultados esté oculto por defecto al cargar
        resultContainer.style.display = 'none'; //
    }

    // --- Función para inicializar los autocompletes y tallas de un bloque de producto ---
    function inicializarProductoAutocompletes(productoDiv) { //
        const productoInput = productoDiv.querySelector('.productoInput'); //
        const resultadosProductos = productoDiv.querySelector('.resultadosProductos'); //
        const productoId = productoDiv.querySelector('.productoId'); //

        // RUTAS ABSOLUTAS: Si 'maka-store' es tu public_html, entonces /controllers/ es la ruta correcta desde la raíz del dominio
        initAutocomplete(
            productoInput,
            resultadosProductos,
            '/controllers/general/buscar_productos.php', //
            productoId
        );

        const colorInput = productoDiv.querySelector('.colorInput'); //
        const resultadosColores = productoDiv.querySelector('.resultadosColores'); //
        const colorId = productoDiv.querySelector('.colorId'); //

        // RUTAS ABSOLUTAS
        initAutocomplete(
            colorInput,
            resultadosColores,
            '/controllers/general/buscar_colores.php', //
            colorId
        );

        // Llenar las tallas al agregar un nuevo bloque o al inicializar el primero
        const tallaSelect = productoDiv.querySelector('.talla-select'); //
        // Solo agrega las opciones si el select está vacío (excepto por la opción por defecto 'Seleccionar talla')
        if (tallaSelect && tallaSelect.options.length <= 1) { //
            tallaSelect.insertAdjacentHTML('beforeend', tallasOptions); //
        }
    }

    // --- Función para agregar un nuevo bloque de producto dinámicamente ---
    function agregarProducto() { //
        const productosContainer = document.getElementById('productos'); //
        const div = document.createElement('div'); //
        div.className = 'col-12 mb-4 producto-item'; // Clases para el diseño en grilla y margen

        div.innerHTML = `
            <div class="card bg-light border shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6 position-relative">
                            <label class="form-label"><i class="fas fa-tag me-2"></i>Producto:</label>
                            <input type="text" class="form-control productoInput" placeholder="Escribe al menos 3 letras del producto..." autocomplete="off" required>
                            <input type="hidden" name="producto_id[]" class="productoId">
                            <div class="resultadosProductos autocomplete list-group mt-1 position-absolute w-100 z-index-1050 shadow-sm"></div>
                        </div>
                        <div class="col-md-6 position-relative">
                            <label class="form-label"><i class="fas fa-palette me-2"></i>Color:</label>
                            <input type="text" class="form-control colorInput" placeholder="Escribe al menos 3 letras del color..." autocomplete="off" required>
                            <input type="hidden" name="color_id[]" class="colorId">
                            <div class="resultadosColores autocomplete list-group mt-1 position-absolute w-100 z-index-1050 shadow-sm"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-ruler-horizontal me-2"></i>Talla:</label>
                            <select name="talla_id[]" class="form-select talla-select" required>
                                <option value="">Seleccionar talla</option>
                                </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-sort-numeric-up me-2"></i>Cantidad:</label>
                            <input type="number" step="1" name="cantidad[]" min="1" class="form-control" placeholder="Cantidad" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label"><i class="fas fa-dollar-sign me-2"></i>Precio de Compra (unidad):</label>
                            <input type="number" step="0.01" name="precio_compra[]" class="form-control" placeholder="0.00" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-eliminar-producto" onclick="this.closest('.producto-item').remove()">
                            <i class="fas fa-trash-alt me-2"></i> Eliminar Producto
                        </button>
                    </div>
                </div>
            </div>
        `; //
        productosContainer.appendChild(div); //
        inicializarProductoAutocompletes(div); // Inicializar autocompletes para el nuevo bloque
    }

    // --- DOMContentLoaded: Se ejecuta cuando el HTML está completamente cargado ---
    document.addEventListener('DOMContentLoaded', function () { //
        // Inicializar los autocompletes para el primer bloque de producto que ya está en el HTML
        document.querySelectorAll('.producto-item').forEach(inicializarProductoAutocompletes); //

        // Asignar la función agregarProducto al botón principal "Agregar Otro Producto"
        document.getElementById('btnAgregarProducto').addEventListener('click', agregarProducto); //
    });
</script>