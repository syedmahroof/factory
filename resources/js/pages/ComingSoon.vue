<script setup>
/**
 * The stand-in behind a menu entry whose screen has not been built.
 *
 * One component for all of them rather than a stub file per route: the only
 * things that differ are the title, the glyph and the sentence describing what
 * the screen will do, and all three already live on the route's own meta — so a
 * new placeholder is a line in the route table, not a new file.
 *
 * It says plainly that the screen does not exist yet. A menu entry that opens a
 * blank sheet reads as a screen that is broken; one that opens this reads as a
 * screen that is coming, which is the truth.
 */
import { computed } from 'vue';
import { useRoute } from 'vue-router';

const route = useRoute();

const title = computed(() => route.meta?.title ?? 'This screen');
const icon = computed(() => route.meta?.icon ?? 'mdi-progress-wrench');
const blurb = computed(() => route.meta?.blurb ?? '');
</script>

<template>
  <div class="card">
    <div class="px-6 pt-12 pb-14 text-center">
      <span
        class="mx-auto mb-4 flex size-14 items-center justify-center rounded-hb-panel bg-hb-sel text-[26px] text-hb-brand"
        aria-hidden="true">
        <i class="mdi" :class="icon" />
      </span>

      <p class="hb-lbl justify-center">Not built yet</p>
      <h2 class="mt-1 text-[19px] font-extrabold tracking-[-0.015em] text-hb-ink">{{ title }}</h2>

      <p v-if="blurb" class="mx-auto mt-2 max-w-[46ch] text-hb-body text-hb-mut">{{ blurb }}</p>

      <p class="mt-6 text-hb-sm text-hb-mut">
        The menu entry and the route are in place, so this screen can be built without touching navigation.
      </p>
    </div>
  </div>
</template>
