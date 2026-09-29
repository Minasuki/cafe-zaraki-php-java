// js/api.js - Configuración y wrapper del API

// ⚠️ CAMBIA ESTA URL según el backend que uses:
// - FastAPI:  http://127.0.0.1:8000/api/v1
// - PHP:      http://127.0.0.1:8080/cafe/api
const API_URL = 'http://127.0.0.1:8000/api/v1';

// WebSocket (solo disponible en FastAPI por ahora)
const WS_URL = 'ws://127.0.0.1:8000/ws/employee';

// Wrapper de fetch con manejo de errores
async function apiRequest(endpoint, options = {}) {
    const url = `${API_URL}${endpoint}`;

    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        },
    };

    const config = { ...defaultOptions, ...options };

    try {
        const response = await fetch(url, config);
        const data = await response.json();

        if (!response.ok) {
            throw new Error(data.detail || `Error ${response.status}`);
        }

        return data;
    } catch (error) {
        console.error(`❌ Error en ${endpoint}:`, error);
        throw error;
    }
}

// Helpers específicos
const api = {
    // Productos
    getProducts: () => apiRequest('/products'),

    // Órdenes
    createOrder: (order) => apiRequest('/orders', {
        method: 'POST',
        body: JSON.stringify(order),
    }),

    getOrders: (status = null) => {
        const query = status ? `?status=${status}` : '';
        return apiRequest(`/orders${query}`);
    },

    getOrderDetail: (id) => apiRequest(`/orders/${id}`),

    updateStatus: (id, status) => apiRequest(`/orders/${id}/status`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
    }),
};

// Helper: mostrar toast
function showToast(message, type = 'info') {
    // Eliminar toast existente
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    // Forzar reflow para que la animación funcione
    requestAnimationFrame(() => toast.classList.add('show'));

    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Helper: formatear precio
function formatPrice(value) {
    return `$${parseFloat(value).toFixed(2)}`;
}

// Helper: formatear hora
function formatTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleTimeString('es-MX', {
        hour: '2-digit',
        minute: '2-digit',
    });
}