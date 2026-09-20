<template>
  <div>
    <resource-hero resource="" title="MRP & Planning" icon="mdi-calendar-clock" />
    <div class="card mb-6"><div class="card-header"><h2 class="font-semibold">MRP Runs</h2></div>
      <data-table :columns="runCols" :items="runs" :loading="loading" hide-actions />
    </div>
    <div class="card"><div class="card-header"><h2 class="font-semibold">Planned Orders</h2></div>
      <data-table :columns="planCols" :items="plannedOrders" :meta="planMeta" :loading="loading" @page="loadOrders">
        <template #actions="{ item }">
          <button v-if="item.status==='planned'" @click="convert(item)" v-tooltip.hb="'Convert'" aria-label="Convert" class="btn btn-icon ok"><i class="mdi mdi-check" /></button>
        </template>
      </data-table>
    </div>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useApi } from '../../composables/useApi';
const { loading, get, post, del } = useApi();
const runs = ref([]); const plannedOrders = ref([]); const planMeta = ref(null); const running = ref(false);
const runCols = [{ key: 'run_number', label: 'Run #' },{ key: 'created_at', label: 'Date' },{ key: 'status', label: 'Status' },{ key: 'items_processed', label: 'Items' }];
const planCols = [{ key: 'order_number', label: 'Order #' },{ key: 'item_id', label: 'Item' },{ key: 'quantity', label: 'Qty' },{ key: 'due_date', label: 'Due' },{ key: 'status', label: 'Status' }];
async function load() { const data = await get('/planning'); runs.value = data.runs; plannedOrders.value = data.planned_orders.data; planMeta.value = data.planned_orders; }
async function loadOrders(p) { const data = await get('/planning', { page: p }); plannedOrders.value = data.planned_orders.data; planMeta.value = data.planned_orders; }
async function runMrp() { running.value = true; await post('/planning/run-mrp'); running.value = false; load(); }
async function convert(item) { if (confirm('Convert planned order?')) { await del(`/planning/planned-orders/${item.id}/convert`); load(); } }
onMounted(load);
</script>
