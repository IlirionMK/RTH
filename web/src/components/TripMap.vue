<script setup lang="ts">
import 'mapbox-gl/dist/mapbox-gl.css'

import type { FeatureCollection } from 'geojson'
import mapboxgl from 'mapbox-gl'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'

import { Alert, AlertDescription } from '@/components/ui/alert'
import { useTheme } from '@/composables/useTheme'
import { flightArc, pointColors, transportLines, visitedCountryColor } from '@/lib/map'
import { transports } from '@/lib/transports'
import type { LineFeature, PointFeature } from '@/types/map'

const props = defineProps<{
  lines: LineFeature[]
  points?: PointFeature[]
  countries: string[]
  selectedLine?: number | null
  selectedPoint?: number | null
  label: string
}>()

const emit = defineEmits<{
  selectLine: [id: number]
  selectPoint: [id: number]
}>()

const CLICK_TOLERANCE = 8

const token = import.meta.env.VITE_MAPBOX_TOKEN
const container = ref<HTMLDivElement | null>(null)
const { theme } = useTheme()
const { t, locale } = useI18n()
const lineLayers = transports.map((transport) => `lines-${transport}`)
let map: mapboxgl.Map | null = null

function styleUrl(): string {
  return `mapbox://styles/mapbox/${theme.value === 'dark' ? 'dark' : 'light'}-v11`
}

const drawnLines = computed(() =>
  props.lines.map((line) =>
    line.properties.transport === 'plane'
      ? { ...line, geometry: { ...line.geometry, coordinates: flightArc(line.geometry.coordinates) } }
      : line,
  ),
)

function collection(features: LineFeature[] | PointFeature[]): FeatureCollection {
  return { type: 'FeatureCollection', features }
}

function countryFilter(): mapboxgl.FilterSpecification {
  return [
    'all',
    ['in', ['get', 'iso_3166_1'], ['literal', props.countries]],
    ['any', ['==', ['get', 'worldview'], 'all'], ['in', 'US', ['get', 'worldview']]],
  ]
}

function addLayers(): void {
  if (!map) {
    return
  }

  const colors = pointColors[theme.value === 'dark' ? 'dark' : 'light']
  const firstLabel = map.getStyle()?.layers?.find((layer) => layer.type === 'symbol')?.id

  map.addSource('countries', { type: 'vector', url: 'mapbox://mapbox.country-boundaries-v1' })
  map.addLayer(
    {
      id: 'visited',
      type: 'fill',
      source: 'countries',
      'source-layer': 'country_boundaries',
      filter: countryFilter(),
      paint: { 'fill-color': visitedCountryColor, 'fill-opacity': 0.2 },
    },
    firstLabel,
  )

  map.addSource('lines', { type: 'geojson', data: collection(drawnLines.value) })
  map.addLayer({
    id: 'lines-halo',
    type: 'line',
    source: 'lines',
    filter: only(props.selectedLine),
    layout: { 'line-cap': 'round', 'line-join': 'round' },
    paint: {
      'line-color': ['match', ['get', 'transport'], ...transports.flatMap((transport) => [transport, transportLines[transport].color]), '#000000'],
      'line-width': 16,
      'line-blur': 3,
      'line-opacity': 0.35,
    },
  })
  map.addLayer({
    id: 'lines-casing',
    type: 'line',
    source: 'lines',
    layout: { 'line-cap': 'round', 'line-join': 'round' },
    paint: {
      'line-color': colors.halo,
      'line-width': ['interpolate', ['linear'], ['zoom'], 3, 5, 6, 6.5, 10, 9],
      'line-opacity': 0.85,
    },
  })

  for (const transport of transports) {
    const line = transportLines[transport]
    map.addLayer({
      id: `lines-${transport}`,
      type: 'line',
      source: 'lines',
      filter: ['==', ['get', 'transport'], transport],
      layout: { 'line-cap': 'round', 'line-join': 'round' },
      paint: {
        'line-color': line.color,
        'line-width': ['interpolate', ['linear'], ['zoom'], 3, 2.5, 6, 3.5, 10, 5],
        ...(line.dash ? { 'line-dasharray': line.dash } : {}),
      },
    })
  }

  if (props.points?.length) {
    for (const layer of map.getStyle()?.layers ?? []) {
      if (layer.id.startsWith('settlement')) {
        map.setLayoutProperty(layer.id, 'visibility', 'none')
      }
    }
  }

  map.addSource('points', { type: 'geojson', data: collection(props.points ?? []) })
  map.addLayer({
    id: 'points',
    type: 'circle',
    source: 'points',
    paint: {
      'circle-radius': 5,
      'circle-color': colors.fill,
      'circle-stroke-color': colors.stroke,
      'circle-stroke-width': 2,
    },
  })
  map.addLayer({
    id: 'points-selected',
    type: 'circle',
    source: 'points',
    filter: only(props.selectedPoint),
    paint: {
      'circle-radius': 9,
      'circle-color': colors.fill,
      'circle-stroke-color': colors.stroke,
      'circle-stroke-width': 3,
    },
  })
  map.addLayer({
    id: 'point-labels',
    type: 'symbol',
    source: 'points',
    layout: { 'text-field': ['get', 'name'], 'text-size': 12, 'text-offset': [0, 1.2], 'text-anchor': 'top' },
    paint: { 'text-color': colors.text, 'text-halo-color': colors.halo, 'text-halo-width': 1.5 },
  })

  showSelection()
}

function only(id: number | null | undefined): mapboxgl.FilterSpecification {
  return ['==', ['id'], id ?? -1]
}

function showSelection(): void {
  if (!map?.getLayer('lines-halo')) {
    return
  }

  map.setFilter('lines-halo', only(props.selectedLine))
  map.setFilter('points-selected', only(props.selectedPoint))
}

function fitAll(): void {
  const coordinates = [
    ...drawnLines.value.flatMap((line) => line.geometry.coordinates),
    ...(props.points ?? []).map((point) => point.geometry.coordinates),
  ]
  const [first] = coordinates

  if (!map || !first) {
    return
  }

  const bounds = coordinates.reduce((box, coordinate) => box.extend(coordinate), new mapboxgl.LngLatBounds(first, first))
  map.fitBounds(bounds, { padding: 48, maxZoom: 9, duration: 0 })
}

function select(event: mapboxgl.MapMouseEvent): void {
  const { x, y } = event.point
  const area: [mapboxgl.PointLike, mapboxgl.PointLike] = [
    [x - CLICK_TOLERANCE, y - CLICK_TOLERANCE],
    [x + CLICK_TOLERANCE, y + CLICK_TOLERANCE],
  ]
  const [feature] = map?.queryRenderedFeatures(area, { layers: ['points', ...lineLayers] }) ?? []

  if (typeof feature?.id !== 'number') {
    return
  }

  if (feature.layer?.id === 'points') {
    emit('selectPoint', feature.id)
  } else {
    emit('selectLine', feature.id)
  }
}

onMounted(() => {
  if (!token || !container.value) {
    return
  }

  mapboxgl.accessToken = token
  map = new mapboxgl.Map({
    container: container.value,
    style: styleUrl(),
    language: locale.value,
    customAttribution: [
      '© openrouteservice.org by HeiGIT',
      '<a href="https://transitous.org/sources/" target="_blank" rel="noopener">Transitous</a>',
    ],
    locale: { 'NavigationControl.ZoomIn': t('map.zoomIn'), 'NavigationControl.ZoomOut': t('map.zoomOut') },
  })
  map.getCanvas().setAttribute('aria-label', props.label)
  map.addControl(new mapboxgl.NavigationControl({ showCompass: false }), 'top-right')
  map.on('style.load', addLayers)
  map.once('load', fitAll)
  map.on('click', select)

  for (const layer of ['points', ...lineLayers]) {
    map.on('mouseenter', layer, () => map && (map.getCanvas().style.cursor = 'pointer'))
    map.on('mouseleave', layer, () => map && (map.getCanvas().style.cursor = ''))
  }
})

onBeforeUnmount(() => {
  map?.remove()
  map = null
})

watch(
  () => [props.lines, props.points],
  () => {
    const lines = map?.getSource<mapboxgl.GeoJSONSource>('lines')
    const points = map?.getSource<mapboxgl.GeoJSONSource>('points')
    lines?.setData(collection(drawnLines.value))
    points?.setData(collection(props.points ?? []))
    fitAll()
  },
)
watch(
  () => props.countries,
  () => map?.getLayer('visited') && map.setFilter('visited', countryFilter()),
)
watch(() => [props.selectedLine, props.selectedPoint], showSelection)
watch(theme, () =>
  map?.setStyle(styleUrl(), { diff: false, localFontFamily: undefined, localIdeographFontFamily: undefined }),
)
watch(locale, (language) => map?.setLanguage(language))
</script>

<template>
  <div class="relative h-[60svh] min-h-80 overflow-hidden rounded-xl border bg-muted">
    <div v-if="token" ref="container" role="region" :aria-label="label" class="size-full" />
    <Alert v-else class="m-4 w-auto">
      <AlertDescription>{{ $t('map.noToken') }}</AlertDescription>
    </Alert>
  </div>
</template>
