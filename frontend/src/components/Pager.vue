<script setup>
// Paginacja na podstawie pola "meta" z odpowiedzi Laravela.
defineProps({ meta: { type: Object, default: null } })
const emit = defineEmits(['page'])
</script>

<template>
  <div v-if="meta && meta.last_page > 1" class="pager">
    <button :disabled="meta.current_page <= 1" @click="emit('page', meta.current_page - 1)">
      <i class="fa-solid fa-chevron-left"></i>
    </button>
    <span class="info">Strona {{ meta.current_page }} z {{ meta.last_page }} · {{ meta.total }} wyników</span>
    <button :disabled="meta.current_page >= meta.last_page" @click="emit('page', meta.current_page + 1)">
      <i class="fa-solid fa-chevron-right"></i>
    </button>
  </div>
</template>
