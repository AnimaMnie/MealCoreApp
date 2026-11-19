<script setup>
import { computed } from 'vue'

// Pierścień postępu (jak w starym dashboardzie).
const props = defineProps({
  percent: { type: Number, default: 0 },
  color: { type: String, default: 'var(--orange)' },
})

const r = 34
const c = 2 * Math.PI * r
const offset = computed(() => c * (1 - Math.min(100, Math.max(0, props.percent)) / 100))
</script>

<template>
  <svg viewBox="0 0 80 80">
    <circle cx="40" cy="40" :r="r" fill="none" stroke="var(--line)" stroke-width="7" />
    <circle
      cx="40" cy="40" :r="r" fill="none" :stroke="color" stroke-width="7" stroke-linecap="round"
      :stroke-dasharray="c" :stroke-dashoffset="offset"
    />
  </svg>
</template>
