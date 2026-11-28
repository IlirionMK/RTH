<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from 'cn'

const props = defineProps<{
  class?: HTMLAttributes['class']
  label?: string
}>()
</script>

<template>
  <div
    data-slot="table-container"
    :role="props.label ? 'region' : undefined"
    :aria-label="props.label"
    :tabindex="props.label ? 0 : undefined"
    class="relative w-full overflow-x-auto rounded-md focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
  >
    <table data-slot="table" :class="cn('w-full caption-bottom text-sm', props.class)">
      <slot />
    </table>
  </div>
</template>
