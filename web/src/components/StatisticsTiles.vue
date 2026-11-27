<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { Card, CardContent } from '@/components/ui/card'
import type { Statistics } from '@/types/map'

const props = defineProps<{
  statistics: Statistics
}>()

const { t, locale } = useI18n()

const tiles = computed(() => {
  const number = new Intl.NumberFormat(locale.value, { maximumFractionDigits: 0 })

  return [
    { label: t('stats.trips'), value: number.format(props.statistics.trips_count) },
    { label: t('stats.countries'), value: number.format(props.statistics.countries_count) },
    { label: t('stats.places'), value: number.format(props.statistics.places_count) },
    { label: t('stats.distance'), value: number.format(props.statistics.distance_km), unit: 'km' },
  ]
})
</script>

<template>
  <dl class="grid grid-cols-2 gap-4 lg:grid-cols-4">
    <Card v-for="tile in tiles" :key="tile.label" class="py-4">
      <CardContent class="flex flex-col gap-1">
        <dt class="text-sm text-muted-foreground">{{ tile.label }}</dt>
        <dd class="text-3xl font-semibold tabular-nums">
          {{ tile.value }}
          <span v-if="tile.unit" class="text-base font-normal text-muted-foreground">{{ tile.unit }}</span>
        </dd>
      </CardContent>
    </Card>
  </dl>
</template>
