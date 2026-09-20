<template>
  <Teleport to="body">
    <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50" @click.self="$emit('cancel')">
      <div class="bg-white rounded-hb-panel shadow-xl max-w-md w-full mx-4 p-6">
        <h3 class="text-lg font-semibold text-hb-ink mb-2">{{ title }}</h3>
        <p class="text-hb-body text-hb-mut mb-6">{{ message }}</p>
        <div class="flex justify-end gap-3">
          <button @click="$emit('cancel')" class="btn btn-secondary">Cancel</button>
          <button @click="$emit('confirm')" :class="danger ? 'bg-hb-red hover:bg-hb-red' : 'btn-primary'" class="btn">{{ confirmText }}</button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
<script setup>
defineProps({ show: Boolean, title: String, message: String, confirmText: { type: String, default: 'Confirm' }, danger: Boolean });
defineEmits(['confirm', 'cancel']);
</script>
