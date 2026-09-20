import { createApp } from 'vue';
import { createPinia } from 'pinia';
import FloatingVue from 'floating-vue';
import router from './router';
import App from './layouts/App.vue';
import '../css/app.css';
import 'floating-vue/dist/style.css';
import '../css/tooltip.css';

// Global components
import StatusBadge from './components/StatusBadge.vue';
import ConfirmationModal from './components/ConfirmationModal.vue';
import SearchableSelect from './components/SearchableSelect.vue';
import LineEditor from './components/LineEditor.vue';
import DataTable from './components/DataTable.vue';
import LoadingSpinner from './components/LoadingSpinner.vue';
import FormField from './components/FormField.vue';
import PageHeader from './components/PageHeader.vue';
import EmptyState from './components/EmptyState.vue';
import ResourceHero from './components/ResourceHero.vue';

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

/*
 * Tooltips. floating-vue owns the positioning — flip, shift-on-overflow, arrow
 * tracking — which is the part that is genuinely hard and the part the browser's
 * own `title` gets wrong: a native tooltip cannot be styled, appears after a
 * second of stillness, and clips at the window edge.
 *
 * `hb` is this app's only theme; the skin is in css/tooltip.css. The delays are
 * short enough that sweeping across a row of action buttons feels responsive but
 * long enough that crossing one on the way somewhere else stays quiet.
 */
app.use(FloatingVue, {
    themes: {
        hb: {
            $extend: 'tooltip',
            triggers: ['hover', 'focus', 'touch'],
            delay: { show: 120, hide: 60 },
            distance: 8,
            placement: 'top',
            html: false,
        },
    },
});

// Register global components
app.component('status-badge', StatusBadge);
app.component('confirmation-modal', ConfirmationModal);
app.component('searchable-select', SearchableSelect);
app.component('line-editor', LineEditor);
app.component('data-table', DataTable);
app.component('loading-spinner', LoadingSpinner);
app.component('form-field', FormField);
app.component('page-header', PageHeader);
app.component('empty-state', EmptyState);
app.component('resource-hero', ResourceHero);

// Global properties
app.config.globalProperties.$formatCurrency = (value) => {
    return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(value || 0);
};

app.config.globalProperties.$formatDate = (date) => {
    return date ? new Date(date).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' }) : '-';
};

// Axios CSRF token
const meta = document.querySelector('meta[name="csrf-token"]');
if (meta) {
    window.csrfToken = meta.getAttribute('content');
}

app.mount('#app');

/*
 * Retire the preloader the blade painted before this bundle existed. Waiting a
 * frame past mount means the first screen is drawn underneath it rather than
 * flashing white between the two.
 */
requestAnimationFrame(() => {
    const preloader = document.getElementById('app-preloader');

    if (!preloader) return;

    preloader.classList.add('is-done');
    preloader.addEventListener('transitionend', () => preloader.remove(), { once: true });
});
