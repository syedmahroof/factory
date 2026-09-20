<script setup>
/**
 * Create and edit, for every register that has no hand-built form of its own.
 *
 * The shape is ip_new's Account form: a hero panel with the way back, then a
 * sheet of numbered sections, two fields to a row, each control carrying a glyph
 * and a placeholder that shows what the answer looks like, with a sticky preview
 * of the record beside it.
 *
 * What is generated rather than written is everything inside those sections. The
 * fields, their order, which section each belongs to, its placeholder and its
 * glyph all come from `/schema/{resource}`, which reads the table — the same
 * introspection the server validates against, so a form drawn here and the rules
 * that check it cannot drift apart, and a column added to a migration appears
 * without anyone editing the SPA.
 *
 * The nine document screens that carry line items keep their own hand-written
 * forms; a generated grid of inputs cannot express a line editor.
 */
import { computed, reactive, ref, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import client from '../api/client';
import HbHero from '../components/HbHero.vue';
import { useToast } from '../composables/useToast';
import '@/../css/resource-form.css';

const route = useRoute();
const router = useRouter();
const toast = useToast();

const schema = ref(null);
const form = reactive({});
const errors = ref({});
const options = reactive({});
const loading = ref(true);
const saving = ref(false);
const failed = ref('');

const resource = computed(() => route.meta.resource);
const listPath = computed(() => route.meta.listPath ?? `/${resource.value}`);
const id = computed(() => route.params.id ?? null);
const isEdit = computed(() => id.value !== null);
const singular = computed(() => route.meta.singular ?? schema.value?.singular ?? 'Record');
const heading = computed(() => `${isEdit.value ? 'Update' : 'Create'} ${singular.value}`);

const fieldsIn = (section) => (schema.value?.fields ?? []).filter((f) => f.section === section);

/** A textarea, and a lone field closing a section, are given the whole row. */
const isWide = (field) => field.type === 'textarea';

/* ── the preview beside the form ────────────────────────────────────────── */

const pick = (...names) => names.map((n) => form[n]).find((v) => v !== undefined && v !== null && v !== '') ?? '';

const previewName = computed(() => {
    const parts = [form.first_name, form.last_name].filter(Boolean).join(' ');

    return parts || pick('name', 'title', 'number', 'code', 'asset_code', 'skill_name') || `New ${singular.value.toLowerCase()}`;
});

const previewSub = computed(() => pick('code', 'number', 'employee_code', 'asset_code', 'email') || 'not saved yet');

const initials = computed(() =>
    String(previewName.value)
        .split(/[\s-]+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((w) => w[0].toUpperCase())
        .join('') || '—',
);

/** Whatever the record classifies itself by, shown as chips. */
const previewTags = computed(() =>
    ['type', 'status', 'severity', 'priority', 'criticality', 'level', 'employment_type']
        .filter((key) => form[key] !== undefined && form[key] !== '' && form[key] !== null)
        .map((key) => {
            const field = schema.value?.fields.find((f) => f.name === key);
            const label = field?.options?.find((o) => String(o.value) === String(form[key]))?.label;

            return { key, icon: field?.icon ?? 'mdi-shape-outline', text: label ?? form[key] };
        }),
);

const requiredLeft = computed(() =>
    (schema.value?.fields ?? []).filter((f) => f.required && (form[f.name] === '' || form[f.name] === null)).length,
);

/* ── loading ────────────────────────────────────────────────────────────── */

/**
 * What to call a related record in a picker. Registers name their rows
 * differently — a code, a number, a name — so the first one present wins.
 */
function labelFor(row) {
    const name = row.name ?? row.description ?? [row.first_name, row.last_name].filter(Boolean).join(' ');
    const key = row.code ?? row.number ?? row.employee_code ?? '';

    return [key, name].filter(Boolean).join(' — ') || `#${row.id}`;
}

async function loadOptions(field) {
    if (!field.resource) return;

    try {
        const { data } = await client.get(`/${field.resource}`, { params: { per_page: 100 } });
        const rows = data.data?.data ?? data.data ?? [];

        options[field.name] = rows.map((r) => ({ value: r.id, label: labelFor(r) }));
    } catch {
        // A picker that cannot be filled falls back to a plain id box.
        options[field.name] = null;
    }
}

/**
 * A new record starts at the column's own default, not at empty.
 *
 * `is_active` defaults to true on most of these tables; a form that opened every
 * checkbox at "No" and then posted it would have created every item switched off.
 */
function blank() {
    for (const field of schema.value.fields) {
        if (field.type === 'checkbox') {
            form[field.name] = field.default ?? false;
        } else {
            form[field.name] = field.default ?? '';
        }
    }
}

async function load() {
    loading.value = true;
    failed.value = '';
    errors.value = {};

    try {
        const { data } = await client.get(`/schema/${resource.value}`);
        schema.value = data.data;

        blank();

        if (isEdit.value) {
            const record = await client.get(`/${resource.value}/${id.value}`);
            const row = record.data.data ?? {};

            for (const field of schema.value.fields) {
                if (row[field.name] === undefined || row[field.name] === null) continue;

                // A date arrives as an ISO timestamp; a date input wants Y-m-d.
                const value = ['date', 'datetime-local'].includes(field.type) && typeof row[field.name] === 'string'
                    ? row[field.name].slice(0, field.type === 'date' ? 10 : 16)
                    : row[field.name];

                form[field.name] = field.type === 'checkbox' ? Boolean(value) : value;
            }
        }

        await Promise.all(schema.value.fields.filter((f) => f.type === 'relation').map(loadOptions));
    } catch (e) {
        failed.value = e.response?.data?.message ?? 'Could not open this form.';
    } finally {
        loading.value = false;
    }
}

function reset() {
    errors.value = {};

    if (isEdit.value) {
        load();
    } else {
        blank();
        toast.info('Form cleared.');
    }
}

async function save() {
    saving.value = true;
    errors.value = {};

    /*
     * A blank field means "leave it alone", not "store an empty string" — an empty
     * string fails a numeric or date rule that a missing key would have passed.
     * On create the key is dropped entirely so the column's own default applies;
     * on edit it is sent as null, which is how a value gets cleared.
     */
    const payload = Object.fromEntries(
        Object.entries(form)
            .filter(([, v]) => isEdit.value || v !== '')
            .map(([k, v]) => [k, v === '' ? null : v]),
    );

    try {
        if (isEdit.value) {
            await client.put(`/${resource.value}/${id.value}`, payload);
        } else {
            await client.post(`/${resource.value}`, payload);
        }

        toast.success(`${singular.value} ${isEdit.value ? 'updated' : 'created'}.`);
        router.push(listPath.value);
    } catch (e) {
        if (e.response?.status === 422) {
            errors.value = e.response.data.errors ?? {};
            toast.error('Please check the highlighted fields.');
        } else {
            toast.error(e.response?.data?.message ?? 'Could not save.');
        }
    } finally {
        saving.value = false;
    }
}

watch(() => [route.meta.resource, route.params.id], load, { immediate: true });
</script>

<template>
  <div class="rf">
    <HbHero :icon="isEdit ? 'mdi-pencil-outline' : 'mdi-plus'" :title="heading">
      <template #action>
        <router-link class="hb-btn-ghost" :to="listPath">
          <i class="mdi mdi-arrow-left" />Back to list
        </router-link>
      </template>
    </HbHero>

    <div v-if="loading" class="rf-sheet"><loading-spinner /></div>

    <div v-else-if="failed" class="rf-sheet">
      <empty-state :message="failed" />
    </div>

    <div v-else class="rf-sheet">
      <form id="resource-form" novalidate @submit.prevent="save">
        <div class="rf-grid">
          <div>
            <div v-for="section in schema.sections" :key="section.key" class="rf-sect">
              <div class="rf-sect-head">
                <span class="rf-no">{{ section.no }}</span>
                <h2>{{ section.title }}</h2>
                <span class="rf-hint">{{ section.hint }}</span>
              </div>

              <div class="rf-frow">
                <div
                  v-for="field in fieldsIn(section.key)"
                  :key="field.name"
                  class="rf-fg"
                  :class="isWide(field) && 'wide'">
                  <label :for="`f-${field.name}`">
                    {{ field.label }}
                    <span v-if="field.required" class="rf-req" aria-hidden="true">*</span>
                  </label>

                  <!-- A boolean is a switch-shaped row, not a box with a glyph. -->
                  <label v-if="field.type === 'checkbox'" class="rf-check" :for="`f-${field.name}`">
                    <input :id="`f-${field.name}`" v-model="form[field.name]" type="checkbox" />
                    {{ form[field.name] ? 'Yes' : 'No' }}
                  </label>

                  <div
                    v-else
                    class="rf-in"
                    :class="[
                      errors[field.name] && 'err',
                      field.type === 'textarea' && 'is-area',
                      (field.type === 'select' || (field.type === 'relation' && options[field.name]?.length)) && 'is-select',
                    ]">
                    <span class="lead"><i class="mdi" :class="field.icon" /></span>

                    <textarea
                      v-if="field.type === 'textarea'"
                      :id="`f-${field.name}`"
                      v-model="form[field.name]"
                      rows="3"
                      :placeholder="field.placeholder" />

                    <select
                      v-else-if="field.type === 'select'"
                      :id="`f-${field.name}`"
                      v-model="form[field.name]">
                      <option value="">{{ field.placeholder }}</option>
                      <option v-for="o in field.options" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>

                    <select
                      v-else-if="field.type === 'relation' && options[field.name]?.length"
                      :id="`f-${field.name}`"
                      v-model="form[field.name]">
                      <option value="">{{ field.placeholder }}</option>
                      <option v-for="o in options[field.name]" :key="o.value" :value="o.value">{{ o.label }}</option>
                    </select>

                    <input
                      v-else
                      :id="`f-${field.name}`"
                      v-model="form[field.name]"
                      :type="field.type === 'relation' ? 'number' : field.type"
                      :step="field.step"
                      :maxlength="field.maxlength"
                      :placeholder="field.placeholder"
                      autocomplete="off" />
                  </div>

                  <div class="rf-msg" :class="errors[field.name] && 'err'">
                    {{ errors[field.name]?.[0] ?? '' }}
                  </div>
                </div>
              </div>
            </div>

            <div class="rf-foot">
              <button type="submit" class="rf-save" :disabled="saving">
                <i class="mdi" :class="saving ? 'mdi-loading animate-spin' : 'mdi-content-save-outline'" />
                {{ saving ? 'Saving…' : `${isEdit ? 'Update' : 'Save'} ${singular}` }}
              </button>
              <button type="button" class="rf-plain" @click="reset">
                <i class="mdi mdi-backup-restore" />Reset
              </button>
              <router-link class="rf-plain spacer" :to="listPath">Cancel</router-link>
            </div>
          </div>

          <aside class="rf-aside">
            <div class="rf-stick">
              <div class="rf-cap">Live preview · as it appears in the list</div>
              <div class="rf-preview">
                <div class="rf-pv-top">
                  <span class="rf-pv-av">{{ initials }}</span>
                  <div style="min-width: 0">
                    <div class="rf-pv-nm">{{ previewName }}</div>
                    <div class="rf-pv-em">{{ previewSub }}</div>
                  </div>
                </div>

                <div v-if="previewTags.length" class="rf-pv-tags">
                  <span v-for="tag in previewTags" :key="tag.key" class="rf-tag">
                    <i class="mdi" :class="tag.icon" />{{ tag.text }}
                  </span>
                </div>

                <div class="rf-pv-note">
                  <template v-if="requiredLeft">
                    {{ requiredLeft }} required {{ requiredLeft === 1 ? 'field is' : 'fields are' }} still empty.
                    They are the ones marked <span class="rf-req">*</span>.
                  </template>
                  <template v-else>
                    Everything required is filled in. Fields left blank take the database's own default.
                  </template>
                </div>
              </div>
            </div>
          </aside>
        </div>
      </form>
    </div>
  </div>
</template>
