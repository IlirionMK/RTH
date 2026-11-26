<script setup lang="ts">
import TransportLine from '@/components/TransportLine.vue'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { visitedCountryColor } from '@/lib/map'
import { transports } from '@/lib/transports'

defineProps<{
  countries?: boolean
}>()
</script>

<template>
  <Card>
    <CardHeader>
      <CardTitle>{{ $t('map.legend') }}</CardTitle>
    </CardHeader>
    <CardContent>
      <ul class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
        <li v-for="transport in transports" :key="transport" class="flex items-center gap-2">
          <TransportLine :transport="transport" />
          {{ $t(`transport.${transport}`) }}
        </li>
        <li v-if="countries" class="col-span-2 flex items-center gap-2">
          <span
            class="h-3 w-7 shrink-0 rounded-sm border opacity-60"
            :style="{ backgroundColor: visitedCountryColor, borderColor: visitedCountryColor }"
            aria-hidden="true"
          />
          {{ $t('map.visited') }}
        </li>
      </ul>
    </CardContent>
  </Card>
</template>
