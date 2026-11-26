import { api } from '@/lib/api'
import type { TripsMap } from '@/types/map'

export async function getMap(year: number | null): Promise<TripsMap> {
  const response = await api.get<{ data: TripsMap }>('/map', { params: year === null ? {} : { year } })

  return response.data.data
}
