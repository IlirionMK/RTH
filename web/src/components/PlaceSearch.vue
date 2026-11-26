<script setup lang="ts">
import { MapPin } from '@lucide/vue'
import { watchDebounced } from '@vueuse/core'
import { computed, ref } from 'vue'
import { useI18n } from 'vue-i18n'

import { searchPlaces } from '@/api/places'
import {
  Combobox,
  ComboboxAnchor,
  ComboboxEmpty,
  ComboboxInput,
  ComboboxItem,
  ComboboxList,
} from '@/components/ui/combobox'
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field'
import type { Place } from '@/types/trip'

const emit = defineEmits<{
  select: [place: Place]
}>()

const { t } = useI18n()

const term = ref('')
const places = ref<Place[]>([])
const searching = ref(false)
const failed = ref(false)
let latest = 0

watchDebounced(
  term,
  async (value) => {
    const query = value.trim()
    const request = ++latest
    failed.value = false

    if (query.length < 2) {
      places.value = []

      return
    }

    searching.value = true

    try {
      const found = await searchPlaces(query)

      if (request === latest) {
        places.value = found
      }
    } catch {
      if (request === latest) {
        places.value = []
        failed.value = true
      }
    } finally {
      if (request === latest) {
        searching.value = false
      }
    }
  },
  { debounce: 300 },
)

const emptyText = computed(() => {
  if (term.value.trim().length < 2) {
    return t('trip.search.hint')
  }

  return t(searching.value ? 'trip.search.searching' : 'trip.search.empty')
})

function choose(value: unknown): void {
  if (value === null || typeof value !== 'object') {
    return
  }

  emit('select', value as Place)
  term.value = ''
  places.value = []
}
</script>

<template>
  <Field :data-invalid="failed ? true : undefined">
    <FieldLabel for="place-search">{{ $t('trip.search.label') }}</FieldLabel>
    <Combobox :model-value="null" by="label" ignore-filter @update:model-value="choose">
      <ComboboxAnchor class="w-full">
        <ComboboxInput
          id="place-search"
          v-model="term"
          class="w-full"
          :placeholder="$t('trip.search.placeholder')"
          :aria-invalid="failed ? true : undefined"
          :aria-describedby="failed ? 'place-search-error' : 'place-search-hint'"
        />
      </ComboboxAnchor>
      <ComboboxList>
        <ComboboxEmpty>{{ emptyText }}</ComboboxEmpty>
        <ComboboxItem
          v-for="place in places"
          :key="`${place.latitude},${place.longitude}`"
          :value="place"
        >
          <MapPin aria-hidden="true" />
          <span class="truncate">{{ place.label }}</span>
        </ComboboxItem>
      </ComboboxList>
    </Combobox>
    <FieldError v-if="failed" id="place-search-error">{{ $t('trip.search.failed') }}</FieldError>
    <FieldDescription v-else id="place-search-hint">{{ $t('trip.search.hint') }}</FieldDescription>
  </Field>
</template>
