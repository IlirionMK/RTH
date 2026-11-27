import { api } from '@/lib/api'
import type { AdminTrip, AdminUser, SystemStatistics } from '@/types/admin'
import type { Page } from '@/types/trip'
import type { Role } from '@/types/user'

export interface AdminUserFilters {
  query: string
  role: Role | ''
  status: 'active' | 'blocked' | ''
  page: number
}

export type AdminUserChanges = Partial<{ role: Role; is_blocked: boolean }>

function present(params: Record<string, string | number>): Record<string, string | number> {
  return Object.fromEntries(Object.entries(params).filter(([, value]) => value !== ''))
}

export async function listAdminUsers(filters: AdminUserFilters): Promise<Page<AdminUser>> {
  const response = await api.get<Page<AdminUser>>('/admin/users', { params: present({ ...filters }) })

  return response.data
}

export async function updateAdminUser(id: number, changes: AdminUserChanges): Promise<AdminUser> {
  const response = await api.patch<{ data: AdminUser }>(`/admin/users/${id}`, changes)

  return response.data.data
}

export async function listAdminTrips(query: string, page: number): Promise<Page<AdminTrip>> {
  const response = await api.get<Page<AdminTrip>>('/admin/trips', { params: present({ query, page }) })

  return response.data
}

export async function getSystemStatistics(): Promise<SystemStatistics> {
  const response = await api.get<{ data: SystemStatistics }>('/admin/statistics')

  return response.data.data
}
