import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
const tokenElement = document.querySelector('meta[name="csrf-token"]');
if (tokenElement) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = tokenElement.getAttribute('content');
}
