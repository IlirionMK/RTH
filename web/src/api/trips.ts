import { api } from '@/lib/api'
import type { Page, Trip } from '@/types/trip'

export interface TripPayload {
  name: string
  starts_on: string
  ends_on: string | null
  description: string | null
}

export async function listTrips(page: number): Promise<Page<Trip>> {
  const response = await api.get<Page<Trip>>('/trips', { params: { page } })

  return response.data
}

export async function getTrip(id: number): Promise<Trip> {
  const response = await api.get<{ data: Trip }>(`/trips/${id}`)

  return response.data.data
}

export async function createTrip(payload: TripPayload): Promise<Trip> {
  const response = await api.post<{ data: Trip }>('/trips', payload)

  return response.data.data
}

export async function updateTrip(id: number, payload: TripPayload): Promise<Trip> {
  const response = await api.patch<{ data: Trip }>(`/trips/${id}`, payload)

  return response.data.data
}

export async function deleteTrip(id: number): Promise<void> {
  await api.delete(`/trips/${id}`)
}
