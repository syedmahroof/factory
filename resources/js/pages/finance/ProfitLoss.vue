<template>
  <div>
    <page-header>Profit &amp; Loss</page-header>
    <div v-if="loading"><loading-spinner /></div>
    <div v-else class="card max-w-2xl p-6 space-y-4">
      <div v-for="section in sections" :key="section.label">
        <h3 class="font-semibold text-hb-ink mb-2">{{ section.label }}</h3>
        <div v-for="item in section.items" :key="item.code" class="flex justify-between text-hb-body py-1"><span>{{ item.code }} {{ item.name }}</span><span class="font-mono">{{ parseFloat(item.amount || 0).toFixed(2) }}</span></div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, get } = useApi();
const data = ref({});
const sections = computed(() => [
    { label: 'Revenue', items: (data.value.revenue || []).map(i => ({ ...i, amount: i.credit })) },
    { label: 'COGS', items: (data.value.cogs || []).map(i => ({ ...i, amount: i.debit })) },
    { label: 'Operating Expenses', items: (data.value.operating_expenses || []).map(i => ({ ...i, amount: i.debit })) },
]);
onMounted(async () => { data.value = await get('/finance/profit-loss'); });
</script>
