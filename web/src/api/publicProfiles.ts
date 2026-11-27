import { api } from '@/lib/api'
import type { Statistics, TripsMap } from '@/types/map'
import type { PublicProfile } from '@/types/profile'
import type { Page, Trip } from '@/types/trip'

function yearParams(year: number | null): Record<string, number> {
  return year === null ? {} : { year }
}

export async function listPublicProfiles(query: string, page: number): Promise<Page<PublicProfile>> {
  const response = await api.get<Page<PublicProfile>>('/public-profiles', {
    params: query === '' ? { page } : { query, page },
  })

  return response.data
}

export async function getPublicProfile(id: number): Promise<PublicProfile> {
  const response = await api.get<{ data: PublicProfile }>(`/public-profiles/${id}`)

  return response.data.data
}

export async function listPublicProfileTrips(id: number, page: number): Promise<Page<Trip>> {
  const response = await api.get<Page<Trip>>(`/public-profiles/${id}/trips`, { params: { page } })

  return response.data
}

export async function getPublicProfileMap(id: number, year: number | null): Promise<TripsMap> {
  const response = await api.get<{ data: TripsMap }>(`/public-profiles/${id}/map`, { params: yearParams(year) })

  return response.data.data
}

export async function getPublicProfileStatistics(id: number, year: number | null): Promise<Statistics> {
  const response = await api.get<{ data: Statistics }>(`/public-profiles/${id}/statistics`, {
    params: yearParams(year),
  })

  return response.data.data
}
