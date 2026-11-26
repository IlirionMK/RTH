<script setup lang="ts">
import { Plus, Route } from '@lucide/vue'
import { onMounted, ref } from 'vue'

import { listTrips } from '@/api/trips'
import PageHeader from '@/components/PageHeader.vue'
import TripCard from '@/components/TripCard.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Empty, EmptyContent, EmptyDescription, EmptyHeader, EmptyMedia, EmptyTitle } from '@/components/ui/empty'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import type { Trip } from '@/types/trip'

const trips = ref<Trip[]>([])
const page = ref(0)
const lastPage = ref(1)
const state = ref<'loading' | 'error' | 'ready'>('loading')
const loadingMore = ref(false)

async function loadPage(next: number): Promise<void> {
  const result = await listTrips(next)
  trips.value = next === 1 ? result.data : [...trips.value, ...result.data]
  page.value = result.meta.current_page
  lastPage.value = result.meta.last_page
}

async function load(): Promise<void> {
  state.value = 'loading'

  try {
    await loadPage(1)
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

async function loadMore(): Promise<void> {
  loadingMore.value = true

  try {
    await loadPage(page.value + 1)
  } catch {
    state.value = 'error'
  } finally {
    loadingMore.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="flex max-w-5xl flex-col gap-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <PageHeader :title="$t('trips.title')" :description="$t('trips.description')" />
      <Button as-child>
        <RouterLink :to="{ name: 'trips.create' }">
          <Plus aria-hidden="true" />
          {{ $t('trips.new') }}
        </RouterLink>
      </Button>
    </div>

    <div v-if="state === 'loading'" class="flex flex-col gap-3" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton v-for="index in 3" :key="index" class="h-28 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('trips.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="load">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <Empty v-else-if="trips.length === 0" class="border">
      <EmptyHeader>
        <EmptyMedia variant="icon">
          <Route aria-hidden="true" />
        </EmptyMedia>
        <EmptyTitle>{{ $t('trips.empty.title') }}</EmptyTitle>
        <EmptyDescription>{{ $t('trips.empty.description') }}</EmptyDescription>
      </EmptyHeader>
      <EmptyContent>
        <Button as-child>
          <RouterLink :to="{ name: 'trips.create' }">{{ $t('trips.empty.action') }}</RouterLink>
        </Button>
      </EmptyContent>
    </Empty>

    <template v-else>
      <ul class="flex flex-col gap-3">
        <li v-for="trip in trips" :key="trip.id">
          <TripCard :trip="trip" />
        </li>
      </ul>
      <Button v-if="page < lastPage" variant="outline" class="self-center" :disabled="loadingMore" @click="loadMore">
        <Spinner v-if="loadingMore" :aria-label="$t('common.loading')" />
        {{ $t('trips.more') }}
      </Button>
    </template>
  </div>
</template>
