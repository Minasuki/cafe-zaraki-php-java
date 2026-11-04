// js/cliente.js

let productos = [];
let carrito = [];

document.addEventListener('DOMContentLoaded', async () => {
    await cargarProductos();
    configurarEventos();
});

async function cargarProductos() {
    try {
        productos = await api.getProducts();
        
        // Simulamos un pequeño retraso (1 seg) para que se vea el Skeleton Loader
        setTimeout(() => {
            renderCategorias();
            renderPopulares();
            renderProductosLista(productos);
        }, 800);

    } catch (error) {
        document.getElementById('productos-lista').innerHTML =
            '<p class="vacio">Error al cargar el menú. Verifica tu conexión.</p>';
        showToast('No se pudo cargar el menú', 'error');
    }
}

// 1. RENDERIZAR CATEGORÍAS CON ICONOS
function renderCategorias() {
    const categorias = [...new Set(productos.map(p => p.category).filter(Boolean))];
    const contenedor = document.getElementById('categorias');
    
    // Diccionario de iconos por categoría
    const iconos = {
        'Bebidas': '☕',
        'Pastelería': '🍰',
        'Panadería': '🥐',
        'General': '🍽️'
    };

    contenedor.innerHTML = `
        <div class="cat-item active" data-categoria="todas">
            <span>✨</span> Todas
        </div>
    ` + categorias.map(cat => `
        <div class="cat-item" data-categoria="${cat}">
            <span>${iconos[cat] || '🍽️'}</span> ${cat}
        </div>
    `).join('');

    contenedor.addEventListener('click', (e) => {
        const btn = e.target.closest('.cat-item');
        if (!btn) return;

        contenedor.querySelectorAll('.cat-item').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        const categoria = btn.dataset.categoria;
        const filtrados = categoria === 'todas'
            ? productos
            : productos.filter(p => p.category === categoria);

        renderProductosLista(filtrados);
    });
}

// 2. RENDERIZAR POPULARES
function renderPopulares() {
    // Tomamos los primeros 4 productos como "Populares"
    const populares = productos.slice(0, 4);
    const contenedor = document.getElementById('populares');

    contenedor.innerHTML = populares.map(p => `
        <div class="popular-card" onclick="agregarAlCarrito(${p.id})">
            <img src="img/${p.id}.jpg" alt="${p.name}" onerror="this.src='https://placehold.co/400x400/2c1e17/ffffff?text=Cafe'">
            <div class="popular-info">
                <h4>${p.name}</h4>
                <div class="precio">${formatPrice(p.price)}</div>
            </div>
        </div>
    `).join('');
}

// 3. RENDERIZAR LISTA DE PRODUCTOS (Tarjeta completa clickeable)
function renderProductosLista(lista) {
    const contenedor = document.getElementById('productos-lista');

    if (lista.length === 0) {
        contenedor.innerHTML = '<p class="vacio">No hay productos en esta categoría</p>';
        return;
    }

    contenedor.innerHTML = lista.map(p => `
        <!-- EL ONCLICK ESTÁ EN TODO EL DIV -->
        <div class="producto-card" onclick="agregarAlCarrito(${p.id})">
            <img src="img/${p.id}.jpg" alt="${p.name}" onerror="this.src='https://placehold.co/400x400/2c1e17/ffffff?text=Cafe'">
            <div class="producto-card-info">
                <h4>${p.name}</h4>
                <div class="precio">${formatPrice(p.price)}</div>
            </div>
            <div class="btn-add-icon">+</div>
        </div>
    `).join('');
}

// 4. LÓGICA DEL CARRITO (Se mantiene igual)
function agregarAlCarrito(productId) {
    const producto = productos.find(p => p.id === productId);
    if (!producto) return;

    const existente = carrito.find(item => item.product_id === productId);
    const cantidadActual = existente ? existente.quantity : 0;

    if (cantidadActual >= 10) { // Límite de 10
        showToast(`Máximo 10 unidades por producto`, 'error');
        return;
    }
    if (cantidadActual + 1 > producto.stock) {
        showToast(`Solo quedan ${producto.stock} unidades`, 'error');
        return;
    }

    if (existente) {
        existente.quantity++;
    } else {
        carrito.push({
            product_id: producto.id,
            name: producto.name,
            unit_price: parseFloat(producto.price),
            quantity: 1,
            stock_max: producto.stock
        });
    }

    renderCarrito();
    showToast(`${producto.name} agregado`, 'success');
}

function cambiarCantidad(productId, delta) {
    const item = carrito.find(i => i.product_id === productId);
    if (!item) return;

    const nuevaCantidad = item.quantity + delta;
    if (nuevaCantidad <= 0) {
        carrito = carrito.filter(i => i.product_id !== productId);
        renderCarrito();
        return;
    }

    if (nuevaCantidad > 10) {
        showToast(`Máximo 10 unidades`, 'error');
        return;
    }
    if (nuevaCantidad > item.stock_max) {
        showToast(`Solo quedan ${item.stock_max} unidades`, 'error');
        return;
    }

    item.quantity = nuevaCantidad;
    renderCarrito();
}

function renderCarrito() {
    const contenedor = document.getElementById('carrito-items');
    const contador = document.getElementById('contador-carrito');
    const totalEl = document.getElementById('total-carrito');

    const totalItems = carrito.reduce((sum, i) => sum + i.quantity, 0);
    contador.textContent = totalItems;

    if (carrito.length === 0) {
        contenedor.innerHTML = '<p class="vacio">Aún no has agregado nada</p>';
    } else {
        contenedor.innerHTML = carrito.map(item => `
            <div class="item-carrito" style="display:flex; justify-content:space-between; align-items:center; padding: 12px 0;">
                <div>
                    <strong style="display:block; font-size: 1rem;">${item.name}</strong>
                    <small style="color: #888;">${formatPrice(item.unit_price)} c/u</small>
                </div>
                <div class="item-carrito-controles" style="display:flex; align-items:center; gap:10px;">
                    <button onclick="cambiarCantidad(${item.product_id}, -1)">−</button>
                    <span class="cantidad" style="font-weight:bold; min-width:20px; text-align:center;">${item.quantity}</span>
                    <button onclick="cambiarCantidad(${item.product_id}, 1)">+</button>
                </div>
            </div>
        `).join('');
    }

    const total = carrito.reduce((sum, i) => sum + (i.unit_price * i.quantity), 0);
    totalEl.textContent = formatPrice(total);
}

async function enviarPedido() {
    const nombre = document.getElementById('nombre-cliente').value.trim();
    if (!nombre) { showToast('Escribe tu nombre antes de enviar', 'error'); return; }
    if (carrito.length === 0) { showToast('Agrega algo al carrito primero', 'error'); return; }

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
        showToast(`¡Pedido #${respuesta.id} enviado! ☕`, 'success');

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

function abrirCarrito() {
    document.getElementById('carrito-panel').classList.add('open');
    document.getElementById('overlay').classList.add('show');
}

function cerrarCarrito() {
    document.getElementById('carrito-panel').classList.remove('open');
    document.getElementById('overlay').classList.remove('show');
}

function configurarEventos() {
    document.getElementById('btn-carrito').addEventListener('click', abrirCarrito);
    document.getElementById('cerrar-carrito').addEventListener('click', cerrarCarrito);
    document.getElementById('overlay').addEventListener('click', cerrarCarrito);
    document.getElementById('btn-enviar-pedido').addEventListener('click', enviarPedido);
}