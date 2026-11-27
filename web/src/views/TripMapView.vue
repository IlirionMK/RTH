<script setup lang="ts">
import { ArrowLeft, Pencil } from '@lucide/vue'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { getTrip } from '@/api/trips'
import MapLegend from '@/components/MapLegend.vue'
import TransportLine from '@/components/TransportLine.vue'
import TripMap from '@/components/TripMap.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import { countryName, formatDates, formatDistance } from '@/lib/format'
import { useAuthStore } from '@/stores/auth'
import type { LineFeature, PointFeature } from '@/types/map'
import type { Trip } from '@/types/trip'

const props = defineProps<{
  id: number
}>()

const { locale } = useI18n()
const auth = useAuthStore()

const trip = ref<Trip | null>(null)
const state = ref<'loading' | 'error' | 'ready'>('loading')
const selectedLine = ref<number | null>(null)
const selectedPoint = ref<number | null>(null)

const isOwn = computed(() => !trip.value || trip.value.user_id === auth.user?.id)
const points = computed(() => trip.value?.points ?? [])
const segments = computed(() =>
  (trip.value?.segments ?? []).map((segment, index) => ({
    ...segment,
    index: index + 1,
    from: points.value.find((point) => point.id === segment.from_point_id)?.name ?? '',
    to: points.value.find((point) => point.id === segment.to_point_id)?.name ?? '',
  })),
)

const lines = computed<LineFeature[]>(() =>
  segments.value.map((segment) => ({
    type: 'Feature',
    id: segment.id,
    geometry: segment.geometry,
    properties: { transport: segment.transport },
  })),
)
const pointFeatures = computed<PointFeature[]>(() =>
  points.value.map((point) => ({
    type: 'Feature',
    id: point.id,
    geometry: { type: 'Point', coordinates: [point.longitude, point.latitude] },
    properties: { name: point.name },
  })),
)

const summary = computed(() => {
  if (!trip.value) {
    return ''
  }

  const distance = segments.value.reduce((sum, segment) => sum + segment.distance_km, 0)
  const countries = (trip.value.countries ?? []).map((code) => countryName(code, locale.value))

  return [formatDates(trip.value.starts_on, trip.value.ends_on, locale.value), formatDistance(distance, locale.value), ...(countries.length ? [countries.join(', ')] : [])].join(' · ')
})

const segment = computed(() => segments.value.find((item) => item.id === selectedLine.value))
const point = computed(() => points.value.find((item) => item.id === selectedPoint.value))

function selectLine(id: number): void {
  selectedLine.value = id
  selectedPoint.value = null
}

function selectPoint(id: number): void {
  selectedPoint.value = id
  selectedLine.value = null
}

async function load(): Promise<void> {
  state.value = 'loading'

  try {
    trip.value = await getTrip(props.id)
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

onMounted(load)
</script>

<template>
  <div class="flex flex-col gap-6">
    <div class="flex flex-col gap-3">
      <RouterLink
        :to="isOwn ? { name: 'trips.index' } : { name: 'discover.show', params: { id: trip?.user_id } }"
        class="inline-flex w-fit items-center gap-1 rounded-sm text-sm text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
      >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ $t(isOwn ? 'trip.back' : 'trip.backToProfile') }}
      </RouterLink>
      <div class="flex flex-wrap items-start justify-between gap-4">
        <header class="flex min-w-0 flex-col gap-1">
          <h1 class="text-2xl font-semibold text-balance md:text-3xl">
            <span :style="{ viewTransitionName: `trip-${id}` }">{{ trip?.name }}</span>
          </h1>
          <p class="text-pretty text-muted-foreground tabular-nums">{{ summary }}</p>
        </header>
        <Button v-if="isOwn" variant="outline" as-child>
          <RouterLink :to="{ name: 'trips.edit', params: { id } }">
            <Pencil aria-hidden="true" />
            {{ $t('trip.edit') }}
          </RouterLink>
        </Button>
      </div>
    </div>

    <div v-if="state === 'loading'" class="grid gap-6 lg:grid-cols-[1fr_20rem]" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-[60svh] min-h-80 rounded-xl" />
      <Skeleton class="h-80 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('map.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="load">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <div v-else class="grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
      <TripMap
        :lines="lines"
        :points="pointFeatures"
        :countries="trip?.countries ?? []"
        :selected-line="selectedLine"
        :selected-point="selectedPoint"
        :label="$t('map.tripRegion', { name: trip?.name })"
        @select-line="selectLine"
        @select-point="selectPoint"
      />

      <div class="flex flex-col gap-4">
        <Card aria-live="polite">
          <CardContent class="flex flex-col gap-2">
            <template v-if="segment">
              <p class="text-sm text-muted-foreground">
                {{ $t('map.segment', { index: segment.index, total: segments.length }) }}
              </p>
              <p class="text-lg font-semibold">{{ segment.from }} → {{ segment.to }}</p>
              <dl class="grid grid-cols-2 gap-2 text-sm">
                <div>
                  <dt class="text-muted-foreground">{{ $t('map.transport') }}</dt>
                  <dd class="font-medium">{{ $t(`transport.${segment.transport}`) }}</dd>
                </div>
                <div>
                  <dt class="text-muted-foreground">{{ $t('map.distance') }}</dt>
                  <dd class="font-medium tabular-nums">{{ formatDistance(segment.distance_km, locale) }}</dd>
                </div>
              </dl>
              <p v-if="segment.is_approximate" class="text-sm text-muted-foreground">{{ $t('map.approximate') }}</p>
            </template>
            <template v-else-if="point">
              <p class="text-lg font-semibold">{{ point.name }}</p>
              <p v-if="point.country_code" class="text-sm text-muted-foreground">
                {{ countryName(point.country_code, locale) }}
              </p>
            </template>
            <p v-else class="text-sm text-muted-foreground">{{ $t('map.pick') }}</p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{{ $t('map.segments') }}</CardTitle>
            <CardDescription v-if="segments.length === 0">{{ $t('map.noSegments') }}</CardDescription>
          </CardHeader>
          <CardContent v-if="segments.length">
            <ol class="flex flex-col gap-1">
              <li v-for="item in segments" :key="item.id">
                <button
                  type="button"
                  class="flex w-full items-center gap-3 rounded-md px-2 py-2 text-left text-sm hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none aria-pressed:bg-muted aria-pressed:font-medium"
                  :aria-pressed="selectedLine === item.id"
                  @click="selectLine(item.id)"
                >
                  <TransportLine :transport="item.transport" />
                  <span class="min-w-0 flex-1 truncate">{{ item.index }}. {{ item.from }} → {{ item.to }}</span>
                  <span class="text-muted-foreground tabular-nums">{{ formatDistance(item.distance_km, locale) }}</span>
                </button>
              </li>
            </ol>
          </CardContent>
        </Card>

        <MapLegend :countries="Boolean(trip?.countries?.length)" />
      </div>
    </div>
  </div>
</template>
