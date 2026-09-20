<template>
  <div>
    <page-header>New Journal Entry</page-header>
    <form @submit.prevent="save" class="space-y-6">
      <div class="card p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
          <form-field label="Date" required><input v-model="form.date" type="date" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
          <form-field label="Description" required><input v-model="form.description" class="w-full rounded-hb-ctl border-hb-line" required /></form-field>
          <form-field label="Reference"><input v-model="form.reference" class="w-full rounded-hb-ctl border-hb-line" placeholder="e.g. INV-001" /></form-field>
        </div>
      </div>
      <div class="card">
        <div class="card-header flex items-center justify-between">
          <h3 class="font-semibold">Journal Lines</h3>
          <button type="button" @click="addLine" class="text-hb-body text-hb-brand">+ Add Line</button>
        </div>
        <table class="data-table">
          <thead><tr><th>Account</th><th>Description</th><th class="text-right">Debit</th><th class="text-right">Credit</th><th></th></tr></thead>
          <tbody>
            <tr v-for="(line, idx) in form.lines" :key="idx">
              <td><select v-model="line.account_id" class="rounded-hb-ctl border-hb-line text-hb-body w-64"><option value="">Select account...</option><option v-for="a in accounts" :key="a.id" :value="a.id">{{ a.code }} - {{ a.name }}</option></select></td>
              <td><input v-model="line.description" class="rounded-hb-ctl border-hb-line text-hb-body w-48" /></td>
              <td><input v-model.number="line.debit" type="number" step="0.01" min="0" class="rounded-hb-ctl border-hb-line text-hb-body w-28 text-right" :disabled="line.credit > 0" /></td>
              <td><input v-model.number="line.credit" type="number" step="0.01" min="0" class="rounded-hb-ctl border-hb-line text-hb-body w-28 text-right" :disabled="line.debit > 0" /></td>
              <td><button type="button" @click="form.lines.splice(idx,1)" class="text-hb-red text-hb-body">✕</button></td>
            </tr>
            <tr v-if="!form.lines.length"><td colspan="5" class="text-center py-4 text-hb-mut">Add at least 2 lines</td></tr>
          </tbody>
          <tfoot v-if="form.lines.length">
            <tr class="font-bold border-t-2">
              <td colspan="2" class="text-right">Totals</td>
              <td class="text-right font-mono" :class="totalDebit !== totalCredit ? 'text-hb-red' : 'text-hb-green'">{{ totalDebit.toFixed(2) }}</td>
              <td class="text-right font-mono" :class="totalDebit !== totalCredit ? 'text-hb-red' : 'text-hb-green'">{{ totalCredit.toFixed(2) }}</td>
              <td><span v-if="totalDebit !== totalCredit" class="text-hb-red text-hb-body">⚠ Unbalanced</span></td>
            </tr>
          </tfoot>
        </table>
      </div>
      <div class="flex justify-end gap-3">
        <router-link to="/journals" class="btn btn-secondary">Cancel</router-link>
        <button type="submit" class="btn btn-primary" :disabled="saving || totalDebit !== totalCredit || form.lines.length < 2">{{ saving ? 'Saving...' : 'Post Journal' }}</button>
      </div>
    </form>
  </div>
</template>
<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { useApi } from '../../composables/useApi';
import { useToast } from '../../composables/useToast';
const router = useRouter(); const { get, post } = useApi(); const toast = useToast();
const saving = ref(false); const accounts = ref([]);
const form = ref({ date: new Date().toISOString().split('T')[0], description: '', reference: '', lines: [{ account_id: '', description: '', debit: 0, credit: 0 }, { account_id: '', description: '', debit: 0, credit: 0 }] });
const totalDebit = computed(() => form.value.lines.reduce((s, l) => s + (l.debit || 0), 0));
const totalCredit = computed(() => form.value.lines.reduce((s, l) => s + (l.credit || 0), 0));
function addLine() { form.value.lines.push({ account_id: '', description: '', debit: 0, credit: 0 }); }
onMounted(async () => { const d = await get('/accounts'); accounts.value = d.data || d; });
async function save() {
  if (totalDebit.value !== totalCredit.value) { toast.error('Debits must equal credits'); return; }
  saving.value = true;
  try { await post('/journals', form.value); toast.success('Journal posted'); router.push('/journals'); }
  catch (e) { toast.error(e.response?.data?.message || 'Save failed'); }
  finally { saving.value = false; }
}
</script>
