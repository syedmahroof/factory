import axios from 'axios';

const client = axios.create({
    baseURL: '/api/v1',
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// CSRF token
client.defaults.headers.common['X-CSRF-TOKEN'] = window.csrfToken || '';

// Auth token from localStorage
const token = localStorage.getItem('auth_token');
if (token) {
    client.defaults.headers.common['Authorization'] = `Bearer ${token}`;
}

// Response interceptor — handle 401
client.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token');
            window.location.href = '/login';
        }
        return Promise.reject(error);
    }
);

export default client;
