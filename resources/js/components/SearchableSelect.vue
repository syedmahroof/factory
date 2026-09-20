<template>
  <select :value="modelValue" @change="$emit('update:modelValue', $event.target.value)" class="w-full rounded-hb-ctl border-hb-line text-hb-body">
    <option value="">{{ placeholder }}</option>
    <option v-for="opt in options" :key="opt.id || opt.value" :value="opt.id || opt.value">{{ opt.name || opt.label }}</option>
  </select>
</template>
<script setup>
defineProps({ modelValue: [String, Number], options: Array, placeholder: { type: String, default: 'Select...' } });
defineEmits(['update:modelValue']);
</script>
