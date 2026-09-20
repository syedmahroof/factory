<script setup>
/**
 * The panel a screen opens with: avatar, eyebrow, title, a meta line, an action
 * slot, an optional stats band and an optional lead gauge.
 *
 * Ported from ip_new's components/ui/HbHero.vue. One difference, on purpose:
 * over there the component formats the figures itself through `utils/money`.
 * There is no such util here and the pages already carry their own formatters,
 * so `value` is taken as a finished display string and `money` is only a size —
 * a currency figure is longer than a count and is set smaller so it still fits.
 *
 * Stats are passed as [{ label, icon, value, dot, money, sub }] so a screen
 * declares what it shows rather than re-writing the grid each time. One stat may
 * carry `lead: true` to be pulled out of the band and set as the gauge on the
 * right, where it may also carry `progress` (0–100) and `foot`.
 *
 * A screen using this must set `meta.ownsHeading: true` on its route — the panel
 * draws over the layout's title band, and two headings on one band is one too
 * many. `meta.crumb: null` silences the breadcrumb with it.
 */
import { computed, useSlots } from 'vue';
import '@/../css/hb-hero.css';

const props = defineProps({
    icon: { type: String, default: 'mdi-view-grid-outline' },
    // Initials shown in the tile instead of a glyph, for a record's own page.
    mono: { type: String, default: '' },
    eyebrow: { type: String, default: 'Factory ERP · Administration' },
    title: { type: String, default: '' },
    stats: { type: Array, default: () => [] },
});

const slots = useSlots();

const lead = computed(() => props.stats.find((s) => s.lead) ?? null);
const band = computed(() => props.stats.filter((s) => !s.lead));

/*
 * With a gauge on the right there is no room beside it, so the buttons drop under
 * the meta line. Without one they stay where every register has them: opposite
 * the title.
 */
const actionsUnder = computed(() => Boolean(lead.value));

const display = (stat) => (stat.value == null || stat.value === '' ? '—' : stat.value);

const clamp = (v) => Math.min(Math.max(Number(v) || 0, 0), 100);
</script>

<template>
  <div class="hbh">
    <div class="hbh-top">
      <div class="hbh-main">
        <div class="hbh-id">
          <span class="hbh-av" :class="mono && 'mono'">
            <template v-if="mono">{{ mono }}</template>
            <i v-else class="mdi" :class="icon" />
          </span>
          <div>
            <div class="hbh-eyebrow">{{ eyebrow }}</div>
            <h1 class="hbh-name"><slot name="title">{{ title }}</slot></h1>
          </div>
        </div>

        <div v-if="slots.meta" class="hbh-meta"><slot name="meta" /></div>

        <div v-if="slots.action && actionsUnder" class="hbh-acts under"><slot name="action" /></div>
      </div>

      <div v-if="lead" class="hbh-gauge">
        <div class="hbh-gauge-eyebrow">{{ lead.label }}</div>
        <div class="hbh-gauge-val">{{ display(lead) }}</div>
        <div v-if="lead.progress != null" class="hbh-track">
          <i :style="{ width: `${clamp(lead.progress)}%` }" />
        </div>
        <div v-if="lead.sub" class="hbh-gauge-sub">{{ lead.sub }}</div>
        <div v-if="lead.foot" class="hbh-gauge-foot">{{ lead.foot }}</div>
      </div>

      <div v-if="slots.action && !actionsUnder" class="hbh-acts"><slot name="action" /></div>
    </div>

    <div v-if="band.length" class="hbh-stats" :style="{ '--hbh-cols': band.length }">
      <div v-for="stat in band" :key="stat.label" class="hbh-stat">
        <div class="hbh-stat-lbl">
          <i class="mdi" :class="stat.icon ?? 'mdi-circle-medium'" />{{ stat.label }}
        </div>
        <div class="hbh-stat-val" :class="stat.money && 'money'">
          <span v-if="stat.dot" class="hbh-dot" :class="stat.dot" />{{ display(stat) }}
        </div>
        <div v-if="stat.sub" class="hbh-stat-sub">{{ stat.sub }}</div>
      </div>
    </div>
  </div>
</template>
