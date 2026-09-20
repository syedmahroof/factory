import { defineStore } from 'pinia';
import { ref } from 'vue';
import client from '../api/client';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const token = ref(localStorage.getItem('auth_token'));

    async function login(email, password) {
        const { data } = await client.post('/login', { email, password });
        token.value = data.data.token;
        user.value = data.data.user;
        localStorage.setItem('auth_token', data.data.token);
        client.defaults.headers.common['Authorization'] = `Bearer ${data.data.token}`;
        return data;
    }

    async function logout() {
        try { await client.post('/logout'); } catch (e) {}
        token.value = null;
        user.value = null;
        localStorage.removeItem('auth_token');
        delete client.defaults.headers.common['Authorization'];
    }

    /*
     * Only a 401 means the token is dead. A 500 or a dropped connection says
     * nothing about the session, and throwing the token away on one of those
     * signed the user out of a working account — the interceptor then bounced
     * the next click to /login.
     */
    async function fetchUser() {
        if (!token.value) return;
        try {
            const { data } = await client.get('/me');
            user.value = data.data;
        } catch (e) {
            if (e.response?.status === 401) logout();
        }
    }

    return { user, token, login, logout, fetchUser };
});
