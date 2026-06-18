const API_BASE     = 'https://travelbuddy-fullstack-production.up.railway.app/api';
const STORAGE_BASE = 'https://travelbuddy-fullstack-production.up.railway.app/storage';

const api = {
    getToken: () => localStorage.getItem('tb_token'),
    setToken: (t) => localStorage.setItem('tb_token', t),
    clearAuth: () => { localStorage.removeItem('tb_token'); localStorage.removeItem('tb_user'); },
    getUser: () => JSON.parse(localStorage.getItem('tb_user') || 'null'),
    setUser: (u) => localStorage.setItem('tb_user', JSON.stringify(u)),

    headers(requiresAuth = true, json = true) {
        const h = { 'Accept': 'application/json' };
        if (json) h['Content-Type'] = 'application/json';
        if (requiresAuth && this.getToken()) h['Authorization'] = `Bearer ${this.getToken()}`;
        return h;
    },

    async request(endpoint, options = {}) {
        try {
            const res = await fetch(`${API_BASE}${endpoint}`, options);
            if (res.status === 401) { this.clearAuth(); window.location.href = 'auth.html'; return { error: 'Unauthorized' }; }
            const data = res.headers.get('content-type')?.includes('application/json') ? await res.json() : {};
            if (!res.ok) return { error: data.message || 'Request failed', details: data.errors, status: res.status };
            return { data, status: res.status };
        } catch (e) {
            console.error('API Error:', e);
            return { error: 'Network error — is the server running?' };
        }
    },

    get(endpoint, auth = true) {
        return this.request(endpoint, { method: 'GET', headers: this.headers(auth) });
    },

    post(endpoint, body, auth = true) {
        return this.request(endpoint, { method: 'POST', headers: this.headers(auth), body: JSON.stringify(body) });
    },

    put(endpoint, body, auth = true) {
        return this.request(endpoint, { method: 'PUT', headers: this.headers(auth), body: JSON.stringify(body) });
    },

    delete(endpoint, auth = true) {
        return this.request(endpoint, { method: 'DELETE', headers: this.headers(auth) });
    },

    // Multipart form data (file uploads)
    upload(endpoint, formData, auth = true) {
        const h = { 'Accept': 'application/json' };
        if (auth && this.getToken()) h['Authorization'] = `Bearer ${this.getToken()}`;
        return this.request(endpoint, { method: 'POST', headers: h, body: formData });
    }
};

// Utility: protect pages
function requireAuth() {
    if (!api.getToken()) { window.location.href = 'auth.html'; return false; }
    return true;
}

// Utility: show alert
function showAlert(id, message, type = 'error') {
    const el = document.getElementById(id);
    if (!el) return;
    el.className = `alert ${type}`;
    el.textContent = message;
    el.style.display = 'block';
    setTimeout(() => { el.style.display = 'none'; }, 5000);
}

// Utility: toggle button loading state
function setLoading(btnId, loading, text = null) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    btn.disabled = loading;
    if (loading) {
        btn.dataset.orig = btn.textContent;
        btn.innerHTML = `<span class="loader"></span> Processing...`;
    } else {
        btn.textContent = text || btn.dataset.orig;
    }
}

// Utility: format date
function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('en-MY', { year: 'numeric', month: 'short', day: 'numeric' });
}
