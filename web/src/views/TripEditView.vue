<script setup lang="ts">
import { ArrowLeft, Trash2 } from '@lucide/vue'
import { computed, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useRouter } from 'vue-router'
import { toast } from 'vue-sonner'

import { addPoint, deletePoint, movePoint } from '@/api/points'
import { updateSegment } from '@/api/segments'
import { createTrip, deleteTrip, getTrip, updateTrip } from '@/api/trips'
import PlaceSearch from '@/components/PlaceSearch.vue'
import TextField from '@/components/TextField.vue'
import TripPoints from '@/components/TripPoints.vue'
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import {
  Dialog,
  DialogClose,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog'
import { Field, FieldError, FieldLabel } from '@/components/ui/field'
import { Skeleton } from '@/components/ui/skeleton'
import { Spinner } from '@/components/ui/spinner'
import { Textarea } from '@/components/ui/textarea'
import { type FieldErrors, validationErrors } from '@/lib/api'
import { celebrate } from '@/lib/celebrate'
import type { Place, Point, Segment, Transport, Trip } from '@/types/trip'

const props = defineProps<{
  id?: number
}>()

const router = useRouter()
const { t } = useI18n()

const trip = ref<Trip | null>(null)
const state = ref<'loading' | 'error' | 'ready'>('ready')
const form = reactive({ name: '', starts_on: '', ends_on: '', description: '' })
const errors = ref<FieldErrors>({})
const saving = ref(false)
const changingPoints = ref(false)
const deleteOpen = ref(false)
const deleting = ref(false)

const title = computed(() => trip.value?.name ?? t('trip.newTitle'))

function fill(value: Trip): void {
  trip.value = value
  Object.assign(form, {
    name: value.name,
    starts_on: value.starts_on,
    ends_on: value.ends_on ?? '',
    description: value.description ?? '',
  })
}

async function load(id: number): Promise<void> {
  state.value = 'loading'

  try {
    fill(await getTrip(id))
    state.value = 'ready'
  } catch {
    state.value = 'error'
  }
}

watch(
  () => props.id,
  (id) => {
    if (id !== undefined && trip.value?.id !== id) {
      load(id)
    }
  },
  { immediate: true },
)

async function save(event: Event): Promise<void> {
  const origin = (event as SubmitEvent).submitter ?? (event.currentTarget as Element)
  const payload = {
    name: form.name,
    starts_on: form.starts_on,
    ends_on: form.ends_on || null,
    description: form.description || null,
  }
  saving.value = true
  errors.value = {}

  try {
    if (trip.value) {
      trip.value = { ...trip.value, ...(await updateTrip(trip.value.id, payload)) }
      toast.success(t('trip.saved'))
      celebrate(origin, 'don')
    } else {
      const created = await createTrip(payload)
      fill(created)
      toast.success(t('trip.created'))
      await router.replace({ name: 'trips.edit', params: { id: created.id } })
    }
  } catch (error) {
    const fieldErrors = validationErrors(error)

    if (fieldErrors) {
      errors.value = fieldErrors
    } else {
      toast.error(t('common.error'))
    }
  } finally {
    saving.value = false
  }
}

async function changePoints(change: (tripId: number) => Promise<unknown>, success?: string): Promise<void> {
  if (!trip.value || changingPoints.value) {
    return
  }

  changingPoints.value = true

  try {
    await change(trip.value.id)
    trip.value = await getTrip(trip.value.id)

    if (success) {
      toast.success(success)
    }
  } catch {
    toast.error(t('common.error'))
  } finally {
    changingPoints.value = false
  }
}

function add(place: Place): void {
  changePoints((tripId) => addPoint(tripId, place), t('trip.points.added', { name: place.name }))
}

function move(point: Point, position: number): void {
  changePoints(() => movePoint(point.id, position))
}

function remove(point: Point): void {
  changePoints(() => deletePoint(point.id))
}

function changeTransport(segment: Segment, transport: Transport): void {
  changePoints(() => updateSegment(segment.id, transport))
}

async function destroy(): Promise<void> {
  if (!trip.value) {
    return
  }

  deleting.value = true

  try {
    await deleteTrip(trip.value.id)
    deleteOpen.value = false
    toast.success(t('trip.delete.done'))
    await router.push({ name: 'trips.index' })
  } catch {
    toast.error(t('common.error'))
  } finally {
    deleting.value = false
  }
}
</script>

<template>
  <div class="flex max-w-3xl flex-col gap-6">
    <div class="flex flex-col gap-3">
      <RouterLink
        :to="{ name: 'trips.index' }"
        class="inline-flex w-fit items-center gap-1 rounded-sm text-sm text-muted-foreground hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
      >
        <ArrowLeft class="size-4" aria-hidden="true" />
        {{ $t('trip.back') }}
      </RouterLink>
      <div class="flex flex-wrap items-start justify-between gap-4">
        <header class="flex min-w-0 flex-col gap-1">
          <h1 class="text-2xl font-semibold text-balance md:text-3xl">
            <span :style="{ viewTransitionName: trip ? `trip-${trip.id}` : undefined }">{{ title }}</span>
          </h1>
          <p class="text-pretty text-muted-foreground">
            {{ $t(trip ? 'trip.editDescription' : 'trip.newDescription') }}
          </p>
        </header>
        <Button v-if="trip" variant="destructive" @click="deleteOpen = true">
          <Trash2 aria-hidden="true" />
          {{ $t('trip.delete.open') }}
        </Button>
      </div>
    </div>

    <div v-if="state === 'loading'" class="flex flex-col gap-6" :aria-label="$t('common.loading')" aria-busy="true">
      <Skeleton class="h-72 rounded-xl" />
      <Skeleton class="h-48 rounded-xl" />
    </div>

    <Alert v-else-if="state === 'error'" variant="destructive">
      <AlertTitle>{{ $t('trips.loadError') }}</AlertTitle>
      <AlertDescription>
        <Button variant="outline" size="sm" class="mt-2" @click="id !== undefined && load(id)">
          {{ $t('trips.retry') }}
        </Button>
      </AlertDescription>
    </Alert>

    <template v-else>
      <Card>
        <CardHeader>
          <CardTitle>{{ $t('trip.info') }}</CardTitle>
        </CardHeader>
        <CardContent>
          <form class="flex flex-col gap-4" novalidate @submit.prevent="save">
            <TextField id="trip-name" v-model="form.name" :label="$t('trip.name')" :error="errors.name" />
            <div class="grid gap-4 md:grid-cols-2">
              <TextField
                id="trip-starts-on"
                v-model="form.starts_on"
                type="date"
                :label="$t('trip.startsOn')"
                :error="errors.starts_on"
              />
              <TextField
                id="trip-ends-on"
                v-model="form.ends_on"
                type="date"
                :label="$t('trip.endsOn')"
                :error="errors.ends_on"
              />
            </div>
            <Field :data-invalid="errors.description ? true : undefined">
              <FieldLabel for="trip-description">{{ $t('trip.description') }}</FieldLabel>
              <Textarea
                id="trip-description"
                v-model="form.description"
                rows="3"
                :aria-invalid="errors.description ? true : undefined"
                :aria-describedby="errors.description ? 'trip-description-error' : undefined"
              />
              <FieldError v-if="errors.description" id="trip-description-error">{{ errors.description }}</FieldError>
            </Field>
            <Button type="submit" class="self-start" :disabled="saving">
              <Spinner v-if="saving" :aria-label="$t('common.loading')" />
              {{ $t(trip ? 'trip.save' : 'trip.create') }}
            </Button>
          </form>
        </CardContent>
      </Card>

      <Card v-if="trip">
        <CardHeader>
          <CardTitle>{{ $t('trip.points.title') }}</CardTitle>
          <CardDescription>{{ $t('trip.points.description') }}</CardDescription>
        </CardHeader>
        <CardContent class="flex flex-col gap-6" :aria-busy="changingPoints">
          <PlaceSearch @select="add" />
          <TripPoints
            v-if="trip.points?.length"
            :points="trip.points"
            :segments="trip.segments ?? []"
            @move="move"
            @remove="remove"
            @transport="changeTransport"
          />
          <p v-else class="text-sm text-muted-foreground">{{ $t('trip.points.empty') }}</p>
        </CardContent>
      </Card>
    </template>

    <Dialog v-model:open="deleteOpen">
      <DialogContent :show-close-button="false">
        <DialogHeader>
          <DialogTitle>{{ $t('trip.delete.title') }}</DialogTitle>
          <DialogDescription>{{ $t('trip.delete.description', { name: trip?.name }) }}</DialogDescription>
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
