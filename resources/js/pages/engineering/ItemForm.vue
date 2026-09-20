<template>
  <div>
    <page-header>{{ isEdit ? 'Edit Item' : 'New Item' }}</page-header>
    <div class="card max-w-2xl">
      <form @submit.prevent="save" class="p-6 space-y-4">
        <form-field label="Code" required><input v-model="form.code" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
        <form-field label="Name" required><input v-model="form.name" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
        <form-field label="Type" required>
          <select v-model="form.type" class="w-full rounded-hb-ctl border-hb-line" required>
            <option value="raw_material">Raw Material</option><option value="finished_good">Finished Good</option><option value="semi_finished">Semi-Finished</option><option value="consumable">Consumable</option>
          </select>
        </form-field>
        <form-field label="Standard Cost"><input v-model="form.standard_cost" type="number" step="0.01" class="w-full rounded-hb-ctl border-hb-line" /></form-field>
        <form-field label="Reorder Point"><input v-model="form.reorder_point" type="number" step="0.01" class="w-full rounded-hb-ctl border-hb-line" /></form-field>
        <div class="flex justify-end gap-3 pt-4 border-t">
          <router-link to="/items" class="btn btn-secondary">Cancel</router-link>
          <button type="submit" class="btn btn-primary">Save</button>
        </div>
      </form>
    </div>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useApi } from '../../composables/useApi';
const route = useRoute(); const router = useRouter();
const { loading, get, post, put } = useApi();
const isEdit = computed(() => !!route.params.id);
const form = ref({ code: '', name: '', type: 'raw_material', standard_cost: 0, reorder_point: 0 });
onMounted(async () => { if (isEdit.value) { const data = await get(`/items/${route.params.id}`); Object.assign(form.value, data); } });
async function save() {
    if (isEdit.value) { await put(`/items/${route.params.id}`, form.value); }
    else { await post('/items', form.value); }
    router.push('/items');
}
</script>
