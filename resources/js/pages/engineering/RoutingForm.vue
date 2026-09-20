<template>
  <div>
    <page-header>{{ isEdit ? 'Edit Routing' : 'New Routing' }}</page-header>
    <form @submit.prevent="save" class="space-y-6">
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <form-field label="Item" required><select v-model="form.item_id" class="w-full rounded-hb-ctl border-hb-line" required><option value="">Select...</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option></select></form-field>
          <form-field label="Version"><input v-model="form.version" class="w-full rounded-hb-ctl border-hb-line" value="1.0" /></form-field>
          <form-field label="Status"><select v-model="form.status" class="w-full rounded-hb-ctl border-hb-line"><option value="draft">Draft</option><option value="active">Active</option></select></form-field>
        </div>
      </div>
      <div class="card">
        <div class="card-header flex items-center justify-between"><h3 class="font-semibold">Operations</h3><button type="button" @click="addOp" class="text-hb-body text-hb-brand">+ Add Operation</button></div>
        <table class="data-table">
          <thead><tr><th>Seq</th><th>Operation Name</th><th>Work Center</th><th>Setup Time (h)</th><th>Run Time (h)</th><th></th></tr></thead>
          <tbody>
            <tr v-for="(op, idx) in form.operations" :key="idx">
              <td><input v-model.number="op.sequence" type="number" class="rounded-hb-ctl border-hb-line text-hb-body w-16" :value="idx + 1" /></td>
              <td><input v-model="op.name" class="rounded-hb-ctl border-hb-line text-hb-body w-48" placeholder="e.g. Cutting" /></td>
              <td><select v-model="op.work_center_id" class="rounded-hb-ctl border-hb-line text-hb-body w-48"><option value="">Select...</option><option v-for="wc in workCenters" :key="wc.id" :value="wc.id">{{ wc.name }}</option></select></td>
              <td><input v-model.number="op.setup_time" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-20 text-right" /></td>
              <td><input v-model.number="op.run_time" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-20 text-right" /></td>
              <td><button type="button" @click="form.operations.splice(idx,1)" class="text-hb-red text-hb-body">✕</button></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div class="flex justify-end gap-3">
        <router-link to="/routings" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import { useRoute, useRouter } from 'vue-router'; import { useApi } from '../../composables/useApi'; import { useToast } from '../../composables/useToast';
const route = useRoute(); const router = useRouter(); const { get, post, put } = useApi(); const toast = useToast();
const isEdit = ref(!!route.params.id); const saving = ref(false); const items = ref([]); const workCenters = ref([]);
const form = ref({ item_id: '', version: '1.0', status: 'draft', operations: [{ sequence: 1, name: '', work_center_id: '', setup_time: 0, run_time: 0 }] });
function addOp() { form.value.operations.push({ sequence: form.value.operations.length + 1, name: '', work_center_id: '', setup_time: 0, run_time: 0 }); }
onMounted(async () => { const [i, w] = await Promise.all([get('/items'), get('/work-centers')]); items.value = i.data || i; workCenters.value = w.data || w; if (isEdit.value) { const d = await get(`/routings/${route.params.id}`); Object.assign(form.value, d); } });
async function save() { saving.value = true; try { if (isEdit.value) await put(`/routings/${route.params.id}`, form.value); else await post('/routings', form.value); toast.success('Routing saved'); router.push('/routings'); } catch (e) { toast.error(e.response?.data?.message || 'Save failed'); } finally { saving.value = false; } }
</script>
