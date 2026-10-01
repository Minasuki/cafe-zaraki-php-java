// js/api.js - Capa de comunicación con el API

const API_URL = 'http://cafe.local/api';

async function apiRequest(endpoint, options = {}) {
    const url = `${API_URL}${endpoint}`;
    const config = {
        headers: { 'Content-Type': 'application/json' },
        ...options,
    };

    const response = await fetch(url, config);
    const data = await response.json();

    if (!response.ok) {
        throw new Error(data.error || `Error ${response.status}`);
    }
    return data;
}

const api = {
    getProducts: () => apiRequest('/products.php'),

    createOrder: (order) => apiRequest('/orders.php', {
        method: 'POST',
        body: JSON.stringify(order),
    }),

    getOrders: (status = null) => {
        const query = status ? `?status=${status}` : '';
        return apiRequest(`/orders.php${query}`);
    },

    getOrderDetail: (id) => apiRequest(`/orders.php?id=${id}`),

    updateStatus: (id, status) => apiRequest(`/status.php?id=${id}`, {
        method: 'PATCH',
        body: JSON.stringify({ status }),
    }),
};

function showToast(message, type = 'info') {
    const existing = document.querySelector('.toast');
    if (existing) existing.remove();

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    requestAnimationFrame(() => toast.classList.add('show'));
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function formatPrice(value) {
    return `$${parseFloat(value).toFixed(2)}`;
}

function formatTime(isoString) {
    const date = new Date(isoString);
    return date.toLocaleString('es-MX', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });
}