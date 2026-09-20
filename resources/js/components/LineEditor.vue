<template>
  <div class="overflow-hidden rounded-hb-panel border border-hb-line">
    <div class="flex items-center justify-between bg-hb-hover px-4 py-2.5">
      <h4 class="hb-lbl mb-0">{{ title }}</h4>
      <button @click="addLine" type="button"
              class="inline-flex items-center gap-1 text-hb-sm font-bold text-hb-brand transition-colors hover:text-hb-brand-2">
        <i class="mdi mdi-plus leading-none" /> Add line
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full text-hb-body">
        <thead>
          <tr>
            <th v-for="(col, idx) in columns" :key="idx"
                class="border-b border-hb-line px-3 py-2 text-left text-hb-lbl font-bold tracking-[0.09em] text-hb-mut uppercase">
              {{ col.label }} <span v-if="col.required" class="text-hb-red">*</span>
            </th>
            <th class="w-10 border-b border-hb-line px-3 py-2"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-hb-row">
          <tr v-for="(line, lineIdx) in lines" :key="lineIdx" class="hover:bg-hb-hover">
            <td v-for="(col, colIdx) in columns" :key="colIdx" class="px-3 py-1.5">
              <select v-if="col.type === 'select'" v-model="line[col.key]" class="hb-input-sm">
                <option value="">Select...</option>
                <option v-for="opt in col.options" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
              </select>
              <input v-else-if="col.type === 'number'" type="number" v-model.number="line[col.key]"
                     :step="col.step || 0.01" :min="col.min || 0" class="hb-input-sm tabular"
                     @input="recalculate(line)" />
              <input v-else-if="col.type === 'date'" type="date" v-model="line[col.key]" class="hb-input-sm" />
              <input v-else type="text" v-model="line[col.key]" class="hb-input-sm" />
            </td>
            <td class="px-3 py-1.5 text-center">
              <button @click="removeLine(lineIdx)" type="button" aria-label="Remove line"
                      class="text-hb-mut transition-colors hover:text-hb-red">
                <i class="mdi mdi-close text-base leading-none" />
              </button>
            </td>
          </tr>
        </tbody>
        <tfoot v-if="showTotals">
          <tr class="bg-hb-hover">
            <td :colspan="columns.length - 1"
                class="px-3 py-2 text-right text-hb-lbl font-bold tracking-[0.09em] text-hb-mut uppercase">Total</td>
            <td class="tabular px-3 py-2 text-hb-body font-bold text-hb-ink">{{ formatCurrency(total) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
    <p v-if="error" class="px-4 py-2 text-hb-sm text-hb-red">{{ error }}</p>
  </div>
</template>

<script>
export default {
  name: 'LineEditor',
  props: {
    modelValue: { type: Array, default: () => [] },
    columns: { type: Array, required: true },
    title: { type: String, default: 'Line Items' },
    showTotals: { type: Boolean, default: true },
    totalField: { type: String, default: 'line_total' },
    error: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  computed: {
    lines: {
      get() { return this.modelValue; },
      set(v) { this.$emit('update:modelValue', v); }
    },
    total() {
      return this.lines.reduce((sum, l) => sum + (l[this.totalField] || l.quantity * l.unit_price || 0), 0);
    },
  },
  methods: {
    addLine() {
      const newLine = {};
      this.columns.forEach(c => { newLine[c.key] = c.default !== undefined ? c.default : ''; });
      this.lines.push(newLine);
    },
    removeLine(idx) {
      this.lines.splice(idx, 1);
    },
    recalculate(line) {
      if (line.quantity !== undefined && line.unit_price !== undefined) {
        line.line_total = (line.quantity || 0) * (line.unit_price || 0);
      }
    },
    formatCurrency(v) {
      return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(v || 0);
    },
  },
  mounted() {
    if (!this.lines.length) this.addLine();
  },
};
</script>
