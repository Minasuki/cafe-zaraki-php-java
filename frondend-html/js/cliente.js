// js/cliente.js - Lógica de la página del cliente

// Estado del carrito
let productos = [];
let carrito = [];  // [{ product_id, name, unit_price, quantity }]

// ==== Inicialización ====
document.addEventListener('DOMContentLoaded', async () => {
    await cargarProductos();
    configurarEventos();
});

// ==== Cargar productos desde el API ====
async function cargarProductos() {
    try {
        productos = await api.getProducts();
        renderCategorias();
        renderProductos(productos);
    } catch (error) {
        document.getElementById('productos-grid').innerHTML =
            '<p class="vacio">Error al cargar el menú. Verifica que el servidor esté corriendo.</p>';
        showToast('No se pudo cargar el menú', 'error');
    }
}

// ==== Renderizar categorías únicas ====
function renderCategorias() {
    const categorias = [...new Set(productos.map(p => p.category).filter(Boolean))];
    const contenedor = document.getElementById('categorias');

    categorias.forEach(cat => {
        const btn = document.createElement('button');
        btn.className = 'cat-btn';
        btn.dataset.categoria = cat;
        btn.textContent = cat;
        contenedor.appendChild(btn);
    });

    // Evento para los botones de categoría
    contenedor.addEventListener('click', (e) => {
        if (!e.target.classList.contains('cat-btn')) return;

        // Actualizar active
        contenedor.querySelectorAll('.cat-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');

        const categoria = e.target.dataset.categoria;
        const filtrados = categoria === 'todas'
            ? productos
            : productos.filter(p => p.category === categoria);

        renderProductos(filtrados);
    });
}

// ==== Renderizar productos ====
function renderProductos(lista) {
    const grid = document.getElementById('productos-grid');

    if (lista.length === 0) {
        grid.innerHTML = '<p class="vacio">No hay productos en esta categoría</p>';
        return;
    }

    grid.innerHTML = lista.map(p => `
        <div class="producto">
            <span class="categoria-tag">${p.category || 'General'}</span>
            <h3>${p.name}</h3>
            <p class="descripcion">${p.description || ''}</p>
            <div class="precio">${formatPrice(p.price)}</div>
            <button class="btn-primary" onclick="agregarAlCarrito(${p.id})">
                Agregar al carrito
            </button>
        </div>
    `).join('');
}

// ==== Agregar al carrito ====
function agregarAlCarrito(productId) {
    const producto = productos.find(p => p.id === productId);
    if (!producto) return;

    const existente = carrito.find(item => item.product_id === productId);

    if (existente) {
        existente.quantity++;
    } else {
        carrito.push({
            product_id: producto.id,
            name: producto.name,
            unit_price: parseFloat(producto.price),
            quantity: 1,
        });
    }

    renderCarrito();
    showToast(`${producto.name} agregado`, 'success');
}

// ==== Cambiar cantidad ====
function cambiarCantidad(productId, delta) {
    const item = carrito.find(i => i.product_id === productId);
    if (!item) return;

    item.quantity += delta;

    if (item.quantity <= 0) {
        carrito = carrito.filter(i => i.product_id !== productId);
    }

    renderCarrito();
}

// ==== Renderizar carrito ====
function renderCarrito() {
    const contenedor = document.getElementById('carrito-items');
    const contador = document.getElementById('contador-carrito');
    const totalEl = document.getElementById('total-carrito');

    // Contador
    const totalItems = carrito.reduce((sum, i) => sum + i.quantity, 0);
    contador.textContent = totalItems;

    // Items
    if (carrito.length === 0) {
        contenedor.innerHTML = '<p class="vacio">Aún no has agregado nada</p>';
    } else {
        contenedor.innerHTML = carrito.map(item => `
            <div class="item-carrito">
                <div class="item-carrito-info">
                    <strong>${item.name}</strong>
                    <small>${formatPrice(item.unit_price)} c/u</small>
                </div>
                <div class="item-carrito-controles">
                    <button onclick="cambiarCantidad(${item.product_id}, -1)">−</button>
                    <span class="cantidad">${item.quantity}</span>
                    <button onclick="cambiarCantidad(${item.product_id}, 1)">+</button>
                </div>
            </div>
        `).join('');
    }

    // Total
    const total = carrito.reduce((sum, i) => sum + (i.unit_price * i.quantity), 0);
    totalEl.textContent = formatPrice(total);
}

// ==== Enviar pedido ====
async function enviarPedido() {
    const nombre = document.getElementById('nombre-cliente').value.trim();

    if (!nombre) {
        showToast('Escribe tu nombre antes de enviar', 'error');
        return;
    }

    if (carrito.length === 0) {
        showToast('Agrega algo al carrito primero', 'error');
        return;
    }

    const btn = document.getElementById('btn-enviar-pedido');
    btn.disabled = true;
    btn.textContent = 'Enviando...';

    try {
        const orden = {
            customer_name: nombre,
            items: carrito.map(item => ({
                product_id: item.product_id,
                quantity: item.quantity,
                unit_price: item.unit_price,
            })),
        };

        const respuesta = await api.createOrder(orden);
        console.log('✅ Orden creada:', respuesta);

        showToast(`¡Pedido #${respuesta.id} enviado! Lo estamos preparando ☕`, 'success');

        // Limpiar carrito
        carrito = [];
        document.getElementById('nombre-cliente').value = '';
        renderCarrito();
        cerrarCarrito();
    } catch (error) {
        showToast('Error al enviar el pedido', 'error');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Enviar pedido';
    }
}

// ==== Abrir/cerrar carrito ====
function abrirCarrito() {
    document.getElementById('carrito-panel').classList.add('open');
    document.getElementById('overlay').classList.add('show');
}

function cerrarCarrito() {
    document.getElementById('carrito-panel').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}

// ==== Eventos ====
function configurarEventos() {
    document.getElementById('btn-carrito').addEventListener('click', abrirCarrito);
    document.getElementById('cerrar-carrito').addEventListener('click', cerrarCarrito);
    document.getElementById('overlay').addEventListener('click', cerrarCarrito);
    document.getElementById('btn-enviar-pedido').addEventListener('click', enviarPedido);
}