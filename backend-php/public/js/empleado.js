// js/empleado.js - Panel del empleado con auto-refresh

let todasLasOrdenes = [];
let ordenes = [];
let detalleCache = {};  // { order_id: [items] }
let filtroActual = 'active';  // 'active' = pending + preparing + ready
let ordenesConocidas = new Set();  // Para detectar nuevas
let primeraCarga = true;

const REFRESH_INTERVAL = 5000;  // 5 segundos

const ESTADOS = {
    pending:   { label: 'Pendiente',  siguiente: 'preparing', btn: 'Preparar',     color: 'warning' },
    preparing: { label: 'Preparando', siguiente: 'ready',     btn: 'Marcar listo', color: 'info' },
    ready:     { label: 'Listo',      siguiente: 'paid',      btn: 'Cobrar',       color: 'success' },
    paid:      { label: 'Cobrado',    siguiente: null,        btn: null,           color: 'secondary' },
};

// ============ Inicialización ============
document.addEventListener('DOMContentLoaded', () => {
    configurarEventos();
    cargarOrdenes();
    setInterval(cargarOrdenes, REFRESH_INTERVAL);
});

function configurarEventos() {
    document.querySelectorAll('.filtro-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filtroActual = btn.dataset.filtro;
            aplicarFiltro();  // ← Renderiza INMEDIATAMENTE con datos ya en memoria
        });
    });
}

// ============ Cargar órdenes del API ============
async function cargarOrdenes() {
    try {
        const todas = await api.getOrders();
        todasLasOrdenes = todas;

        // Detectar nuevas órdenes
        const nuevas = new Set();
        todas.forEach(o => {
            if (!ordenesConocidas.has(o.id) && !primeraCarga) {
                nuevas.add(o.id);
            }
        });
        todas.forEach(o => ordenesConocidas.add(o.id));
        primeraCarga = false;

        // Cargar detalles solo de las que vamos a mostrar
        await cargarDetalles(todas);

        aplicarFiltro(nuevas);
        actualizarBadges(todas);
        actualizarHora();
    } catch (error) {
        console.error('Error cargando órdenes:', error);
        document.getElementById('ordenes-grid').innerHTML =
            '<p class="vacio">Error al cargar órdenes.</p>';
    }
}

function aplicarFiltro(nuevasIds = new Set()) {
    ordenes = todasLasOrdenes.filter(o => {
        if (filtroActual === 'active') return o.status !== 'paid';
        return o.status === filtroActual;
    });
    renderOrdenes(nuevasIds);
}

async function cargarDetalles(listaOrdenes) {
    const promesas = listaOrdenes
        .filter(o => !detalleCache[o.id])
        .map(o => api.getOrderDetail(o.id).then(det => {
            detalleCache[o.id] = det.items || [];
        }));
    await Promise.all(promesas);
}

// ============ Renderizar ============
function renderOrdenes(nuevasIds = new Set()) {
    const grid = document.getElementById('ordenes-grid');

    if (ordenes.length === 0) {
        grid.innerHTML = '<p class="vacio">No hay órdenes en este filtro</p>';
        return;
    }

    grid.innerHTML = ordenes.map(o => {
        const items = detalleCache[o.id] || [];
        const estado = ESTADOS[o.status] || { label: o.status, btn: null };
        const esNueva = nuevasIds.has(o.id) ? 'nueva' : '';

        const itemsHtml = items.map(it => `
            <li>
                <span class="cant">${it.quantity}×</span>
                <span>${it.product_name}</span>
                <span>${formatPrice(it.unit_price * it.quantity)}</span>
            </li>
        `).join('');

        let botonAccion = '';
        if (estado.siguiente) {
            botonAccion = `
                <button class="btn-${estado.color}" onclick="cambiarEstado(${o.id}, '${estado.siguiente}')">
                    ${estado.btn}
                </button>
            `;
        }

        return `
            <div class="orden-card status-${o.status} ${esNueva}">
                <div class="orden-header">
                    <span class="orden-id">#${o.id}</span>
                    <span class="orden-hora">${formatTime(o.created_at)}</span>
                </div>
                <div class="orden-cliente">
                    <strong>${o.customer_name}</strong>
                    <span class="orden-status-badge">${estado.label}</span>
                </div>
                <div class="orden-items">
                    <ul>${itemsHtml || '<li><em>Sin items</em></li>'}</ul>
                </div>
                <div class="orden-total">
                    <span>Total:</span>
                    <span>${formatPrice(o.total)}</span>
                </div>
                <div class="orden-acciones">
                    ${botonAccion}
                </div>
            </div>
        `;
    }).join('');
}

function actualizarBadges(todas) {
    const counts = {
        active: todas.filter(o => o.status !== 'paid').length,
        pending: todas.filter(o => o.status === 'pending').length,
        preparing: todas.filter(o => o.status === 'preparing').length,
        ready: todas.filter(o => o.status === 'ready').length,
        paid: todas.filter(o => o.status === 'paid').length,
    };

    document.querySelectorAll('.filtro-btn').forEach(btn => {
        const f = btn.dataset.filtro;
        const badge = btn.querySelector('.badge');
        const count = counts[f] || 0;

        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        } else if (count > 0) {
            const span = document.createElement('span');
            span.className = 'badge';
            span.textContent = count;
            btn.appendChild(span);
        }
    });
}

function actualizarHora() {
    const el = document.getElementById('refresh-info');
    if (el) {
        const now = new Date();
        const formato = now.toLocaleString('es-MX', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false,
        });
        el.textContent = `Actualizado: ${formato}`;
    }
}

// ============ Cambiar estado ============
async function cambiarEstado(orderId, nuevoStatus) {
    try {
        await api.updateStatus(orderId, nuevoStatus);
        delete detalleCache[orderId];  // Forzar recarga
        showToast(`Orden #${orderId} actualizada`, 'success');
        await cargarOrdenes();
    } catch (error) {
        showToast('Error al cambiar el estado', 'error');
        console.error(error);
    }
}