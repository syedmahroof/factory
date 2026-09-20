<template>
  <div class="fixed top-4 right-4 z-50 flex flex-col gap-2" style="max-width: 380px">
    <div v-for="toast in toasts" :key="toast.id"
      class="animate-slide-in flex items-center gap-2.5 rounded-hb-ctl border border-hb-line border-l-4 bg-white py-2.5 pr-3 pl-3.5 shadow-hb-sheet"
      :class="{
        'border-l-hb-green': toast.type === 'success',
        'border-l-hb-red': toast.type === 'error',
        'border-l-hb-dot-o': toast.type === 'warning',
        'border-l-hb-brand': !['success', 'error', 'warning'].includes(toast.type),
      }">
      <i class="mdi text-base leading-none"
        :class="{
          'mdi-check-circle text-hb-green': toast.type === 'success',
          'mdi-alert-circle text-hb-red': toast.type === 'error',
          'mdi-alert text-hb-dot-o': toast.type === 'warning',
          'mdi-information text-hb-brand': !['success', 'error', 'warning'].includes(toast.type),
        }" />
      <span class="text-hb-body font-semibold text-hb-ink">{{ toast.message }}</span>
      <button type="button" aria-label="Dismiss"
        class="ml-auto text-hb-mut transition-colors hover:text-hb-ink"
        @click="remove(toast.id)">
        <i class="mdi mdi-close text-base leading-none" />
      </button>
    </div>
  </div>
</template>
<script setup>
import { useToast } from '../composables/useToast';
const { toasts } = useToast();
function remove(id) {
    const { toasts: t } = useToast();
    t.splice(t.findIndex(x => x.id === id), 1);
}
</script>
<style>
@keyframes slide-in { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
.animate-slide-in { animation: slide-in 0.3s ease-out; }
</style>
