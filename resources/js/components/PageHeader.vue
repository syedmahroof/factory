<template>
  <!--
    | The screen's toolbar.
    |
    | The title band above already names the page — it takes the title from
    | the route's own meta, including the create/edit variants — so repeating it
    | here in dark ink would print the same words twice, the second time on top
    | of the band itself.
    |
    | The default slot is still accepted, and ignored, so none of the 68 screens
    | that pass a title need an edit.
  -->
  <div v-if="$slots.actions" class="page-toolbar mb-4 flex flex-wrap items-center justify-end gap-2">
    <slot name="actions" />
  </div>
</template>
