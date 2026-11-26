import { api } from '@/lib/api'
import type { Place } from '@/types/trip'

export async function searchPlaces(query: string): Promise<Place[]> {
  const response = await api.get<{ data: Place[] }>('/places', { params: { query } })

  return response.data.data
}
