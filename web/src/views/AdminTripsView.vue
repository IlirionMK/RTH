<script setup lang="ts">
import { MapIcon, Search, Trash2 } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { listAdminTrips } from '@/api/admin'
import { deleteTrip } from '@/api/trips'
import PageHeader from '@/components/PageHeader.vue'
import PagePager from '@/components/PagePager.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { InputGroup, InputGroupAddon, InputGroupInput } from '@/components/ui/input-group'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { countryName, formatDates, formatDistance } from '@/lib/format'
import type { AdminTrip } from '@/types/admin'

const { t, locale } = useI18n()

const query = ref('')
const trips = ref<AdminTrip[]>([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const state = ref<'loading' | 'error' | 'ready'>('loading')
const selectedId = ref<number | null>(null)
const deleteOpen = ref(false)
const deleting = ref(false)
let latest = 0

const selected = computed(() => trips.value.find((trip) => trip.id === selectedId.value))

function countries(trip: AdminTrip): string {
  return (trip.countries ?? []).map((code) => countryName(code, locale.value)).join(', ')
}

async function load(page: number): Promise<void> {
  const request = ++latest

  try {
    const result = await listAdminTrips(query.value.trim(), page)

    if (request === latest) {
      trips.value = result.data
      meta.value = result.meta
      state.value = 'ready'
    }
  } catch {
    state.value = 'error'
  }
}

function retry(): void {
  state.value = 'loading'
  load(1)
}

async function destroy(): Promise<void> {
  const trip = selected.value

  if (!trip) {
    return
  }

  deleting.value = true

  try {
    await deleteTrip(trip.id)
    toast.success(t('admin.tripDeleted', { name: trip.name }))
    deleteOpen.value = false
    selectedId.value = null
    await load(meta.value.current_page)
  } catch {
    toast.error(t('common.error'))
  } finally {
    deleting.value = false
  }
}

watchDebounced(query, () => load(1), { debounce: 300 })
onMounted(() => load(1))
</script>

<template>
  <div class="flex flex-col gap-6">
    <PageHeader :title="$t('admin.tripsTitle')" :description="$t('admin.tripsCount', { count: meta.total })" />

    <InputGroup class="md:max-w-md">
      <InputGroupAddon>
        <Search aria-hidden="true" />
      </InputGroupAddon>
      <InputGroupInput v-model="query" type="search" :placeholder="$t('admin.searchTrips')" :aria-label="$t('admin.searchTrips')" />
    </InputGroup>

    <div v-if="state === 'loading'" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-96 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('admin.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="retry">{{ $t('trips.retry') }}</Button>
      </AlertDescription>
    </Alert>

    <p v-else-if="trips.length === 0" class="text-muted-foreground">{{ $t('admin.noTrips') }}</p>

    <div v-else class="grid items-start gap-6 lg:grid-cols-[1fr_20rem]">
      <Card class="py-0">
        <CardContent class="flex flex-col gap-4 p-0 pb-4">
          <div
            class="hidden border-b px-4 pt-3 pb-2 text-sm font-medium text-muted-foreground md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_12rem_6rem] md:gap-4"
            aria-hidden="true"
          >
            <span>{{ $t('admin.trip') }}</span>
            <span>{{ $t('admin.author') }}</span>
            <span>{{ $t('admin.dates') }}</span>
            <span class="text-right">{{ $t('map.distance') }}</span>
          </div>
          <ul class="flex flex-col divide-y">
            <li v-for="trip in trips" :key="trip.id">
              <button
                type="button"
                class="grid w-full gap-1 px-4 py-3 text-left hover:bg-muted focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none aria-pressed:bg-muted md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_12rem_6rem] md:items-center md:gap-4"
                :aria-pressed="selectedId === trip.id"
                @click="selectedId = trip.id"
              >
                <span class="flex min-w-0 flex-col">
                  <span class="truncate font-medium">{{ trip.name }}</span>
                  <span class="truncate text-sm text-muted-foreground">{{ countries(trip) || '—' }}</span>
                </span>
                <span class="truncate text-sm">{{ trip.user.name }}</span>
                <span class="text-sm text-muted-foreground tabular-nums">{{ formatDates(trip.starts_on, trip.ends_on, locale) }}</span>
                <span class="text-sm font-medium tabular-nums md:text-right">{{ formatDistance(trip.distance_km ?? 0, locale) }}</span>
              </button>
            </li>
          </ul>
          <PagePager
            class="px-4"
            :page="meta.current_page"
            :last-page="meta.last_page"
            :total="meta.total"
            :per-page="15"
            @change="load"
          />
        </CardContent>
      </Card>

      <Card aria-live="polite" class="lg:sticky lg:top-4">
        <template v-if="selected">
          <CardHeader>
            <CardTitle>{{ selected.name }}</CardTitle>
          </CardHeader>
          <CardContent class="flex flex-col gap-4">
            <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-sm">
              <dt class="text-muted-foreground">{{ $t('admin.author') }}</dt>
              <dd>{{ selected.user.name }} ({{ selected.user.email }})</dd>
              <dt class="text-muted-foreground">{{ $t('admin.dates') }}</dt>
              <dd class="tabular-nums">{{ formatDates(selected.starts_on, selected.ends_on, locale) }}</dd>
              <dt class="text-muted-foreground">{{ $t('map.distance') }}</dt>
              <dd class="tabular-nums">{{ formatDistance(selected.distance_km ?? 0, locale) }}</dd>
              <dt class="text-muted-foreground">{{ $t('trips.countries') }}</dt>
              <dd>{{ countries(selected) || '—' }}</dd>
            </dl>
            <div class="flex flex-wrap gap-2">
              <Button variant="outline" size="sm" as-child>
                <RouterLink :to="{ name: 'trips.map', params: { id: selected.id } }">
                  <MapIcon aria-hidden="true" />
                  {{ $t('admin.openMap') }}
                </RouterLink>
              </Button>
              <Button variant="destructive" size="sm" @click="deleteOpen = true">
                <Trash2 aria-hidden="true" />
                {{ $t('trip.delete.open') }}
              </Button>
            </div>
          </CardContent>
        </template>
        <CardContent v-else>
          <p class="text-sm text-muted-foreground">{{ $t('admin.pickTrip') }}</p>
        </CardContent>
      </Card>
    </div>

    <Dialog v-model:open="deleteOpen">
      <DialogContent :show-close-button="false">
        <DialogHeader>
          <DialogTitle>{{ $t('trip.delete.title') }}</DialogTitle>
          <DialogDescription>
            {{ $t('admin.deleteTripDescription', { name: selected?.name, author: selected?.user.name }) }}
          </DialogDescription>
        </DialogHeader>
        <DialogFooter>
          <DialogClose as-child>
            <Button variant="outline">{{ $t('trip.delete.cancel') }}</Button>
          </DialogClose>
          <Button variant="destructive" :disabled="deleting" @click="destroy">
            <Spinner v-if="deleting" :aria-label="$t('common.loading')" />
            {{ $t('trip.delete.confirm') }}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>
