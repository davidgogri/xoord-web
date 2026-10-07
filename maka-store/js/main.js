function agregarProducto() {
    const div = document.createElement('div');
    div.className = 'producto';
    div.innerHTML = `
        <select name="producto_id">
            <option value="">Seleccionar producto</option>
        </select>
        <input type="number" name="cantidad" min="1" placeholder="Cantidad">
        <button type="button" onclick="this.parentNode.remove()">Eliminar</button>
    `;
    document.getElementById('productos').appendChild(div);
}

document.getElementById('ventaForm').addEventListener('submit', function(e) {
    e.preventDefault();
    alert('Venta registrada (simulación)');
});