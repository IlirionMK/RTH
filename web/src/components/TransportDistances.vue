<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { formatDistance } from '@/lib/format'
import { transportLines } from '@/lib/map'
import { transports } from '@/lib/transports'
import type { Transport } from '@/types/trip'

const props = defineProps<{
  distances: Record<Transport, number>
}>()

const { locale } = useI18n()

const largest = computed(() => Math.max(...transports.map((transport) => props.distances[transport]), 1))
</script>

<template>
  <ul class="flex flex-col gap-3">
    <li v-for="transport in transports" :key="transport" class="grid grid-cols-[6rem_1fr_auto] items-center gap-3 text-sm">
      <span class="flex items-center gap-2">
        <span class="size-3 shrink-0 rounded-sm" :style="{ backgroundColor: transportLines[transport].color }" aria-hidden="true" />
        {{ $t(`transport.${transport}`) }}
      </span>
      <span class="h-4" aria-hidden="true">
        <span
          v-if="distances[transport] > 0"
          class="block h-full min-w-1 rounded-r-sm"
          :style="{
            width: `${(distances[transport] / largest) * 100}%`,
            backgroundColor: transportLines[transport].color,
          }"
        />
      </span>
      <span class="text-right font-medium tabular-nums">{{ formatDistance(distances[transport], locale) }}</span>
    </li>
  </ul>
</template>
