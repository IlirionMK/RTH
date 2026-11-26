<script setup lang="ts">
import { ChevronDown, ChevronUp, Trash2 } from '@lucide/vue'
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

import { Button } from '@/components/ui/button'
import { ToggleGroup, ToggleGroupItem } from '@/components/ui/toggle-group'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { countryName, formatDistance } from '@/lib/format'
import { transportIcons, transports } from '@/lib/transports'
import type { Point, Segment, Transport } from '@/types/trip'

const props = defineProps<{
  points: Point[]
  segments: Segment[]
}>()

const emit = defineEmits<{
  move: [point: Point, position: number]
  remove: [point: Point]
  transport: [segment: Segment, transport: Transport]
}>()

const { locale } = useI18n()

const rows = computed(() =>
  props.points.map((point, index) => ({
    point,
    next: props.points[index + 1],
    segment: props.segments.find((segment) => segment.from_point_id === point.id),
  })),
)

function changeTransport(segment: Segment, value: unknown): void {
  const transport = transports.find((item) => item === value)

  if (transport && transport !== segment.transport) {
    emit('transport', segment, transport)
  }
}
</script>

<template>
  <ol class="flex flex-col">
    <li v-for="({ point, next, segment }, index) in rows" :key="point.id" class="flex flex-col">
      <div class="flex items-center gap-3">
        <span
          class="flex size-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-medium text-primary-foreground tabular-nums"
          aria-hidden="true"
        >
          {{ point.position }}
        </span>
        <div class="flex min-w-0 flex-1 flex-col">
          <span class="truncate font-medium">{{ point.name }}</span>
          <span v-if="point.country_code" class="text-xs text-muted-foreground">
            {{ countryName(point.country_code, locale) }}
          </span>
        </div>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              :aria-label="$t('trip.points.moveUp', { name: point.name })"
              :disabled="index === 0"
              @click="emit('move', point, point.position - 1)"
            >
              <ChevronUp aria-hidden="true" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('trip.points.moveUp', { name: point.name }) }}</TooltipContent>
        </Tooltip>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              :aria-label="$t('trip.points.moveDown', { name: point.name })"
              :disabled="!next"
              @click="emit('move', point, point.position + 1)"
            >
              <ChevronDown aria-hidden="true" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('trip.points.moveDown', { name: point.name }) }}</TooltipContent>
        </Tooltip>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button
              variant="ghost"
              size="icon"
              :aria-label="$t('trip.points.remove', { name: point.name })"
              @click="emit('remove', point)"
            >
              <Trash2 aria-hidden="true" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('trip.points.remove', { name: point.name }) }}</TooltipContent>
        </Tooltip>
      </div>

      <div v-if="segment && next" class="ml-3.5 flex flex-wrap items-center gap-3 border-l border-dashed py-3 pl-6">
        <ToggleGroup
          type="single"
          variant="outline"
          size="sm"
          :model-value="segment.transport"
          :aria-label="$t('trip.points.transport', { from: point.name, to: next.name })"
          @update:model-value="changeTransport(segment, $event)"
        >
          <ToggleGroupItem v-for="transport in transports" :key="transport" :value="transport">
            <component :is="transportIcons[transport]" aria-hidden="true" />
            {{ $t(`transport.${transport}`) }}
          </ToggleGroupItem>
        </ToggleGroup>
        <span class="text-sm text-muted-foreground tabular-nums">
          {{ formatDistance(segment.distance_km, locale) }}
          <template v-if="segment.is_approximate">({{ $t('trip.points.approximate') }})</template>
        </span>
      </div>
    </li>
  </ol>
</template>
