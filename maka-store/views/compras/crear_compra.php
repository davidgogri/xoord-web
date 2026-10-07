<?php
session_start();
// Asegúrate de que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php"); // Redirige al login si no hay sesión
    exit();
}

// Incluye tu cabecera y pie de página como lo haces normalmente
include_once '../../partials/header.php';
// Aquí puedes incluir cualquier otro archivo que necesites para la vista,
// pero los datos para los selectores los cargaremos con AJAX.

$id_usuario_actual = $_SESSION['user_id'] ?? 1; // Ajusta según tu lógica real de sesión
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h2>Registrar Nueva Compra de Productos</h2>
        </div>
        <div class="card-body">
            <form id="formNuevaCompra">
                <input type="hidden" name="id_usuario" value="<?php echo $id_usuario_actual; ?>">

                <div class="mb-3">
                    <label for="id_proveedor" class="form-label">Proveedor:</label>
                    <select class="form-control" id="id_proveedor" name="id_proveedor" required>
                        <option value="">Cargando proveedores...</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="metodo_pago" class="form-label">Método de Pago:</label>
                    <select class="form-control" id="metodo_pago" name="metodo_pago" required>
                        <option value="">Seleccione un método</option>
                        <option value="Efectivo">Efectivo</option>
                        <option value="Tarjeta de Crédito">Tarjeta de Crédito</option>
                        <option value="Tarjeta de Débito">Tarjeta de Débito</option>
                        <option value="Transferencia Bancaria">Transferencia Bancaria</option>
                        <option value="Credito">Crédito</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label for="id_punto_venta" class="form-label">Punto de Venta (Almacén):</label>
                    <select class="form-control" id="id_punto_venta" name="id_punto_venta" required>
                        <option value="">Cargando puntos de venta...</option>
                    </select>
                </div>

                <hr>
                <h4>Detalles de Productos</h4>
                <div id="productos_container">
                    <div class="row mb-3 product-item" data-index="0">
                        <div class="col-md-3">
                            <label for="producto_0" class="form-label">Producto:</label>
                            <select class="form-control producto-select" name="productos[0][id_producto]" required>
                                <option value="">Cargando productos...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="color_0" class="form-label">Color:</label>
                            <select class="form-control color-select" name="productos[0][id_color]" required>
                                <option value="">Cargando colores...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="talla_0" class="form-label">Talla:</label>
                            <select class="form-control talla-select" name="productos[0][id_talla]" required>
                                <option value="">Cargando tallas...</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="cantidad_0" class="form-label">Cantidad:</label>
                            <input type="number" class="form-control cantidad-input" name="productos[0][cantidad]" min="1" value="1" required>
                        </div>
                        <div class="col-md-2">
                            <label for="precio_compra_0" class="form-label">Precio Compra Unit.:</label>
                            <input type="number" class="form-control precio-compra-input" name="productos[0][precio_compra]" step="0.01" min="0.01" required>
                        </div>
                        <div class="col-md-1 d-flex align-items-end">
                            <button type="button" class="btn btn-danger remove-product-btn">X</button>
                        </div>
                    </div>
                </div>

                <button type="button" class="btn btn-secondary mb-3" id="add_product_btn">Añadir Otro Producto</button>

                <div class="mb-3">
                    <label for="total_compra" class="form-label">Total de la Compra:</label>
                    <input type="text" class="form-control" id="total_compra" name="total_compra" readonly value="0.00">
                </div>

                <button type="submit" class="btn btn-success">Registrar Compra</button>
            </form>
        </div>
    </div>
</div>

<?php include_once '../../partials/footer.php'; // Incluye tu pie de página ?>

<script src="../../js/jquery.min.js"></script>
<script src="../../js/bootstrap.bundle.min.js"></script>
<script>
    $(document).ready(function() {
        let productIndex = 1; // Para el índice de los nuevos productos

        // Variables para almacenar los datos cargados por AJAX
        let allProveedores = [];
        let allPuntosVenta = [];
        let allProductos = [];
        let allColores = [];
        let allTallas = [];

        // Función para cargar datos de selectores
        function loadSelectOptions(selector, url, idField, nameField, callback = null, extraData = null) {
            $.ajax({
                url: url,
                type: 'GET',
                dataType: 'json',
                success: function(data) {
                    let options = '<option value="">Seleccione...</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item[idField]}"`;
                        if (extraData) {
                            for (const key in extraData) {
                                if (item[extraData[key]]) {
                                    options += ` data-${key}="${item[extraData[key]]}"`;
                                }
                            }
                        }
                        options += `>${item[nameField]}</option>`;
                    });
                    $(selector).html(options);
                    if (callback) callback(data);
                },
                error: function(xhr, status, error) {
                    console.error(`Error cargando ${url}:`, status, error);
                    $(selector).html('<option value="">Error al cargar</option>');
                }
            });
        }

        // Cargar todos los datos iniciales
        loadSelectOptions('#id_proveedor', '../../controllers/compras/buscar_proveedores.php', 'id_proveedor', 'nombre', function(data){ allProveedores = data; });
        loadSelectOptions('#id_punto_venta', '../../controllers/general/buscar_puntos_venta.php', 'id_punto_venta', 'nombre_punto', function(data){ allPuntosVenta = data; }); // Asumiendo que tienes un controlador para puntos de venta en general
        loadSelectOptions('.producto-select', '../../controllers/compras/buscar_productos.php', 'id_producto', 'nombre', function(data){ allProductos = data; }, { 'precio-compra': 'precio_compra' });
        loadSelectOptions('.color-select', '../../controllers/compras/buscar_colores.php', 'id_color', 'nombre_color', function(data){ allColores = data; });
        loadSelectOptions('.talla-select', '../../controllers/compras/buscar_tallas.php', 'id_talla', 'nombre_talla', function(data){ allTallas = data; });


        // Función para calcular el total de la compra
        function calculateTotal() {
            let total = 0;
            $('.product-item').each(function() {
                const cantidad = parseFloat($(this).find('.cantidad-input').val()) || 0;
                const precioCompra = parseFloat($(this).find('.precio-compra-input').val()) || 0;
                total += (cantidad * precioCompra);
            });
            $('#total_compra').val(total.toFixed(2));
        }

        // Inicializar el total al cargar la página
        calculateTotal();

        // Evento para añadir un nuevo producto
        $('#add_product_btn').on('click', function() {
            const newProductItem = `
                <div class="row mb-3 product-item" data-index="${productIndex}">
                    <div class="col-md-3">
                        <label for="producto_${productIndex}" class="form-label">Producto:</label>
                        <select class="form-control producto-select" name="productos[${productIndex}][id_producto]" required>
                            <option value="">Seleccione producto</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="color_${productIndex}" class="form-label">Color:</label>
                        <select class="form-control color-select" name="productos[${productIndex}][id_color]" required>
                                <option value="">Seleccione color</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="talla_${productIndex}" class="form-label">Talla:</label>
                        <select class="form-control talla-select" name="productos[${productIndex}][id_talla]" required>
                            <option value="">Seleccione talla</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="cantidad_${productIndex}" class="form-label">Cantidad:</label>
                        <input type="number" class="form-control cantidad-input" name="productos[${productIndex}][cantidad]" min="1" value="1" required>
                    </div>
                    <div class="col-md-2">
                        <label for="precio_compra_${productIndex}" class="form-label">Precio Compra Unit.:</label>
                        <input type="number" class="form-control precio-compra-input" name="productos[${productIndex}][precio_compra]" step="0.01" min="0.01" required>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-danger remove-product-btn">X</button>
                    </div>
                </div>
            `;
            $('#productos_container').append(newProductItem);

            // Poblar los nuevos selectores con los datos ya cargados
            const $newProductItem = $(`div.product-item[data-index="${productIndex}"]`);
            populateSelect($newProductItem.find('.producto-select'), allProductos, 'id_producto', 'nombre', { 'precio-compra': 'precio_compra' });
            populateSelect($newProductItem.find('.color-select'), allColores, 'id_color', 'nombre_color');
            populateSelect($newProductItem.find('.talla-select'), allTallas, 'id_talla', 'nombre_talla');

            productIndex++;
            calculateTotal();
        });

        // Función auxiliar para poblar un select con datos JS (ya cargados)
        function populateSelect($selectElement, data, idField, nameField, extraData = null) {
            let options = '<option value="">Seleccione...</option>';
            $.each(data, function(index, item) {
                options += `<option value="${item[idField]}"`;
                if (extraData) {
                    for (const key in extraData) {
                        if (item[extraData[key]]) {
                            options += ` data-${key}="${item[extraData[key]]}"`;
                        }
                    }
                }
                options += `>${item[nameField]}</option>`;
            });
            $selectElement.html(options);
        }


        // Evento para eliminar un producto (delegación de eventos)
        $('#productos_container').on('click', '.remove-product-btn', function() {
            $(this).closest('.product-item').remove();
            calculateTotal();
        });

        // Evento para actualizar el precio de compra unitario al seleccionar un producto
        $('#productos_container').on('change', '.producto-select', function() {
            const selectedOption = $(this).find('option:selected');
            const precioCompra = selectedOption.data('precio-compra');
            $(this).closest('.product-item').find('.precio-compra-input').val(precioCompra);
            calculateTotal();
        });

        // Eventos para recalcular el total al cambiar cantidad o precio
        $('#productos_container').on('input', '.cantidad-input, .precio-compra-input', function() {
            calculateTotal();
        });


        // Manejar el envío del formulario
        $('#formNuevaCompra').on('submit', function(e) {
            e.preventDefault();

            if ($('.product-item').length === 0) {
                alert('Debe añadir al menos un producto a la compra.');
                return;
            }

            const formData = $(this).serializeArray();
            let mainData = {};
            let productsArray = [];

            formData.forEach(function(item) {
                const match = item.name.match(/productos\[(\d+)\]\[(\w+)\]/);
                if (match) {
                    const index = match[1];
                    const field = match[2];
                    if (!productsArray[index]) {
                        productsArray[index] = {};
                    }
                    productsArray[index][field] = item.value;
                } else {
                    mainData[item.name] = item.value;
                }
            });

            productsArray = productsArray.filter(item => item !== null && typeof item === 'object');
            mainData['productos'] = productsArray;

            const jsonData = JSON.stringify(mainData);

            $.ajax({
                url: '../../controllers/compras/registrar_compra.php', // Apunta a tu controlador registrar_compra.php
                type: 'POST',
                contentType: 'application/json',
                data: jsonData,
                success: function(response) {
                    if (response.success) {
                        alert('Compra registrada con éxito. ID de Compra: ' + response.id_compra);
                        $('#formNuevaCompra')[0].reset();
                        // Resetear el contenedor de productos con un solo item vacío
                        $('#productos_container').html(`
                            <div class="row mb-3 product-item" data-index="0">
                                <div class="col-md-3">
                                    <label for="producto_0" class="form-label">Producto:</label>
                                    <select class="form-control producto-select" name="productos[0][id_producto]" required>
                                        <option value="">Cargando productos...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="color_0" class="form-label">Color:</label>
                                    <select class="form-control color-select" name="productos[0][id_color]" required>
                                            <option value="">Cargando colores...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="talla_0" class="form-label">Talla:</label>
                                    <select class="form-control talla-select" name="productos[0][id_talla]" required>
                                        <option value="">Cargando tallas...</option>
                                    </select>
                                </div>
                                <div class="col-md-2">
                                    <label for="cantidad_0" class="form-label">Cantidad:</label>
                                    <input type="number" class="form-control cantidad-input" name="productos[0][cantidad]" min="1" value="1" required>
                                </div>
                                <div class="col-md-2">
                                    <label for="precio_compra_0" class="form-label">Precio Compra Unit.:</label>
                                    <input type="number" class="form-control precio-compra-input" name="productos[0][precio_compra]" step="0.01" min="0.01" required>
                                </div>
                                <div class="col-md-1 d-flex align-items-end">
                                    <button type="button" class="btn btn-danger remove-product-btn">X</button>
                                </div>
                            </div>
                        `);
                        productIndex = 1; // Resetear el contador de índice

                        // Recargar las opciones para el primer item después de resetear
                        populateSelect($('div.product-item[data-index="0"]').find('.producto-select'), allProductos, 'id_producto', 'nombre', { 'precio-compra': 'precio_compra' });
                        populateSelect($('div.product-item[data-index="0"]').find('.color-select'), allColores, 'id_color', 'nombre_color');
                        populateSelect($('div.product-item[data-index="0"]').find('.talla-select'), allTallas, 'id_talla', 'nombre_talla');

                        calculateTotal();
                    } else {
                        alert('Error al registrar la compra: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error AJAX:', status, error);
                    alert('Ocurrió un error en la comunicación con el servidor. Consulta la consola para más detalles.');
                }
            });
        });

        // Asegurarse de que el primer conjunto de selectores se pueble al cargar
        // ya que la función `loadSelectOptions` se encarga de esto.
        // Solo necesitamos asegurar que el select de producto para el item 0 se puebla si los datos ya están disponibles.
        if (allProductos.length > 0) { // Si los datos ya se cargaron (poco probable al inicio)
            populateSelect($('div.product-item[data-index="0"]').find('.producto-select'), allProductos, 'id_producto', 'nombre', { 'precio-compra': 'precio_compra' });
        }
        if (allColores.length > 0) {
            populateSelect($('div.product-item[data-index="0"]').find('.color-select'), allColores, 'id_color', 'nombre_color');
        }
        if (allTallas.length > 0) {
            populateSelect($('div.product-item[data-index="0"]').find('.talla-select'), allTallas, 'id_talla', 'nombre_talla');
        }

    });
</script>
</body>
</html>