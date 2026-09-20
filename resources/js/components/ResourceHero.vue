<script setup>
/**
 * The panel a register opens with.
 *
 * ip_new's list screens open with HbHero carrying a stats band and the primary
 * action; this is that, for the fifty registers here. It fetches its own figures
 * from `/stats/{resource}`, so a list page adds a title card by naming its
 * resource rather than by plumbing counts through its own load().
 *
 * Failing to fetch is not an error worth showing: the stats band is context, and
 * a register whose counts did not arrive still has its table. The hero just
 * renders without them.
 */
import { computed, ref, watch } from 'vue';
import client from '../api/client';
import HbHero from './HbHero.vue';

const props = defineProps({
    /** The API slug, which is not always the path — the RMA register is /rma on /rmas. */
    resource: { type: String, required: true },
    title: { type: String, required: true },
    icon: { type: String, default: 'mdi-view-grid-outline' },

    /** Where the primary button goes. Omitted for a register with nothing to create. */
    createTo: { type: String, default: '' },
    createLabel: { type: String, default: '' },
});

const stats = ref([]);

/*
 * Every figure sits in the band, none of them pulled out as the gauge. That is
 * ip_new's list layout, and it is what keeps the primary button opposite the
 * title: HbHero drops its actions under the meta line whenever a lead exists,
 * and on a register the button belongs at the top right where every other
 * register has it.
 */
const heroStats = computed(() =>
    stats.value.map((s) => ({
        label: s.label,
        icon: s.icon,
        value: Number(s.value ?? 0).toLocaleString('en-US'),
        dot: s.dot,
    })),
);

async function load() {
    // A report screen has a title card but no register behind it to count.
    if (!props.resource) {
        stats.value = [];

        return;
    }

    try {
        const { data } = await client.get(`/stats/${props.resource}`);
        stats.value = data.data?.stats ?? [];
    } catch {
        stats.value = [];
    }
}

watch(() => props.resource, load, { immediate: true });

defineExpose({ refresh: load });
</script>

<template>
  <HbHero :icon="icon" :title="title" :stats="heroStats">
    <template v-if="createTo" #action>
      <router-link class="hb-btn-white" :to="createTo">
        <i class="mdi mdi-plus-circle-outline" />{{ createLabel || 'New' }}
      </router-link>
    </template>
  </HbHero>
</template>
