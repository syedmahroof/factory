<template>
  <div>
    <page-header>New Customer Invoice</page-header>
    <form @submit.prevent="save" class="card max-w-2xl p-6 space-y-4">
      <form-field label="Customer" required><select v-model="form.customer_id" class="w-full rounded-hb-ctl border-hb-line" required><option value="">Select...</option><option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option></select></form-field>
      <form-field label="Invoice Number" required><input v-model="form.invoice_number" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      <div class="grid grid-cols-2 gap-4">
        <form-field label="Invoice Date" required><input v-model="form.invoice_date" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
        <form-field label="Due Date" required><input v-model="form.due_date" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      </div>
      <form-field label="Total Amount" required><input v-model.number="form.total_amount" type="number" step="0.01" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      <form-field label="Notes"><textarea v-model="form.notes" rows="2" class="w-full rounded-hb-ctl border-hb-line"></textarea></form-field>
      <div class="flex justify-end gap-3 pt-4 border-t">
        <router-link to="/customer-invoices" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import { useRouter } from 'vue-router'; import { useApi } from '../../composables/useApi'; import { useToast } from '../../composables/useToast';
const router = useRouter(); const { get, post } = useApi(); const toast = useToast();
const saving = ref(false); const customers = ref([]);
const form = ref({ customer_id: '', invoice_number: '', invoice_date: new Date().toISOString().split('T')[0], due_date: '', total_amount: 0, notes: '' });
onMounted(async () => { const d = await get('/customers'); customers.value = d.data || d; });
async function save() { saving.value = true; try { await post('/customer-invoices', form.value); toast.success('Invoice created'); router.push('/customer-invoices'); } catch (e) { toast.error(e.response?.data?.message || 'Save failed'); } finally { saving.value = false; } }
</script>
