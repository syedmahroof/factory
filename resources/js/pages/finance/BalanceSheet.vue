<template>
  <div>
    <page-header>Balance Sheet</page-header>
    <div v-if="loading"><loading-spinner /></div>
    <div v-else class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <div class="card p-4"><h2 class="font-semibold mb-3">Assets</h2>
        <div v-for="(items, group) in data.assets || {}" :key="group" class="mb-3">
          <h3 class="text-hb-body font-semibold text-hb-mut">{{ group }}</h3>
          <div v-for="item in items" :key="item.code" class="flex justify-between text-hb-body py-1"><span>{{ item.code }} {{ item.name }}</span><span class="font-mono">{{ parseFloat(item.balance || 0).toFixed(2) }}</span></div>
        </div>
      </div>
      <div class="card p-4"><h2 class="font-semibold mb-3">Liabilities & Equity</h2>
        <div v-for="(items, group) in data.liabilities || {}" :key="group" class="mb-3">
          <h3 class="text-hb-body font-semibold text-hb-mut">{{ group }}</h3>
          <div v-for="item in items" :key="item.code" class="flex justify-between text-hb-body py-1"><span>{{ item.code }} {{ item.name }}</span><span class="font-mono">{{ parseFloat(item.balance || 0).toFixed(2) }}</span></div>
        </div>
      </div>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, get } = useApi();
const data = ref({});
onMounted(async () => { data.value = await get('/finance/balance-sheet'); });
</script>
