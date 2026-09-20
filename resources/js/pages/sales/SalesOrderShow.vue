<template>
  <div>
    <page-header>Sales Order {{ order.number || '' }}
      <template #actions>
        <div class="flex gap-2">
          <button v-if="order.status === 'draft'" @click="confirmOrder" class="btn btn-primary">Confirm</button>
          <router-link to="/sales-orders" class="btn btn-secondary">Back</router-link>
        </div>
      </template>
    </page-header>
    <div v-if="loading"><loading-spinner /></div>
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card p-6"><h3 class="font-semibold mb-3">Order Details</h3>
          <dl class="space-y-2 text-hb-body">
            <div class="flex justify-between"><dt class="text-hb-mut">Number</dt><dd class="font-mono">{{ order.number }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Customer</dt><dd>{{ order.customer?.name }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Date</dt><dd>{{ order.order_date }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Status</dt><dd><status-badge :value="order.status" /></dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Total</dt><dd class="font-mono font-bold">{{ parseFloat(order.total_amount || 0).toFixed(2) }}</dd></div>
          </dl>
        </div>
        <div class="lg:col-span-2 card">
          <div class="card-header"><h3 class="font-semibold">Line Items</h3></div>
          <table class="data-table">
            <thead><tr><th>Item</th><th>Qty</th><th class="text-right">Price</th><th class="text-right">Total</th></tr></thead>
            <tbody><tr v-for="line in order.lines || []" :key="line.id"><td>{{ line.item?.name || line.description }}</td><td>{{ line.quantity }}</td><td class="text-right">{{ parseFloat(line.unit_price||0).toFixed(2) }}</td><td class="text-right font-mono">{{ ((line.quantity||0)*(line.unit_price||0)).toFixed(2) }}</td></tr></tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useApi } from '../../composables/useApi';
import { useToast } from '../../composables/useToast';
const route = useRoute(); const { loading, get, post } = useApi(); const toast = useToast();
const order = ref({});
onMounted(async () => { order.value = await get(`/sales-orders/${route.params.id}`); });
async function confirmOrder() { await post(`/sales-orders/${order.value.id}/confirm`); toast.success('SO Confirmed'); order.value = await get(`/sales-orders/${route.params.id}`); }
</script>
