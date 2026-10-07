<?php
// views/inventario/ajustar_inventario.php

// --- BLOQUE DE DEPURACIÓN (REMOVER EN PRODUCCIÓN) ---
// Habilita la visualización de errores de PHP
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Revisa si ya hay una sesión iniciada. Si no, la inicia.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluye la conexión a la base de datos y la validación de sesión
// Las rutas están ajustadas para ir un nivel arriba desde 'views/inventario'
// Si la estructura es diferente, por favor ajusta estas rutas
$db_path = __DIR__ . '/../includes/db.php';
$auth_path = __DIR__ . '/../includes/auth_check.php';

// Verificar si los archivos existen antes de incluirlos para evitar un error 500
if (!file_exists($db_path)) {
    die("Error: El archivo de base de datos no se encontró en la ruta: " . $db_path);
}
if (!file_exists($auth_path)) {
    die("Error: El archivo de autenticación no se encontró en la ruta: " . $auth_path);
}

require_once $db_path;
require_once $auth_path;

// Validar que el usuario tenga el rol de administrador
if ($_SESSION['rol'] !== 'admin') {
    // Redirigir a la página principal si no es administrador
    header("Location: ../../index.php");
    exit;
}

// Obtener los puntos de venta para el menú desplegable
try {
    $stmt_pv = $pdo->query("SELECT id_punto_venta, nombre_punto_venta FROM puntos_venta ORDER BY nombre_punto_venta ASC");
    $puntos_venta = $stmt_pv->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Manejar el error de manera controlada
    error_log("Error al cargar los puntos de venta: " . $e->getMessage());
    $puntos_venta = [];
    echo "<div class='alert alert-danger'>Error al cargar los puntos de venta. Por favor, revisa la conexión a la base de datos.</div>";
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajuste de Inventario</title>
    <!-- Incluye Bootstrap CSS y Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f0f2f5;
            padding: 20px;
        }
        .card {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            padding: 10px 20px;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #004085;
        }
        .alert-container {
            min-height: 40px;
        }
    </style>
</head>
<body>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <div class="card p-4">
                <h3 class="card-title text-center mb-4">Ajuste de Inventario</h3>
                
                <div id="alert-message" class="alert-container"></div>

                <form id="ajuste-form">
                    <!-- Fila 1: Punto de Venta y Producto -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="punto-venta-select" class="form-label">Punto de Venta</label>
                            <select id="punto-venta-select" class="form-select" required>
                                <option value="">Selecciona un punto de venta</option>
                                <?php foreach ($puntos_venta as $pv): ?>
                                    <option value="<?php echo htmlspecialchars($pv['id_punto_venta']); ?>">
                                        <?php echo htmlspecialchars($pv['nombre_punto_venta']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="producto-select" class="form-label">Producto</label>
                            <select id="producto-select" class="form-select" disabled required>
                                <option value="">Selecciona un producto</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 2: Color y Talla -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="color-select" class="form-label">Color</label>
                            <select id="color-select" class="form-select" disabled required>
                                <option value="">Selecciona un color</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="talla-select" class="form-label">Talla</label>
                            <select id="talla-select" class="form-select" disabled required>
                                <option value="">Selecciona una talla</option>
                            </select>
                        </div>
                    </div>

                    <!-- Fila 3: Cantidad a Ajustar y Stock Actual -->
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="ajustar-input" class="form-label">Cantidad a Ajustar</label>
                            <input type="number" id="ajustar-input" class="form-control" value="0" min="-99999" required>
                        </div>
                        <div class="col-md-6">
                            <label for="stock-input" class="form-label">Stock Actual</label>
                            <input type="text" id="stock-input" class="form-control" value="0" readonly>
                        </div>
                    </div>

                    <!-- Fila 4: Observación -->
                    <div class="mb-4">
                        <label for="observacion-textarea" class="form-label">Observación (Motivo del ajuste)</label>
                        <textarea id="observacion-textarea" class="form-control" rows="3" required></textarea>
                    </div>

                    <!-- Botón de Envío -->
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary" id="submit-btn">Realizar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // --- Referencias a los elementos del DOM ---
        const pvSelect = document.getElementById('punto-venta-select');
        const productoSelect = document.getElementById('producto-select');
        const colorSelect = document.getElementById('color-select');
        const tallaSelect = document.getElementById('talla-select');
        const ajustarInput = document.getElementById('ajustar-input');
        const stockInput = document.getElementById('stock-input');
        const observacionTextarea = document.getElementById('observacion-textarea');
        const submitBtn = document.getElementById('submit-btn');
        const alertMessage = document.getElementById('alert-message');
        const form = document.getElementById('ajuste-form');

        // --- Manejadores de eventos de cambio ---
        pvSelect.addEventListener('change', () => {
            const pvId = pvSelect.value;
            if (pvId) {
                fetchProducts(pvId);
            } else {
                // Reiniciar los select si no se selecciona un punto de venta
                productoSelect.innerHTML = '<option value="">Selecciona un producto</option>';
                productoSelect.disabled = true;
                resetFormControls();
            }
        });

        productoSelect.addEventListener('change', () => {
            const productId = productoSelect.value;
            const pvId = pvSelect.value;
            if (productId && pvId) {
                fetchColors(productId, pvId);
            } else {
                resetFormControls();
            }
        });

        colorSelect.addEventListener('change', () => {
            const productId = productoSelect.value;
            const colorId = colorSelect.value;
            const pvId = pvSelect.value;
            if (productId && colorId && pvId) {
                fetchTallas(productId, colorId, pvId);
            } else {
                resetTallaAndStockControls();
            }
        });

        tallaSelect.addEventListener('change', () => {
            const productId = productoSelect.value;
            const colorId = colorSelect.value;
            const tallaId = tallaSelect.value;
            const pvId = pvSelect.value;
            if (productId && colorId && tallaId && pvId) {
                fetchStock(productId, colorId, tallaId, pvId);
            } else {
                stockInput.value = '0';
            }
        });
        
        // --- Manejador de evento del formulario (submit) ---
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';
            alertMessage.innerHTML = '';
            
            // Recolectar datos del formulario
            const data = {
                punto_venta_id: pvSelect.value,
                producto_id: productoSelect.value,
                color_id: colorSelect.value,
                talla_id: tallaSelect.value,
                ajuste_cantidad: ajustarInput.value,
                observacion: observacionTextarea.value
            };

            try {
                // Llamada al controlador para guardar el ajuste
                // Usamos una ruta absoluta para evitar problemas de ruteo
                const response = await fetch('/controllers/inventario/realizar_ajuste_inventario.php', {
                    method: 'POST',
                    body: JSON.stringify(data),
                    headers: { 'Content-Type': 'application/json' }
                });

                const result = await response.json();

                if (!response.ok) {
                    throw new Error(result.error || 'Error al realizar el ajuste.');
                }

                alertMessage.innerHTML = `<div class="alert alert-success">${result.message}</div>`;
                form.reset();
                resetAllControls();
            } catch (error) {
                console.error('Error al enviar el formulario:', error);
                alertMessage.innerHTML = `<div class="alert alert-danger">${error.message || 'Error desconocido al realizar el ajuste.'}</div>`;
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Realizar Ajuste';
            }
        });

        // --- Funciones de Fetch para los controladores ---

        /**
         * @description Fetches products based on the selected sales point.
         * @param {number} pvId The sales point ID.
         */
        function fetchProducts(pvId) {
            productoSelect.innerHTML = '<option value="">Cargando productos...</option>';
            productoSelect.disabled = true;
            resetFormControls();

            // Usar una ruta relativa desde la carpeta 'views/inventario/'
            const url = `../controllers/inventario/buscar_productos.php?id_punto_venta=${pvId}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(products => {
                    productoSelect.innerHTML = '<option value="">Selecciona un producto</option>';
                    products.forEach(product => {
                        const option = document.createElement('option');
                        option.value = product.id_producto;
                        option.textContent = product.nombre_producto;
                        productoSelect.appendChild(option);
                    });
                    productoSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error al cargar productos:', error);
                    alertMessage.innerHTML = `<div class="alert alert-danger">${error.error || 'Error al cargar productos.'}</div>`;
                });
        }
        
        /**
         * @description Fetches colors based on the selected product and sales point.
         * @param {number} productId The product ID.
         * @param {number} pvId The sales point ID.
         */
        function fetchColors(productId, pvId) {
            colorSelect.innerHTML = '<option value="">Cargando colores...</option>';
            colorSelect.disabled = true;
            resetTallaAndStockControls();

            // Usar una ruta relativa desde la carpeta 'views/inventario/'
            const url = `../controllers/inventario/buscar_colores_por_producto.php?id_producto=${productId}&id_punto_venta=${pvId}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(colors => {
                    colorSelect.innerHTML = '<option value="">Selecciona un color</option>';
                    colors.forEach(color => {
                        const option = document.createElement('option');
                        option.value = color.id_color;
                        option.textContent = color.nombre_color;
                        colorSelect.appendChild(option);
                    });
                    colorSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error al cargar colores:', error);
                    alertMessage.innerHTML = `<div class="alert alert-danger">${error.error || 'Error al cargar colores.'}</div>`;
                    colorSelect.innerHTML = '<option value="">Selecciona un color</option>';
                    colorSelect.disabled = true;
                });
        }

        /**
         * @description Fetches sizes based on the selected product, color, and sales point.
         * @param {number} productId The product ID.
         * @param {number} colorId The color ID.
         * @param {number} pvId The sales point ID.
         */
        function fetchTallas(productId, colorId, pvId) {
            tallaSelect.innerHTML = '<option value="">Cargando tallas...</option>';
            tallaSelect.disabled = true;
            stockInput.value = '0';

            // Usar una ruta relativa desde la carpeta 'views/inventario/'
            const url = `../controllers/inventario/buscar_tallas_por_producto.php?id_producto=${productId}&id_color=${colorId}&id_punto_venta=${pvId}`;

            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(tallas => {
                    tallaSelect.innerHTML = '<option value="">Selecciona una talla</option>';
                    tallas.forEach(talla => {
                        const option = document.createElement('option');
                        option.value = talla.id_talla;
                        option.textContent = talla.nombre_talla;
                        tallaSelect.appendChild(option);
                    });
                    tallaSelect.disabled = false;
                })
                .catch(error => {
                    console.error('Error al cargar tallas:', error);
                    alertMessage.innerHTML = `<div class="alert alert-danger">${error.error || 'Error al cargar tallas.'}</div>`;
                    tallaSelect.innerHTML = '<option value="">Selecciona una talla</option>';
                    tallaSelect.disabled = true;
                });
        }
        
        /**
         * @description Fetches current stock based on the selected product, color, size, and sales point.
         * @param {number} productId The product ID.
         * @param {number} colorId The color ID.
         * @param {number} tallaId The size ID.
         * @param {number} pvId The sales point ID.
         */
        function fetchStock(productId, colorId, tallaId, pvId) {
            stockInput.value = 'Cargando...';
            // Usar una ruta relativa desde la carpeta 'views/inventario/'
            const url = `../controllers/inventario/buscar_stock_por_talla.php?id_producto=${productId}&id_color=${colorId}&id_talla=${tallaId}&id_punto_venta=${pvId}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(err => Promise.reject(err));
                    }
                    return response.json();
                })
                .then(stockData => {
                    stockInput.value = stockData.stock || 0;
                })
                .catch(error => {
                    console.error('Error al cargar el stock:', error);
                    alertMessage.innerHTML = `<div class="alert alert-danger">${error.error || 'Error al cargar el stock.'}</div>`;
                    stockInput.value = '0';
                });
        }

        // --- Funciones auxiliares para reiniciar los controles del formulario ---
        function resetFormControls() {
            colorSelect.innerHTML = '<option value="">Selecciona un color</option>';
            colorSelect.disabled = true;
            resetTallaAndStockControls();
        }

        function resetTallaAndStockControls() {
            tallaSelect.innerHTML = '<option value="">Selecciona una talla</option>';
            tallaSelect.disabled = true;
            stockInput.value = '0';
        }
        
        function resetAllControls() {
            productoSelect.innerHTML = '<option value="">Selecciona un producto</option>';
            productoSelect.disabled = true;
            resetFormControls();
        }
    });
</script>

<!-- Scripts de Bootstrap (JS bundle) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
