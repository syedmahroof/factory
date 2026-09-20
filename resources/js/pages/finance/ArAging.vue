<template>
  <div>
    <resource-hero resource="" title="AR Aging" icon="mdi-book-open-outline" />
    <data-table :empty-text="error || undefined" :columns="columns" :items="items" :meta="meta" :loading="loading" v-model:search="search" v-model:sort="sort" @apply="load()" @reset="load()" @page="loadPage">
      <template #actions="{ item }">
        <button @click="remove(item)" v-tooltip.hb="'Delete'" aria-label="Delete" class="btn btn-icon danger"><i class="mdi mdi-trash-can-outline" /></button>
      </template>
    </data-table>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, error, get, del } = useApi();
const items = ref([]); const meta = ref(null); const search = ref(''); const sort = ref({ key: '', dir: 'asc' });
const columns = [{ key: 'invoice_number', label: 'Invoice #' },{ key: 'due_date', label: 'Due' },{ key: 'status', label: 'Status' }];
async function load(page = 1) {
    try {
        const data = await get('/customer-invoices', { page, search: search.value, sort: sort.value.key, dir: sort.value.dir });
        items.value = data?.data ?? [];
        meta.value = data ?? null;
    } catch {
        // The endpoint answered with something that is not a page of
        // records; the table says so through its empty state.
        items.value = [];
        meta.value = null;
    }
}
function loadPage(p) { load(p); }
async function remove(item) {
    if (confirm('Delete?')) { await del('/customer-invoices/' + item.id); load(); }
}
onMounted(() => load());
</script>
