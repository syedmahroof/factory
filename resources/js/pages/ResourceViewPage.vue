<script setup>
/**
 * One record, read-only.
 *
 * Same schema as the form, so what a screen shows and what it edits are the same
 * list of fields — a column added to the table appears in both without an edit
 * here. Values are shown as they read rather than as they are stored: a boolean
 * is Yes/No, an enum is its label, a foreign key is the record it points at.
 */
import { computed, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import client from '../api/client';
import HbHero from '../components/HbHero.vue';

const route = useRoute();

const schema = ref(null);
const record = ref({});
const related = ref({});
const loading = ref(true);
const failed = ref('');

const resource = computed(() => route.meta.resource);
const listPath = computed(() => route.meta.listPath ?? `/${resource.value}`);
const id = computed(() => route.params.id);
const singular = computed(() => route.meta.singular ?? schema.value?.singular ?? 'Record');

/** The line a record is known by, for the heading. */
const heading = computed(() => {
    const r = record.value;

    return [r.code ?? r.number, r.name ?? r.description].filter(Boolean).join(' — ') || `#${id.value}`;
});

function displayed(field) {
    const value = record.value[field.name];

    if (value === null || value === undefined || value === '') return '—';

    if (field.type === 'checkbox') return value ? 'Yes' : 'No';

    if (field.type === 'select') {
        return field.options?.find((o) => String(o.value) === String(value))?.label ?? value;
    }

    if (field.type === 'relation') {
        return related.value[field.name] ?? `#${value}`;
    }

    return value;
}

/** A foreign key on its own tells the reader nothing, so it is looked up. */
async function resolveRelation(field) {
    const value = record.value[field.name];

    if (!field.resource || !value) return;

    try {
        const { data } = await client.get(`/${field.resource}/${value}`);
        const row = data.data ?? {};

        related.value[field.name] =
            [row.code ?? row.number, row.name ?? row.description].filter(Boolean).join(' — ') || `#${value}`;
    } catch {
        related.value[field.name] = `#${value}`;
    }
}

async function load() {
    loading.value = true;
    failed.value = '';
    related.value = {};

    try {
        const [s, r] = await Promise.all([
            client.get(`/schema/${resource.value}`),
            client.get(`/${resource.value}/${id.value}`),
        ]);

        schema.value = s.data.data;
        record.value = r.data.data ?? {};

        await Promise.all(schema.value.fields.filter((f) => f.type === 'relation').map(resolveRelation));
    } catch (e) {
        failed.value = e.response?.data?.message ?? 'Could not open this record.';
    } finally {
        loading.value = false;
    }
}

watch(() => [route.meta.resource, route.params.id], load, { immediate: true });
</script>

<template>
  <div>
    <HbHero icon="mdi-eye-outline" :title="heading">
      <template #action>
        <router-link class="hb-btn-ghost" :to="listPath">
          <i class="mdi mdi-arrow-left" />Back to list
        </router-link>
        <router-link class="hb-btn-white" :to="`${listPath}/${id}/edit`">
          <i class="mdi mdi-pencil-outline" />Edit {{ singular }}
        </router-link>
      </template>
    </HbHero>

    <div v-if="loading" class="card mt-3.5 p-8"><loading-spinner /></div>

    <div v-else-if="failed" class="card mt-3.5">
      <empty-state :message="failed" />
    </div>

    <div v-else class="card mt-3.5">
      <dl class="card-body grid grid-cols-1 gap-x-6 gap-y-0 sm:grid-cols-2">
        <div
          v-for="field in schema.fields"
          :key="field.name"
          class="flex items-baseline justify-between gap-4 border-b border-hb-row py-2.5 last:border-0">
          <dt class="text-hb-body text-hb-mut">{{ field.label }}</dt>
          <dd class="min-w-0 text-right text-hb-body font-semibold break-words text-hb-ink">
            {{ displayed(field) }}
          </dd>
        </div>
      </dl>
    </div>
  </div>
</template>
