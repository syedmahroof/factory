<template>
  <div>
    <resource-hero resource="" title="Trial Balance" icon="mdi-book-open-outline" />
    <data-table :columns="cols" :items="accounts" :loading="loading" hide-actions>
      <template #cell-opening_balance="{ value }"><span class="text-right block">{{ formatNum(value) }}</span></template>
      <template #cell-debit="{ value }"><span class="text-right block">{{ formatNum(value) }}</span></template>
      <template #cell-credit="{ value }"><span class="text-right block">{{ formatNum(value) }}</span></template>
      <template #cell-closing_balance="{ value }"><span class="text-right block font-semibold">{{ formatNum(value) }}</span></template>
    </data-table>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, error, get } = useApi();
const accounts = ref([]);
const fromDate = ref(new Date().getFullYear() + '-01-01');
const toDate = ref(new Date().toISOString().split('T')[0]);
const cols = [{ key: 'code', label: 'Code' },{ key: 'name', label: 'Account' },{ key: 'opening_balance', label: 'Opening', class: 'text-right' },{ key: 'debit', label: 'Debit', class: 'text-right' },{ key: 'credit', label: 'Credit', class: 'text-right' },{ key: 'closing_balance', label: 'Closing', class: 'text-right' }];
function formatNum(v) { return parseFloat(v || 0).toFixed(2); }
async function load() { const data = await get('/finance/trial-balance', { from: fromDate.value, to: toDate.value }); accounts.value = data.accounts || []; }
onMounted(load);
</script>
