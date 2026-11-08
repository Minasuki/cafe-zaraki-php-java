// js/empleado.js

let todasLasOrdenes = [];
let ordenes = [];
let detalleCache = {};
let filtroActual = 'active';
let filtroFecha = 'today'; // Por defecto mostramos hoy
let ordenesConocidas = new Set();
let primeraCarga = true;

const REFRESH_INTERVAL = 5000;

const ESTADOS = {
    pending:   { label: 'Pendiente',  siguiente: 'preparing', btn: 'Preparar',     color: 'warning' },
    preparing: { label: 'Preparando', siguiente: 'ready',     btn: 'Marcar listo', color: 'info' },
    ready:     { label: 'Listo',      siguiente: 'paid',      btn: 'Cobrar',       color: 'success' },
    paid:      { label: 'Cobrado',    siguiente: null,        btn: null,           color: 'secondary' },
    cancelled: { label: 'Cancelado',  siguiente: null,        btn: null,           color: 'danger' }
};

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
            
            const filtrosTiempo = document.getElementById('filtros-tiempo');
            if (filtroActual === 'paid') {
                filtrosTiempo.style.display = 'flex';
            } else {
                filtrosTiempo.style.display = 'none';
            }

            cargarOrdenes();
        });
    });

    document.querySelectorAll('.btn-fecha').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.btn-fecha').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            filtroFecha = btn.dataset.fecha;
            cargarOrdenes();
        });
    });
}

async function cargarOrdenes() {
    try {
        let dateParam = (filtroActual !== 'paid') ? 'today' : filtroFecha;
        let url = `/orders.php?date=${dateParam}`;
        
        if (filtroActual !== 'active' && filtroActual !== 'paid') {
            url += `&status=${filtroActual}`;
        }

        const todas = await apiRequest(url);
        todasLasOrdenes = todas;

        const nuevas = new Set();
        todas.forEach(o => {
            if (!ordenesConocidas.has(o.id) && !primeraCarga) {
                nuevas.add(o.id);
            }
        });
        todas.forEach(o => ordenesConocidas.add(o.id));
        primeraCarga = false;

        await cargarDetalles(todas);
        
        if (filtroActual === 'active') {
            ordenes = todasLasOrdenes.filter(o => o.status !== 'paid' && o.status !== 'cancelled');
        } else if (filtroActual === 'paid') {
            ordenes = todasLasOrdenes.filter(o => o.status === 'paid');
        } else {
            ordenes = todasLasOrdenes.filter(o => o.status === filtroActual);
        }

        renderOrdenes(nuevas);
        actualizarBadges(todasLasOrdenes);
        actualizarHora();
    } catch (error) {
        console.error('Error cargando órdenes:', error);
    }
}

async function cargarDetalles(listaOrdenes) {
    const promesas = listaOrdenes
        .filter(o => !detalleCache[o.id])
        .map(o => api.getOrderDetail(o.id).then(det => {
            detalleCache[o.id] = det.items || [];
        }).catch(err => {
            detalleCache[o.id] = []; // Si falla una, no rompemos todo
        }));
    await Promise.all(promesas);
}

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
                <span>${it.product_name} <small style="color:#888">${it.customizations ? '('+it.customizations+')' : ''}</small></span>
                <span>${formatPrice(it.unit_price * it.quantity)}</span>
            </li>
        `).join('');

        let botonAccion = '';
        let botonCancelar = '';

        if (estado.siguiente) {
            botonAccion = `<button class="btn-${estado.color}" onclick="cambiarEstado(${o.id}, '${estado.siguiente}')">${estado.btn}</button>`;
        }
        if (o.status === 'ready') {
            botonCancelar = `<button class="btn-danger" onclick="cancelarOrden(${o.id})" style="flex: 0.5;">Cancelar</button>`;
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
                    ${o.cajero ? `<div style="font-size: 0.8rem; color: #666; margin-top: 5px;">Atendió: ${o.cajero}</div>` : ''}
                </div>
                <div class="orden-items"><ul>${itemsHtml || '<li><em>Sin items</em></li>'}</ul></div>
                <div class="orden-total"><span>Total:</span><span>${formatPrice(o.total)}</span></div>
                <div class="orden-acciones">${botonAccion}${botonCancelar}</div>
            </div>
        `;
    }).join('');
}

function actualizarBadges(todas) {
    const counts = {
        active: todas.filter(o => o.status !== 'paid' && o.status !== 'cancelled').length,
        pending: todas.filter(o => o.status === 'pending').length,
        preparing: todas.filter(o => o.status === 'preparing').length,
        ready: todas.filter(o => o.status === 'ready').length
    };

    document.querySelectorAll('.filtro-btn').forEach(btn => {
        const f = btn.dataset.filtro;
        if (f === 'paid') return; // No mostramos badge en cobradas
        const badge = btn.querySelector('.badge');
        const count = counts[f] || 0;

        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'inline-block' : 'none';
        }
    });
}

function actualizarHora() {
    const el = document.getElementById('refresh-info');
    if (el) {
        const now = new Date();
        el.textContent = `Actualizado: ${now.toLocaleTimeString('es-MX')}`;
    }
}

async function cambiarEstado(orderId, nuevoStatus) {
    try {
        await api.updateStatus(orderId, nuevoStatus);
        delete detalleCache[orderId];
        showToast(`Orden #${orderId} actualizada`, 'success');
        await cargarOrdenes();
    } catch (error) {
        showToast(error.message, 'error');
    }
}

async function cancelarOrden(orderId) {
    if (!confirm('¿Estás seguro de cancelar esta orden?')) return;
    try {
        await api.updateStatus(orderId, 'cancelled');
        delete detalleCache[orderId];
        showToast(`Orden #${orderId} cancelada`, 'error');
        await cargarOrdenes();
    } catch (error) {
        showToast('Error al cancelar la orden', 'error');
    }
}