<template>
  <div>
    <resource-hero resource="" title="Period Close" icon="mdi-book-open-outline" />
    <data-table :columns="[{ key: 'period', label: 'Period' },{ key: 'status', label: 'Status' },{ key: 'journal_count', label: 'Journals' }]" :items="closes" :loading="loading" hide-actions />
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, get, post } = useApi();
const closes = ref([]); const period = ref(new Date().toISOString().slice(0, 7));
async function load() { closes.value = await get('/finance/period-close'); }
async function close() { if (confirm('Close this period?')) { await post('/finance/period-close', { period: period.value }); load(); } }
onMounted(load);
</script>
