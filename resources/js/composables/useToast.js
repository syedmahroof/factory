import { reactive } from 'vue';

const state = reactive({
    toasts: [],
    nextId: 0,
});

export function useToast() {
    function show(message, type = 'success', duration = 3000) {
        const id = state.nextId++;
        state.toasts.push({ id, message, type });
        setTimeout(() => {
            state.toasts = state.toasts.filter(t => t.id !== id);
        }, duration);
    }

    function success(message) { show(message, 'success'); }
    function error(message) { show(message, 'error', 5000); }
    function warning(message) { show(message, 'warning', 4000); }
    function info(message) { show(message, 'info'); }

    return { toasts: state.toasts, show, success, error, warning, info };
}
