<script setup lang="ts">
import { computed } from 'vue'

import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'

defineProps<{
  years: number[]
  allLabel: string
}>()

const year = defineModel<number | null>({ required: true })

const value = computed({
  get: () => (year.value === null ? 'all' : String(year.value)),
  set: (choice: unknown) => {
    if (typeof choice === 'string' && choice !== '') {
      year.value = choice === 'all' ? null : Number(choice)
    }
  },
})

const itemClass =
  'tabular-nums data-[state=on]:border-primary data-[state=on]:bg-primary data-[state=on]:text-primary-foreground data-[state=on]:hover:bg-primary/90 data-[state=on]:hover:text-primary-foreground'
</script>

<template>
  <ToggleGroup v-model="value" type="single" variant="outline" :aria-label="$t('map.year')" class="flex-wrap">
    <ToggleGroupItem value="all" :class="itemClass">{{ allLabel }}</ToggleGroupItem>
    <ToggleGroupItem v-for="item in years" :key="item" :value="String(item)" :class="itemClass">
      {{ item }}
    </ToggleGroupItem>
  </ToggleGroup>
</template>
