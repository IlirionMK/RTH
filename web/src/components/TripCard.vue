<script setup lang="ts">
import { CalendarDays } from '@lucide/vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { Badge } from '@/components/ui/badge'
import { countryName, formatDates, formatDistance } from '@/lib/format'
import { transportIcons } from '@/lib/transports'
import type { Trip } from '@/types/trip'

const props = defineProps<{
  trip: Trip
}>()

const { locale } = useI18n()

const dates = computed(() => formatDates(props.trip.starts_on, props.trip.ends_on, locale.value))
const route = computed(() => (props.trip.points ?? []).map((point) => point.name).join(' → '))
const countries = computed(() => (props.trip.countries ?? []).map((code) => countryName(code, locale.value)))
const distance = computed(() => formatDistance(props.trip.distance_km ?? 0, locale.value))
</script>

<template>
  <article class="flex flex-col gap-3 rounded-xl border bg-card p-4 text-card-foreground md:flex-row md:items-center md:gap-6">
    <div class="flex min-w-0 flex-1 flex-col gap-1">
      <h2 class="text-lg font-semibold text-balance">
        <RouterLink
          :to="{ name: 'trips.edit', params: { id: trip.id } }"
          class="rounded-sm hover:underline focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          :style="{ viewTransitionName: `trip-${trip.id}` }"
        >
          {{ trip.name }}
        </RouterLink>
      </h2>
      <p class="flex items-center gap-1.5 text-sm text-muted-foreground tabular-nums">
        <CalendarDays class="size-4" aria-hidden="true" />
        {{ dates }}
      </p>
      <p v-if="route" class="truncate text-sm text-muted-foreground">{{ route }}</p>
    </div>

    <ul v-if="countries.length" :aria-label="$t('trips.countries')" class="flex flex-wrap gap-1.5 md:max-w-56">
      <li v-for="country in countries" :key="country">
        <Badge variant="outline">{{ country }}</Badge>
      </li>
    </ul>

    <ul v-if="trip.transports?.length" :aria-label="$t('trips.transports')" class="flex gap-1.5">
      <li
        v-for="transport in trip.transports"
        :key="transport"
        class="flex size-8 items-center justify-center rounded-full bg-muted text-muted-foreground"
      >
        <component :is="transportIcons[transport]" class="size-4" aria-hidden="true" />
        <span class="sr-only">{{ $t(`transport.${transport}`) }}</span>
      </li>
    </ul>

    <p class="text-lg font-semibold tabular-nums md:w-28 md:text-right">{{ distance }}</p>
  </article>
</template>
