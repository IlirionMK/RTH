<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { getMap } from '@/api/map'
import { getStatistics } from '@/api/statistics'
import MapLegend from '@/components/MapLegend.vue'
import PageHeader from '@/components/PageHeader.vue'
import TripMap from '@/components/TripMap.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { countryName, formatDistance } from '@/lib/format'
import { visitedCountryColor } from '@/lib/map'
import type { Statistics, TripsMap } from '@/types/map'

const { t, locale } = useI18n()

const year = ref<number | null>(null)
const tripsMap = ref<TripsMap | null>(null)
const statistics = ref<Statistics | null>(null)
const state = ref<'loading' | 'error' | 'ready'>('loading')
const selectedLine = ref<number | null>(null)

const lines = computed(() => tripsMap.value?.features ?? [])
const selected = computed(() => lines.value.find((line) => line.id === selectedLine.value)?.properties)
const countries = computed(() =>
  (statistics.value?.countries ?? [])
    .map((code) => ({ code, name: countryName(code, locale.value) }))
    .sort((a, b) => a.name.localeCompare(b.name, locale.value)),
)
const summary = computed(() =>
  statistics.value
    ? t('map.summary', { trips: statistics.value.trips_count, countries: statistics.value.countries_count })
    : undefined,
)

const filter = computed({
  get: () => (year.value === null ? 'all' : String(year.value)),
  set: (value: unknown) => {
    if (typeof value === 'string' && value !== '') {
      year.value = value === 'all' ? null : Number(value)
    }
  },
})

async function load(): Promise<void> {
  try {
    const [map, stats] = await Promise.all([getMap(year.value), getStatistics(year.value)])
    tripsMap.value = map
    statistics.value = stats
    selectedLine.value = null
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

function retry(): void {
  state.value = 'loading'
  load()
}

watch(year, load)
onMounted(load)
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <PageHeader :title="$t('map.title')" :description="summary" />
      <ToggleGroup
        v-if="statistics?.years.length"
        v-model="filter"
        type="single"
        variant="outline"
        :aria-label="$t('map.year')"
        class="flex-wrap"
      >
        <ToggleGroupItem
          value="all"
          class="data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground data-[state=on]:hover:bg-primary/90 data-[state=on]:hover:text-primary-foreground"
        >
          {{ $t('map.all') }}
        </ToggleGroupItem>
        <ToggleGroupItem
          v-for="item in statistics.years"
          :key="item"
          :value="String(item)"
          class="tabular-nums data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground data-[state=on]:hover:bg-primary/90 data-[state=on]:hover:text-primary-foreground"
        >
          {{ item }}
        </ToggleGroupItem>
      </ToggleGroup>
    </div>

    <div v-if="state === 'loading'" class="grid gap-6 lg:grid-cols-[1fr_20rem]" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-[60svh] min-h-80 rounded-xl" />
      <Skeleton class="h-80 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('map.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="retry">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <div v-else class="grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
      <TripMap
        :lines="lines"
        :countries="statistics?.countries ?? []"
        :selected-line="selectedLine"
        :label="$t('map.region')"
        @select-line="selectedLine = $event"
      />

      <div class="flex flex-col gap-4">
        <Card aria-live="polite">
          <CardContent class="flex flex-col gap-2">
            <template v-if="selected">
              <p class="text-sm text-muted-foreground">{{ $t('map.selectedTrip') }}</p>
              <p class="text-lg font-semibold">{{ selected.trip_name }}</p>
              <p class="text-sm text-muted-foreground tabular-nums">
                {{ $t(`transport.${selected.transport}`) }} · {{ formatDistance(selected.distance_km, locale) }}
              </p>
              <Button variant="outline" size="sm" class="self-start" as-child>
                <RouterLink :to="{ name: 'trips.map', params: { id: selected.trip_id } }">
                  {{ $t('map.openTripMap') }}
                </RouterLink>
              </Button>
            </template>
            <p v-else class="text-sm text-muted-foreground">
              {{ $t(lines.length ? 'map.pickLine' : 'map.empty') }}
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{{ $t('map.countries', { count: countries.length }) }}</CardTitle>
          </CardHeader>
          <CardContent>
            <ul v-if="countries.length" class="flex flex-col gap-2 text-sm">
              <li v-for="country in countries" :key="country.code" class="flex items-center gap-2">
                <span
                  class="size-3 shrink-0 rounded-sm opacity-60"
                  :style="{ backgroundColor: visitedCountryColor }"
                  aria-hidden="true"
                />
                {{ country.name }}
              </li>
            </ul>
            <p v-else class="text-sm text-muted-foreground">{{ $t('map.noCountries') }}</p>
          </CardContent>
        </Card>

        <MapLegend countries />
      </div>
    </div>
  </div>
</template>
