import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

// CSRF: que axios tome el token de la cookie XSRF-TOKEN (siempre fresca, incluso
// después de que el login regenere la sesión). Antes se fijaba un X-CSRF-TOKEN del
// <meta> que quedaba viejo tras iniciar sesión y causaba "Desajuste de tokens CSRF" (419).
window.axios.defaults.withCredentials = true;
window.axios.defaults.withXSRFToken = true;

// Helper para las llamadas con fetch() (que no usan axios): devuelve el token CSRF
// fresco desde la cookie XSRF-TOKEN. Enviar como header 'X-XSRF-TOKEN'.
window.getXsrfToken = () =>
    decodeURIComponent((document.cookie.match(/(^|;)\s*XSRF-TOKEN=([^;]+)/) || [])[2] || '');
