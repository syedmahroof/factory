<template>
  <div>
    <resource-hero create-to="/stock/create" create-label="New Stock Balance" resource="stock" title="Stock Balances" icon="mdi-archive-outline" />
    <data-table :empty-text="error || undefined" :columns="columns" :items="items" :meta="meta" :loading="loading" v-model:search="search" v-model:sort="sort" export-url="/exports/stock" export-name="stock" table-key="stock" @apply="load()" @reset="load()" @page="loadPage">
      <template #actions="{ item }">
        <router-link :to="`/stock/${item.id}`" v-tooltip.hb="'View'" aria-label="View" class="btn btn-icon"><i class="mdi mdi-eye-outline" /></router-link>
        <router-link :to="`/stock/${item.id}/edit`" v-tooltip.hb="'Edit'" aria-label="Edit" class="btn btn-icon"><i class="mdi mdi-pencil-outline" /></router-link>
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
const columns = [{ key: 'item.code', label: 'Item' },{ key: 'item.name', label: 'Description' },{ key: 'warehouse.name', label: 'Warehouse' },{ key: 'quantity', label: 'On hand', class: 'text-right' },{ key: 'available_quantity', label: 'Available', class: 'text-right' }];
async function load(page = 1) {
    try {
        const data = await get('/stock', { page, search: search.value, sort: sort.value.key, dir: sort.value.dir });
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
    if (confirm('Delete?')) { await del('/stock/' + item.id); load(); }
}
onMounted(() => load());
</script>
