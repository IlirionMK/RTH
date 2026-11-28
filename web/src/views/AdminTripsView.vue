<script setup lang="ts">
import { Search, Trash2 } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { listAdminTrips } from '@/api/admin'
import { deleteTrip } from '@/api/trips'
import PageHeader from '@/components/PageHeader.vue'
import PagePager from '@/components/PagePager.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent } from '@/components/ui/card'
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
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { countryName, formatDates, formatDistance } from '@/lib/format'
import type { AdminTrip } from '@/types/admin'

const { t, locale } = useI18n()

const query = ref('')
const trips = ref<AdminTrip[]>([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const state = ref<'loading' | 'error' | 'ready'>('loading')
const selected = ref<AdminTrip | null>(null)
const deleteOpen = ref(false)
const deleting = ref(false)
let latest = 0

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

function confirmDelete(trip: AdminTrip): void {
  selected.value = trip
  deleteOpen.value = true
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

    <Card v-else class="py-0">
      <CardContent class="flex flex-col gap-4 p-0 pb-4">
        <div
          class="hidden border-b pt-3 pr-14 pb-2 pl-4 text-sm font-medium text-muted-foreground md:grid md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_12rem_6rem] md:gap-4"
          aria-hidden="true"
        >
          <span>{{ $t('admin.trip') }}</span>
          <span>{{ $t('admin.author') }}</span>
          <span>{{ $t('admin.dates') }}</span>
          <span class="text-right">{{ $t('map.distance') }}</span>
        </div>
        <ul class="flex flex-col divide-y">
          <li v-for="trip in trips" :key="trip.id" class="flex items-center gap-2 pr-4 hover:bg-muted">
            <RouterLink
              :to="{ name: 'trips.map', params: { id: trip.id } }"
              class="grid min-w-0 flex-1 gap-1 py-3 pl-4 focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none md:grid-cols-[minmax(0,2fr)_minmax(0,1fr)_12rem_6rem] md:items-center md:gap-4"
            >
              <span class="flex min-w-0 flex-col">
                <span class="truncate font-medium">{{ trip.name }}</span>
                <span class="truncate text-sm text-muted-foreground">{{ countries(trip) || '—' }}</span>
              </span>
              <span class="flex min-w-0 flex-col text-sm">
                <span class="truncate">{{ trip.user.name }}</span>
                <span class="truncate text-muted-foreground">{{ trip.user.email }}</span>
              </span>
              <span class="text-sm text-muted-foreground tabular-nums">{{ formatDates(trip.starts_on, trip.ends_on, locale) }}</span>
              <span class="text-sm font-medium tabular-nums md:text-right">{{ formatDistance(trip.distance_km ?? 0, locale) }}</span>
            </RouterLink>
            <Tooltip>
              <TooltipTrigger as-child>
                <Button variant="ghost" size="icon" :aria-label="$t('admin.deleteTrip', { name: trip.name })" @click="confirmDelete(trip)">
                  <Trash2 aria-hidden="true" />
                </Button>
              </TooltipTrigger>
              <TooltipContent>{{ $t('admin.deleteTrip', { name: trip.name }) }}</TooltipContent>
            </Tooltip>
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
