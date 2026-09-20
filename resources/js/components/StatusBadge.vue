<template>
  <span :class="badgeClass" class="hb-pill">{{ label || value }}</span>
</template>
<script setup>
import { computed } from 'vue';

const props = defineProps({ value: String, label: String });

/*
 * The four pill hues the ip_new registers use — green for live, blue for
 * finished, red for stopped, amber for waiting — with everything unrecognised
 * falling to the neutral chip rather than inventing a colour.
 */
const badgeClass = computed(() => ({
    draft: 'neutral',
    pending: 'idle',
    open: 'idle',
    approved: 'done',
    confirmed: 'done',
    in_progress: 'ok',
    active: 'ok',
    completed: 'done',
    closed: 'done',
    cancelled: 'off',
    rejected: 'off',
}[props.value] ?? 'neutral'));
</script>
