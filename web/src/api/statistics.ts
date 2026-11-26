import { api } from '@/lib/api'
import type { Statistics } from '@/types/map'

export async function getStatistics(year: number | null): Promise<Statistics> {
  const response = await api.get<{ data: Statistics }>('/statistics', { params: year === null ? {} : { year } })

  return response.data.data
}
