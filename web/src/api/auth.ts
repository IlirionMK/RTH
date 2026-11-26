import { api } from '@/lib/api'
import type { User } from '@/types/user'

export interface RegisterPayload {
  name: string
  email: string
  password: string
  password_confirmation: string
}

export interface LoginPayload {
  email: string
  password: string
}

export interface NewToken {
  token: string
  user: User
}

export async function register(payload: RegisterPayload): Promise<void> {
  await api.post('/users', payload)
}

export async function createToken(payload: LoginPayload): Promise<NewToken> {
  const response = await api.post<{ data: NewToken }>('/tokens', { ...payload, device_name: 'web' })

  return response.data.data
}

export async function deleteToken(): Promise<void> {
  await api.delete('/tokens/current')
}
