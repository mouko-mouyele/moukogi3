import './bootstrap';
import { createApp } from 'vue';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import axios from 'axios';

// Configuration axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['Accept'] = 'application/json';
axios.defaults.headers.common['Content-Type'] = 'application/json';

// Récupérer le token depuis le localStorage
const token = localStorage.getItem('token');
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Intercepteur pour gérer les erreurs d'authentification
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

// Routes
const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('./pages/Login.vue'),
        meta: { requiresAuth: false }
    },
    {
        path: '/',
        name: 'dashboard',
        component: () => import('./pages/Dashboard.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/products',
        name: 'products',
        component: () => import('./pages/Products.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/products/:id',
        name: 'product-detail',
        component: () => import('./pages/ProductDetail.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/categories',
        name: 'categories',
        component: () => import('./pages/Categories.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/movements',
        name: 'movements',
        component: () => import('./pages/Movements.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/inventories',
        name: 'inventories',
        component: () => import('./pages/Inventories.vue'),
        meta: { requiresAuth: true }
    },
    {
        path: '/alerts',
        name: 'alerts',
        component: () => import('./pages/Alerts.vue'),
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes
});

// Garde de navigation
router.beforeEach((to, from, next) => {
    const token = localStorage.getItem('token');
    const requiresAuth = to.meta.requiresAuth !== false;    if (requiresAuth && !token) {
        next('/login');
    } else if (!requiresAuth && token) {
        next('/');
    } else {
        next();
    }
});const app = createApp(App);
app.use(router);
app.mount('#app');
