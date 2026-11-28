<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { formatDistance } from '@/lib/format'
import { transportLines } from '@/lib/map'
import { transports } from '@/lib/transports'
import type { Statistics } from '@/types/map'
import type { Transport } from '@/types/trip'

const props = defineProps<{
  years: Statistics['distance_by_year']
}>()

const { t, locale } = useI18n()
const hovered = ref<{ year: number; transport: Transport } | null>(null)

const columns = computed(() => {
  const totals = props.years.map((row) => transports.reduce((sum, transport) => sum + row[transport], 0))
  const largest = Math.max(...totals, 1)

  return props.years.map((row, index) => ({
    year: row.year,
    total: totals[index] ?? 0,
    height: ((totals[index] ?? 0) / largest) * 100,
    parts: transports
      .filter((transport) => row[transport] > 0)
      .map((transport) => ({ transport, distance: row[transport], share: (row[transport] / (totals[index] || 1)) * 100 })),
  }))
})

const detail = computed(() => {
  const row = props.years.find((item) => item.year === hovered.value?.year)

  return hovered.value && row
    ? `${row.year} · ${t(`transport.${hovered.value.transport}`)}: ${formatDistance(row[hovered.value.transport], locale.value)}`
    : ''
})
</script>

<template>
  <div class="flex flex-col gap-3">
    <p class="h-5 text-sm text-muted-foreground tabular-nums" aria-hidden="true">{{ detail }}</p>
    <div class="flex h-48 items-end gap-4 border-b border-border" aria-hidden="true">
      <div v-for="column in columns" :key="column.year" class="flex h-full max-w-16 flex-1 flex-col items-center justify-end gap-1">
        <span class="text-xs font-medium tabular-nums">{{ formatDistance(column.total, locale) }}</span>
        <div class="flex w-full flex-col-reverse gap-0.5" :style="{ height: `${column.height}%` }">
          <div
            v-for="(part, index) in column.parts"
            :key="part.transport"
            class="w-full"
            :class="index === column.parts.length - 1 ? 'rounded-t-sm' : ''"
            :style="{ height: `${part.share}%`, backgroundColor: transportLines[part.transport].color }"
            @mouseenter="hovered = { year: column.year, transport: part.transport }"
            @mouseleave="hovered = null"
          />
        </div>
      </div>
    </div>
    <div class="flex gap-4" aria-hidden="true">
      <span v-for="column in columns" :key="column.year" class="max-w-16 flex-1 text-center text-xs text-muted-foreground tabular-nums">
        {{ column.year }}
      </span>
    </div>
    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm" aria-hidden="true">
      <li v-for="transport in transports" :key="transport" class="flex items-center gap-2">
        <span class="size-3 rounded-sm" :style="{ backgroundColor: transportLines[transport].color }" />
        {{ $t(`transport.${transport}`) }}
      </li>
    </ul>
    <div class="sr-only">
      <table>
        <caption>{{ $t('stats.byYear') }}</caption>
        <thead>
          <tr>
            <th scope="col">{{ $t('stats.year') }}</th>
            <th v-for="transport in transports" :key="transport" scope="col">{{ $t(`transport.${transport}`) }}</th>
            <th scope="col">{{ $t('stats.total') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in years" :key="row.year">
            <th scope="row">{{ row.year }}</th>
            <td v-for="transport in transports" :key="transport">{{ formatDistance(row[transport], locale) }}</td>
            <td>{{ formatDistance(columns[index]?.total ?? 0, locale) }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
