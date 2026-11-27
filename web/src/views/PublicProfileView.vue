<script setup lang="ts">
import { ArrowLeft, ChevronRight, Eye, Globe } from '@lucide/vue'
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import {
  getPublicProfile,
  getPublicProfileMap,
  getPublicProfileStatistics,
  listPublicProfileTrips,
} from '@/api/publicProfiles'
import StatisticsTiles from '@/components/StatisticsTiles.vue'
import TripMap from '@/components/TripMap.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import YearFilter from '@/components/YearFilter.vue'
import { formatDates, formatDistance, initials } from '@/lib/format'
import type { Statistics, TripsMap } from '@/types/map'
import type { PublicProfile } from '@/types/profile'
import type { Trip } from '@/types/trip'

const props = defineProps<{
  id: number
}>()

const { locale } = useI18n()

const profile = ref<PublicProfile | null>(null)
const trips = ref<Trip[]>([])
const tripsMap = ref<TripsMap | null>(null)
const statistics = ref<Statistics | null>(null)
const year = ref<number | null>(null)
const state = ref<'loading' | 'error' | 'ready'>('loading')
const selectedLine = ref<number | null>(null)

const lines = computed(() => tripsMap.value?.features ?? [])
const selected = computed(() => lines.value.find((line) => line.id === selectedLine.value)?.properties)

async function loadYear(): Promise<void> {
  const [map, stats] = await Promise.all([
    getPublicProfileMap(props.id, year.value),
    getPublicProfileStatistics(props.id, year.value),
  ])
  tripsMap.value = map
  statistics.value = stats
  selectedLine.value = null
}

async function load(): Promise<void> {
  state.value = 'loading'

  try {
    const [found, page] = await Promise.all([getPublicProfile(props.id), listPublicProfileTrips(props.id, 1), loadYear()])
    profile.value = found
    trips.value = page.data
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

watch(year, async () => {
  try {
    await loadYear()
  } catch {
    state.value = 'error'
  }
})
onMounted(load)
</script>

<template>
  <div class="flex flex-col gap-6">
    <RouterLink
      :to="{ name: 'discover' }"
      class="inline-flex w-fit items-center gap-1 rounded-sm text-sm text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
    >
      <ArrowLeft class="size-4" aria-hidden="true" />
      {{ $t('discover.back') }}
    </RouterLink>

    <div v-if="state === 'loading'" class="flex flex-col gap-6" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-16 w-72 rounded-xl" />
      <Skeleton class="h-28 rounded-xl" />
      <Skeleton class="h-[60svh] min-h-80 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('discover.missing') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="load">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <template v-else-if="profile && statistics">
      <div class="flex flex-wrap items-center justify-between gap-4">
        <div class="flex items-center gap-4">
          <Avatar class="size-16 text-lg">
            <AvatarFallback>{{ initials(profile.name) }}</AvatarFallback>
          </Avatar>
          <div class="flex flex-col gap-2">
            <h1 class="text-2xl font-semibold text-balance md:text-3xl">{{ profile.name }}</h1>
            <div class="flex flex-wrap gap-2">
              <Badge variant="outline">
                <Globe aria-hidden="true" />
                {{ $t('discover.public') }}
              </Badge>
              <Badge variant="outline">
                <Eye aria-hidden="true" />
                {{ $t('discover.readOnly') }}
              </Badge>
            </div>
          </div>
        </div>
        <YearFilter v-if="statistics.years.length" v-model="year" :years="statistics.years" :all-label="$t('stats.wholeHistory')" />
      </div>

      <StatisticsTiles :statistics="statistics" />

      <div class="grid items-start gap-6 lg:grid-cols-[1fr_22rem]">
        <TripMap
          :lines="lines"
          :countries="statistics.countries"
          :selected-line="selectedLine"
          :label="$t('discover.mapRegion', { name: profile.name })"
          @select-line="selectedLine = $event"
        />

        <div class="flex flex-col gap-4">
          <Card v-if="selected" aria-live="polite">
            <CardContent class="flex flex-col gap-2">
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
            </CardContent>
          </Card>

          <Card>
            <CardHeader>
              <CardTitle>{{ $t('discover.trips') }}</CardTitle>
            </CardHeader>
            <CardContent>
              <ul v-if="trips.length" class="flex flex-col">
                <li v-for="trip in trips" :key="trip.id">
                  <RouterLink
                    :to="{ name: 'trips.map', params: { id: trip.id } }"
                    class="flex items-center gap-3 rounded-md px-2 py-2 hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                  >
                    <span class="flex min-w-0 flex-1 flex-col">
                      <span class="truncate font-medium" :style="{ viewTransitionName: `trip-${trip.id}` }">{{ trip.name }}</span>
                      <span class="text-sm text-muted-foreground tabular-nums">
                        {{ formatDates(trip.starts_on, trip.ends_on, locale) }} · {{ formatDistance(trip.distance_km ?? 0, locale) }}
                      </span>
                    </span>
                    <ChevronRight class="size-4 text-muted-foreground" aria-hidden="true" />
                  </RouterLink>
                </li>
              </ul>
              <p v-else class="text-sm text-muted-foreground">{{ $t('discover.noTrips') }}</p>
            </CardContent>
          </Card>
        </div>
      </div>
    </template>
  </div>
</template>
