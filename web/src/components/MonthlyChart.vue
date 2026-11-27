<script setup lang="ts">
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import type { SystemStatistics } from '@/types/admin'

const props = defineProps<{
  months: SystemStatistics['months']
}>()

const { t, locale } = useI18n()
const hovered = ref<string | null>(null)

const series = [
  { key: 'users', color: 'var(--chart-1)' },
  { key: 'trips', color: 'var(--chart-2)' },
] as const

const largest = computed(() => Math.max(...props.months.flatMap((month) => [month.users, month.trips]), 1))

function monthName(month: string, style: 'short' | 'long'): string {
  const [year = 1970, number = 1] = month.split('-').map(Number)

  return new Intl.DateTimeFormat(locale.value, { month: style, year: style === 'long' ? 'numeric' : undefined }).format(
    new Date(year, number - 1, 1),
  )
}

const detail = computed(() => {
  const month = props.months.find((item) => item.month === hovered.value)

  return month
    ? `${monthName(month.month, 'long')} · ${t('admin.newUsers')}: ${month.users} · ${t('admin.newTrips')}: ${month.trips}`
    : ''
})
</script>

<template>
  <div class="flex flex-col gap-3">
    <ul class="flex flex-wrap gap-x-4 gap-y-1 text-sm" aria-hidden="true">
      <li v-for="item in series" :key="item.key" class="flex items-center gap-2">
        <span class="size-3 rounded-sm" :style="{ backgroundColor: item.color }" />
        {{ $t(item.key === 'users' ? 'admin.newUsers' : 'admin.newTrips') }}
      </li>
    </ul>
    <p class="h-5 text-sm text-muted-foreground tabular-nums" aria-hidden="true">{{ detail }}</p>
    <div class="flex h-48 items-end gap-1 border-b border-border sm:gap-3" aria-hidden="true">
      <div
        v-for="month in months"
        :key="month.month"
        class="flex h-full flex-1 items-end justify-center gap-0.5 rounded-sm hover:bg-muted/60"
        @mouseenter="hovered = month.month"
        @mouseleave="hovered = null"
      >
        <span
          v-for="item in series"
          :key="item.key"
          class="w-2 max-w-4 flex-1 rounded-t-sm sm:w-3"
          :style="{ height: `${(month[item.key] / largest) * 100}%`, backgroundColor: item.color }"
        />
      </div>
    </div>
    <div class="flex gap-1 sm:gap-3" aria-hidden="true">
      <span v-for="month in months" :key="month.month" class="flex-1 text-center text-xs text-muted-foreground">
        {{ monthName(month.month, 'short') }}
      </span>
    </div>
    <table class="sr-only">
      <caption>{{ $t('admin.chart') }}</caption>
      <thead>
        <tr>
          <th scope="col">{{ $t('admin.month') }}</th>
          <th scope="col">{{ $t('admin.newUsers') }}</th>
          <th scope="col">{{ $t('admin.newTrips') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="month in months" :key="month.month">
          <th scope="row">{{ monthName(month.month, 'long') }}</th>
          <td>{{ month.users }}</td>
          <td>{{ month.trips }}</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>
