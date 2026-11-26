import { useLocalStorage } from '@vueuse/core'
import axios, { isAxiosError } from 'axios'

import { i18n } from '@/i18n'

export const token = useLocalStorage<string | null>('rth-token', null)

export const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  config.headers['Accept-Language'] = i18n.global.locale.value

  if (token.value) {
    config.headers.Authorization = `Bearer ${token.value}`
  }

  return config
})

api.interceptors.response.use(undefined, (error: unknown) => {
  if (isAxiosError(error) && error.response?.status === 401) {
    token.value = null
  }

  return Promise.reject(error)
})

export type FieldErrors = Record<string, string>

export function validationErrors(error: unknown): FieldErrors | null {
  if (!isAxiosError<{ errors?: Record<string, string[]> }>(error) || error.response?.status !== 422) {
    return null
  }

  const errors = error.response.data.errors ?? {}

  return Object.fromEntries(
    Object.entries(errors).map(([field, messages]) => [field, messages[0] ?? '']),
  )
}
