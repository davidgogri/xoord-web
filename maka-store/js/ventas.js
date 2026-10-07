function initAutocomplete(inputElement, resultContainer, endpoint, callback) {
    inputElement.addEventListener('input', function () {
        const query = this.value.trim();

        if (query.length >= 3) {
            fetch(`${endpoint}?q=${encodeURIComponent(query)}`)
                .then(res => res.json())
                .then(data => {
                    resultContainer.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(item => {
                            const div = document.createElement('div');
                            div.textContent = item.nombre || item.nombre_color || item.nombre_talla || item.nombre_cliente;
                            div.onclick = () => {
                                inputElement.value = item.nombre || item.nombre_color || item.nombre_talla || item.nombre_cliente;
                                callback(item.id);
                                resultContainer.innerHTML = '';
                            };
                            resultContainer.appendChild(div);
                        });
                    } else {
                        resultContainer.innerHTML = '<div>No se encontraron resultados</div>';
                    }
                });
        } else {
            resultContainer.innerHTML = '';
        }
    });
}

// Autocomplete para cliente
const clienteInput = document.getElementById('clienteInput');
const resultadosClientes = document.getElementById('resultadosClientes');

initAutocomplete(clienteInput, resultadosClientes, '/maka-store/controllers/ventas/buscar_clientes.php', function(id) {
    clienteInput.nextElementSibling ? clienteInput.nextElementSibling.remove() : null;
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'id_cliente';
    hiddenInput.value = id;
    clienteInput.after(hiddenInput);
});

// Función general para productos, colores y tallas
function agregarProducto() {
    const div = document.createElement('div');
    div.className = 'producto';

    div.innerHTML = `
        <input type="text" class="productoInput" placeholder="Buscar producto..." autocomplete="off" required>
        <input type="hidden" name="producto_id[]" class="productoId">

        <input type="text" class="colorInput" placeholder="Buscar color..." autocomplete="off" required>
        <input type="hidden" name="color_id[]" class="colorId">

        <input type="text" class="tallaInput" placeholder="Buscar talla..." autocomplete="off" required>
        <input type="hidden" name="talla_id[]" class="tallaId">

        <input type="number" name="cantidad[]" min="1" placeholder="Cantidad" required>
        <button type="button" onclick="this.parentNode.remove()">Eliminar</button>
    `;

    document.getElementById('productos').appendChild(div);

    // Inicializar búsquedas para cada nuevo producto
    const productoInput = div.querySelector('.productoInput');
    const colorInput = div.querySelector('.colorInput');
    const tallaInput = div.querySelector('.tallaInput');

    const productoResultados = document.createElement('div');
    productoResultados.className = 'autocomplete';
    div.insertBefore(productoResultados, productoInput.nextSibling);

    const colorResultados = document.createElement('div');
    colorResultados.className = 'autocomplete';
    div.insertBefore(colorInput.nextSibling, colorInput.nextSibling);

    const tallaResultados = document.createElement('div');
    tallaResultados.className = 'autocomplete';
    div.insertBefore(tallaInput.nextSibling, tallaInput.nextSibling);

    initAutocomplete(productoInput, productoResultados, '/maka-store/controllers/ventas/buscar_productos.php', function(id) {
        div.querySelector('.productoId').value = id;
    });

    initAutocomplete(colorInput, colorResultados, '/maka-store/controllers/ventas/buscar_colores.php', function(id) {
        div.querySelector('.colorId').value = id;
    });

    initAutocomplete(tallaInput, tallaResultados, '/maka-store/controllers/ventas/buscar_tallas.php', function(id) {
        div.querySelector('.tallaId').value = id;
    });
}