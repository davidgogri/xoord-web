function agregarProducto() {
    const div = document.createElement('div');
    div.className = 'producto';

    const selectProducto = document.createElement('select');
    selectProducto.name = 'producto_id[]';
    selectProducto.required = true;
    selectProducto.innerHTML = `
        <option value="">Seleccionar producto</option>
        <?php
        $stmt = $pdo->query("SELECT id_producto, nombre FROM productos");
        while ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$p['id_producto']}'>{$p['nombre']}</option>";
        }
        ?>
    `;

    const selectColor = document.createElement('select');
    selectColor.name = 'color_id[]';
    selectColor.required = true;
    selectColor.innerHTML = `
        <option value="">Seleccionar color</option>
        <?php
        $stmt = $pdo->query("SELECT id_color, nombre_color FROM colores");
        while ($c = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$c['id_color']}'>{$c['nombre_color']}</option>";
        }
        ?>
    `;

    const selectTalla = document.createElement('select');
    selectTalla.name = 'talla_id[]';
    selectTalla.required = true;
    selectTalla.innerHTML = `
        <option value="">Seleccionar talla</option>
        <?php
        $stmt = $pdo->query("SELECT id_talla, nombre_talla FROM tallas");
        while ($t = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$t['id_talla']}'>{$t['nombre_talla']}</option>";
        }
        ?>
    `;

    const inputCantidad = document.createElement('input');
    inputCantidad.type = 'number';
    inputCantidad.name = 'cantidad[]';
    inputCantidad.min = 1;
    inputCantidad.placeholder = 'Cantidad';

    const btnEliminar = document.createElement('button');
    btnEliminar.type = 'button';
    btnEliminar.textContent = 'Eliminar';
    btnEliminar.onclick = () => div.remove();

    div.appendChild(selectProducto);
    div.appendChild(selectColor);
    div.appendChild(selectTalla);
    div.appendChild(inputCantidad);
    div.appendChild(btnEliminar);

    document.getElementById('productos').appendChild(div);
}