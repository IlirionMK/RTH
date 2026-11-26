<script setup lang="ts">
import { computed } from 'vue'

import { transportLines } from '@/lib/map'
import type { Transport } from '@/types/trip'

const props = defineProps<{
  transport: Transport
}>()

const line = computed(() => transportLines[props.transport])
const dash = computed(() => line.value.dash?.map((length) => length * 3).join(' '))
</script>

<template>
  <svg class="h-2 w-7 shrink-0" viewBox="0 0 28 8" aria-hidden="true">
    <line
      x1="3"
      y1="4"
      x2="25"
      y2="4"
      :stroke="line.color"
      stroke-width="3"
      stroke-linecap="round"
      :stroke-dasharray="dash"
    />
  </svg>
</template>
