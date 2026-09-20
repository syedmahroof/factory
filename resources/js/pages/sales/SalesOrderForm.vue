<template>
  <div>
    <page-header>{{ isEdit ? 'Edit Sales Order' : 'New Sales Order' }}</page-header>
    <form @submit.prevent="save" class="space-y-6">
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <form-field label="Customer" required>
            <select v-model="form.customer_id" class="w-full rounded-hb-ctl border-hb-line" required>
              <option value="">Select customer...</option>
              <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
            </select>
          </form-field>
          <form-field label="Order Date" required><input v-model="form.order_date" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
          <form-field label="Delivery Date"><input v-model="form.delivery_date" type="date" class="w-full rounded-hb-ctl border-hb-line" /></form-field>
          <form-field label="Payment Terms">
            <select v-model="form.payment_terms" class="w-full rounded-hb-ctl border-hb-line"><option value="net_30">Net 30</option><option value="net_60">Net 60</option><option value="cod">COD</option></select>
          </form-field>
          <form-field label="Shipping Address"><input v-model="form.shipping_address" class="w-full rounded-hb-ctl border-hb-line" /></form-field>
        </div>
        <form-field label="Notes"><textarea v-model="form.notes" rows="2" class="w-full rounded-hb-ctl border-hb-line"></textarea></form-field>
      </div>
      <div class="card">
        <div class="card-header flex items-center justify-between"><h3 class="font-semibold">Line Items</h3><button type="button" @click="addLine" class="text-hb-body text-hb-brand">+ Add Line</button></div>
        <table class="data-table">
          <thead><tr><th>Item</th><th>Description</th><th class="text-right">Qty</th><th class="text-right">Price</th><th class="text-right">Tax%</th><th class="text-right">Total</th><th></th></tr></thead>
          <tbody>
            <tr v-for="(line, idx) in form.lines" :key="idx">
              <td><select v-model="line.item_id" class="rounded-hb-ctl border-hb-line text-hb-body w-48"><option value="">Select...</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option></select></td>
              <td><input v-model="line.description" class="rounded-hb-ctl border-hb-line text-hb-body w-40" /></td>
              <td><input v-model.number="line.quantity" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-20 text-right" /></td>
              <td><input v-model.number="line.unit_price" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-24 text-right" /></td>
              <td><input v-model.number="line.tax_rate" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-16 text-right" value="0" /></td>
              <td class="text-right font-mono">{{ ((line.quantity||0)*(line.unit_price||0)*(1+(line.tax_rate||0)/100)).toFixed(2) }}</td>
              <td><button type="button" @click="form.lines.splice(idx,1)" class="text-hb-red text-hb-body">✕</button></td>
            </tr>
            <tr v-if="!form.lines.length"><td colspan="7" class="text-center py-4 text-hb-mut">No lines yet</td></tr>
          </tbody>
          <tfoot v-if="form.lines.length"><tr class="font-bold"><td colspan="5" class="text-right">Total</td><td class="text-right font-mono text-lg">{{ grandTotal.toFixed(2) }}</td><td></td></tr></tfoot>
        </table>
      </div>
      <div class="flex justify-end gap-3">
        <router-link to="/sales-orders" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '../../composables/useApi';
import { useToast } from '../../composables/useToast';
const route = useRoute(); const router = useRouter();
const { get, post, put } = useApi(); const toast = useToast();
const isEdit = computed(() => !!route.params.id); const saving = ref(false);
const customers = ref([]); const items = ref([]);
const form = ref({ customer_id: '', order_date: new Date().toISOString().split('T')[0], delivery_date: '', payment_terms: 'net_30', shipping_address: '', notes: '', lines: [{ item_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0 }] });
const grandTotal = computed(() => form.value.lines.reduce((s, l) => s + (l.quantity||0)*(l.unit_price||0)*(1+(l.tax_rate||0)/100), 0));
function addLine() { form.value.lines.push({ item_id: '', description: '', quantity: 1, unit_price: 0, tax_rate: 0 }); }
onMounted(async () => {
  const [c, i] = await Promise.all([get('/customers'), get('/items')]); customers.value = c.data || c; items.value = i.data || i;
  if (isEdit.value) { const d = await get(`/sales-orders/${route.params.id}`); Object.assign(form.value, d); }
});
async function save() { saving.value = true; try { if (isEdit.value) await put(`/sales-orders/${route.params.id}`, form.value); else await post('/sales-orders', form.value); toast.success('Sales order saved'); router.push('/sales-orders'); } catch (e) { toast.error(e.response?.data?.message || 'Save failed'); } finally { saving.value = false; } }
</script>
