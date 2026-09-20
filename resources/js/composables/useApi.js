import { ref } from 'vue';
import client from '../api/client';

export function useApi() {
    const loading = ref(false);
    const error = ref(null);

    async function get(url, params = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await client.get(url, { params });
            return data.data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Request failed';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function post(url, payload = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await client.post(url, payload);
            return data.data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Request failed';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function put(url, payload = {}) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await client.put(url, payload);
            return data.data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Request failed';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    async function del(url) {
        loading.value = true;
        error.value = null;
        try {
            const { data } = await client.delete(url);
            return data;
        } catch (e) {
            error.value = e.response?.data?.message || 'Request failed';
            throw e;
        } finally {
            loading.value = false;
        }
    }

    return { loading, error, get, post, put, del };
}
