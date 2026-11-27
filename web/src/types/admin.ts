import type { Role } from '@/types/user'

export interface AdminUser {
  id: number
  name: string
  email: string
  role: Role
  is_public: boolean
  is_blocked: boolean
  blocked_at: string | null
  email_verified_at: string | null
  created_at: string
  trips_count: number
}

export interface AdminTrip {
  id: number
  name: string
  starts_on: string
  ends_on: string | null
  created_at: string
  distance_km?: number
  countries?: string[]
  user: { id: number; name: string; email: string }
}

export interface SystemStatistics {
  users_count: number
  trips_count: number
  months: { month: string; users: number; trips: number }[]
}
