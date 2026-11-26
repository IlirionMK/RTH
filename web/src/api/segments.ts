import { api } from '@/lib/api'
import type { Segment, Transport } from '@/types/trip'

export async function updateSegment(id: number, transport: Transport): Promise<Segment> {
  const response = await api.patch<{ data: Segment }>(`/segments/${id}`, { transport })

  return response.data.data
}
