<script setup lang="ts">
import { computed, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { getStatistics } from '@/api/statistics'
import PageHeader from '@/components/PageHeader.vue'
import StatisticsTiles from '@/components/StatisticsTiles.vue'
import TransportDistances from '@/components/TransportDistances.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Skeleton } from '@/components/ui/skeleton'
import YearDistances from '@/components/YearDistances.vue'
import YearFilter from '@/components/YearFilter.vue'
import { countryName } from '@/lib/format'
import type { Statistics } from '@/types/map'

const { t, locale } = useI18n()

const year = ref<number | null>(null)
const statistics = ref<Statistics | null>(null)
const state = ref<'loading' | 'error' | 'ready'>('loading')

const period = computed(() => (year.value === null ? t('stats.wholeHistory') : t('stats.inYear', { year: year.value })))
const countries = computed(() =>
  (statistics.value?.countries ?? [])
    .map((code) => countryName(code, locale.value))
    .sort((a, b) => a.localeCompare(b, locale.value)),
)

async function load(): Promise<void> {
  try {
    statistics.value = await getStatistics(year.value)
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
      <PageHeader :title="$t('stats.title')" :description="period" />
      <YearFilter
        v-if="statistics?.years.length"
        v-model="year"
        :years="statistics.years"
        :all-label="$t('stats.wholeHistory')"
      />
    </div>

    <div v-if="state === 'loading'" class="flex flex-col gap-6" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-28 rounded-xl" />
      <Skeleton class="h-72 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('stats.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="retry">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <template v-else-if="statistics">
      <StatisticsTiles :statistics="statistics" />

      <div class="grid gap-6 lg:grid-cols-2">
        <Card>
          <CardHeader>
            <CardTitle>{{ $t('stats.byTransport') }}</CardTitle>
            <CardDescription>
              {{ year === null ? $t('stats.kilometresAll') : $t('stats.kilometresYear', { year }) }}
            </CardDescription>
          </CardHeader>
          <CardContent>
            <TransportDistances :distances="statistics.distance_by_transport" />
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>{{ $t('stats.byYear') }}</CardTitle>
            <CardDescription>{{ $t('stats.byYearHint') }}</CardDescription>
          </CardHeader>
          <CardContent>
            <YearDistances v-if="statistics.distance_by_year.length" :years="statistics.distance_by_year" />
            <p v-else class="text-sm text-muted-foreground">{{ $t('stats.empty') }}</p>
          </CardContent>
        </Card>
      </div>

      <Card>
        <CardHeader class="flex flex-row flex-wrap items-center justify-between gap-2">
          <CardTitle>{{ $t('stats.visitedCountries') }}</CardTitle>
          <RouterLink
            :to="{ name: 'map' }"
            class="rounded-sm text-sm font-medium underline underline-offset-4 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
          >
            {{ $t('stats.showOnMap') }}
          </RouterLink>
        </CardHeader>
        <CardContent>
          <ul v-if="countries.length" class="flex flex-wrap gap-2">
            <li v-for="country in countries" :key="country">
              <Badge variant="outline" class="text-sm">{{ country }}</Badge>
            </li>
          </ul>
          <p v-else class="text-sm text-muted-foreground">{{ $t('stats.empty') }}</p>
        </CardContent>
      </Card>
    </template>
  </div>
</template>
