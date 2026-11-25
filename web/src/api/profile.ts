import { api } from '@/lib/api'
import type { User } from '@/types/user'

export type ProfileChanges = Partial<Pick<User, 'name' | 'email' | 'is_public' | 'locale' | 'theme'>>

export interface PasswordPayload {
  current_password: string
  password: string
  password_confirmation: string
}

export async function getProfile(): Promise<User> {
  const response = await api.get<{ data: User }>('/profile')

  return response.data.data
}

export async function updateProfile(changes: ProfileChanges): Promise<User> {
  const response = await api.patch<{ data: User }>('/profile', changes)

  return response.data.data
}

export async function updatePassword(payload: PasswordPayload): Promise<void> {
  await api.put('/profile/password', payload)
}
