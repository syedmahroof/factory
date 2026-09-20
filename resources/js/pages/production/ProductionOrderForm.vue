<template>
  <div>
    <page-header>New Production Order</page-header>
    <form @submit.prevent="save" class="card max-w-2xl p-6 space-y-4">
      <form-field label="Item (Finished Good)" required>
        <select v-model="form.item_id" class="w-full rounded-hb-ctl border-hb-line" required>
          <option value="">Select item...</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }} ({{ i.code }})</option>
        </select>
      </form-field>
      <form-field label="Quantity" required><input v-model.number="form.quantity" type="number" step="0.01" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      <form-field label="Planned Start Date" required><input v-model="form.planned_start" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      <form-field label="Planned End Date" required><input v-model="form.planned_end" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
      <form-field label="Priority">
        <select v-model="form.priority" class="w-full rounded-hb-ctl border-hb-line"><option value="normal">Normal</option><option value="high">High</option><option value="urgent">Urgent</option></select>
      </form-field>
      <form-field label="Notes"><textarea v-model="form.notes" rows="2" class="w-full rounded-hb-ctl border-hb-line"></textarea></form-field>
      <div class="flex justify-end gap-3 pt-4 border-t">
        <router-link to="/production-orders" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Create' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useApi } from '../../composables/useApi';
import { useToast } from '../../composables/useToast';
const router = useRouter(); const { get, post } = useApi(); const toast = useToast();
const saving = ref(false); const items = ref([]);
const form = ref({ item_id: '', quantity: 1, planned_start: '', planned_end: '', priority: 'normal', notes: '' });
onMounted(async () => { const d = await get('/items'); items.value = d.data || d; });
async function save() { saving.value = true; try { await post('/production-orders', form.value); toast.success('Production order created'); router.push('/production-orders'); } catch (e) { toast.error(e.response?.data?.message || 'Save failed'); } finally { saving.value = false; } }
</script>
