<template>
  <div>
    <page-header>Production Order {{ order.number || '' }}
      <template #actions>
        <div class="flex gap-2">
          <button v-if="order.status === 'planned'" @click="startOrder" class="btn btn-primary">Start</button>
          <button v-if="order.status === 'in_progress'" @click="completeOrder" class="btn btn-primary bg-hb-green">Complete</button>
          <router-link to="/production-orders" class="btn btn-secondary">Back</router-link>
        </div>
      </template>
    </page-header>
    <div v-if="loading"><loading-spinner /></div>
    <template v-else>
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card p-6"><h3 class="font-semibold mb-3">Order Details</h3>
          <dl class="space-y-2 text-hb-body">
            <div class="flex justify-between"><dt class="text-hb-mut">Number</dt><dd class="font-mono">{{ order.number }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Item</dt><dd>{{ order.item?.name }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Quantity</dt><dd>{{ order.quantity }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Planned Start</dt><dd>{{ order.planned_start }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Planned End</dt><dd>{{ order.planned_end }}</dd></div>
            <div class="flex justify-between"><dt class="text-hb-mut">Status</dt><dd><status-badge :value="order.status" /></dd></div>
          </dl>
        </div>
        <div class="lg:col-span-2 card">
          <div class="card-header"><h3 class="font-semibold">Operations</h3></div>
          <table class="data-table">
            <thead><tr><th>#</th><th>Operation</th><th>Work Center</th><th>Status</th></tr></thead>
            <tbody>
              <tr v-for="job in order.operation_jobs || []" :key="job.id">
                <td>{{ job.sequence }}</td><td>{{ job.operation?.name || '-' }}</td><td>{{ job.workCenter?.name || '-' }}</td>
                <td><status-badge :value="job.status" /></td>
              </tr>
              <tr v-if="!order.operation_jobs?.length"><td colspan="4" class="text-center py-4 text-hb-mut">No operations</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '../../composables/useApi';
import { useToast } from '../../composables/useToast';
const route = useRoute(); const router = useRouter(); const { loading, get, post } = useApi(); const toast = useToast();
const order = ref({});
onMounted(async () => { order.value = await get(`/production-orders/${route.params.id}`); });
async function startOrder() { await post(`/production-orders/${order.value.id}/start`); toast.success('Production started'); order.value = await get(`/production-orders/${route.params.id}`); }
async function completeOrder() { await post(`/production-orders/${order.value.id}/complete`); toast.success('Production completed'); order.value = await get(`/production-orders/${route.params.id}`); }
</script>
