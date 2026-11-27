export type Transport = 'car' | 'plane' | 'train' | 'other'

export interface Point {
  id: number
  trip_id: number
  name: string
  latitude: number
  longitude: number
  country_code: string | null
  position: number
}

export interface Segment {
  id: number
  from_point_id: number
  to_point_id: number
  transport: Transport
  distance_km: number
  is_approximate: boolean
  geometry: { type: 'LineString'; coordinates: [number, number][] }
}

export interface Trip {
  id: number
  user_id: number
  name: string
  starts_on: string
  ends_on: string | null
  description: string | null
  distance_km?: number
  countries?: string[]
  transports?: Transport[]
  points?: Point[]
  segments?: Segment[]
}

export interface Place {
  name: string
  label: string
  country_code: string | null
  latitude: number
  longitude: number
}

export interface Page<T> {
  data: T[]
  meta: { current_page: number; last_page: number; total: number }
}
