import type { Transport } from '@/types/trip'

export interface LineFeature<Properties = Record<string, unknown>> {
  type: 'Feature'
  id: number
  geometry: { type: 'LineString'; coordinates: [number, number][] }
  properties: Properties & { transport: Transport }
}

export interface PointFeature {
  type: 'Feature'
  id: number
  geometry: { type: 'Point'; coordinates: [number, number] }
  properties: { name: string }
}

export interface MapSegmentProperties {
  trip_id: number
  trip_name: string
  transport: Transport
  distance_km: number
}

export interface TripsMap {
  type: 'FeatureCollection'
  features: LineFeature<MapSegmentProperties>[]
}

export interface Statistics {
  trips_count: number
  countries_count: number
  countries: string[]
  places_count: number
  distance_km: number
  distance_by_transport: Record<Transport, number>
  years: number[]
  distance_by_year: ({ year: number } & Record<Transport, number>)[]
}
