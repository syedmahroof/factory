<template>
  <div>
    <page-header>{{ isEdit ? 'Edit BOM' : 'New BOM' }}</page-header>
    <form @submit.prevent="save" class="space-y-6">
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <form-field label="Finished Item" required>
            <select v-model="form.item_id" class="w-full rounded-hb-ctl border-hb-line" required><option value="">Select...</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option></select>
          </form-field>
          <form-field label="Version"><input v-model="form.version" class="w-full rounded-hb-ctl border-hb-line" value="1.0" /></form-field>
          <form-field label="Status">
            <select v-model="form.status" class="w-full rounded-hb-ctl border-hb-line"><option value="draft">Draft</option><option value="active">Active</option></select>
          </form-field>
        </div>
      </div>
      <div class="card">
        <div class="card-header flex items-center justify-between"><h3 class="font-semibold">BOM Lines</h3><button type="button" @click="addLine" class="text-hb-body text-hb-brand">+ Add Component</button></div>
        <table class="data-table">
          <thead><tr><th>Component Item</th><th>Quantity</th><th>UOM</th><th>Scrap %</th><th></th></tr></thead>
          <tbody>
            <tr v-for="(line, idx) in form.lines" :key="idx">
              <td><select v-model="line.item_id" class="rounded-hb-ctl border-hb-line text-hb-body w-64"><option value="">Select...</option><option v-for="i in items" :key="i.id" :value="i.id">{{ i.name }}</option></select></td>
              <td><input v-model.number="line.quantity" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-24 text-right" /></td>
              <td><input v-model="line.uom" class="rounded-hb-ctl border-hb-line text-hb-body w-20" value="EA" /></td>
              <td><input v-model.number="line.scrap_percent" type="number" step="0.01" class="rounded-hb-ctl border-hb-line text-hb-body w-20 text-right" value="0" /></td>
              <td><button type="button" @click="form.lines.splice(idx,1)" class="text-hb-red text-hb-body">✕</button></td>
            </tr>
            <tr v-if="!form.lines.length"><td colspan="5" class="text-center py-4 text-hb-mut">No components yet</td></tr>
          </tbody>
        </table>
      </div>
      <div class="flex justify-end gap-3">
        <router-link to="/boms" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving">{{ saving ? 'Saving...' : 'Save BOM' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, onMounted } from 'vue'; import { useRoute, useRouter } from 'vue-router'; import { useApi } from '../../composables/useApi'; import { useToast } from '../../composables/useToast';
const route = useRoute(); const router = useRouter(); const { get, post, put } = useApi(); const toast = useToast();
const isEdit = ref(!!route.params.id); const saving = ref(false); const items = ref([]);
const form = ref({ item_id: '', version: '1.0', status: 'draft', lines: [{ item_id: '', quantity: 1, uom: 'EA', scrap_percent: 0 }] });
function addLine() { form.value.lines.push({ item_id: '', quantity: 1, uom: 'EA', scrap_percent: 0 }); }
onMounted(async () => { const d = await get('/items'); items.value = d.data || d; if (isEdit.value) { const data = await get(`/boms/${route.params.id}`); Object.assign(form.value, data); } });
async function save() { saving.value = true; try { if (isEdit.value) await put(`/boms/${route.params.id}`, form.value); else await post('/boms', form.value); toast.success('BOM saved'); router.push('/boms'); } catch (e) { toast.error(e.response?.data?.message || 'Save failed'); } finally { saving.value = false; } }
</script>
