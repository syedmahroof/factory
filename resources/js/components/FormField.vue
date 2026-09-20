<template>
  <div>
    <!-- .hb-lbl: 10.5px, 700, tracked and uppercased. -->
    <label class="hb-lbl">
      {{ label }} <span v-if="required" class="text-hb-red">*</span>
    </label>
    <slot />
    <p v-if="error" class="mt-1 text-hb-sm text-hb-red">{{ error }}</p>
  </div>
</template>
<script setup>
defineProps({ label: String, error: String, required: Boolean });
</script>
