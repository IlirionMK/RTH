import { api } from '@/lib/api'
import type { Place, Point } from '@/types/trip'

export async function addPoint(tripId: number, place: Place): Promise<Point> {
  const response = await api.post<{ data: Point }>(`/trips/${tripId}/points`, {
    name: place.name,
    latitude: place.latitude,
    longitude: place.longitude,
  })

  return response.data.data
}

export async function movePoint(id: number, position: number): Promise<Point> {
  const response = await api.patch<{ data: Point }>(`/points/${id}`, { position })

  return response.data.data
}

export async function deletePoint(id: number): Promise<void> {
  await api.delete(`/points/${id}`)
}
