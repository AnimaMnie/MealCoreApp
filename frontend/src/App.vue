<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import AppLayout from './components/AppLayout.vue'
import { toastState } from './toast'

const route = useRoute()
const isGuestPage = computed(() => route.meta.guest === true)
// Zanim router dopasuje pierwszą trasę, route.matched jest puste - nic nie renderujemy.
const ready = computed(() => route.matched.length > 0)
</script>

<template>
  <template v-if="!ready" />
  <RouterView v-else-if="isGuestPage" />
  <AppLayout v-else>
    <RouterView />
  </AppLayout>

  <div class="toast" :class="{ show: toastState.visible, error: toastState.error }">
    {{ toastState.message }}
  </div>
</template>
