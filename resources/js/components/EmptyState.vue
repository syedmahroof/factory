<template>
  <!-- .hb-empty: centred, muted, generous above and below. -->
  <div class="px-0 pt-10 pb-9 text-center text-[14px] text-hb-mut">
    <i class="mdi mdi-tray-remove mb-1 block text-[26px]" />
    <p>{{ message || 'No records found.' }}</p>
    <slot />
  </div>
</template>
<script setup>
defineProps({ message: String });
</script>
