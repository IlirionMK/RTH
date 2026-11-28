import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { createToken, deleteToken, register } from '@/api/auth'
import { getProfile, updateProfile } from '@/api/profile'
import { guestTheme } from '@/composables/useTheme'
import { guestLocale } from '@/i18n'
import { token } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/user'

vi.mock('@/api/auth')
vi.mock('@/api/profile')

const user: User = {
  id: 1,
  name: 'Test User',
  email: 'test@example.com',
  pending_email: null,
  role: 'user',
  is_public: false,
  locale: 'pl',
  theme: 'light',
}

describe('auth store', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
    vi.mocked(createToken).mockResolvedValue({ token: 'test-token', user })
  })

  afterEach(() => {
    token.value = null
    guestTheme.value = null
    guestLocale.value = null
    vi.resetAllMocks()
  })

  it('keeps the token and the user after login', async () => {
    const auth = useAuthStore()

    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    expect(token.value).toBe('test-token')
    expect(auth.user).toEqual(user)
    expect(auth.isAuthenticated).toBe(true)
    expect(auth.isAdmin).toBe(false)
  })

  it('stays logged out after registration until the e-mail is confirmed', async () => {
    vi.mocked(register).mockResolvedValue()
    const auth = useAuthStore()

    await auth.register({
      name: 'Test User',
      email: 'test@example.com',
      password: 'secret-password',
      password_confirmation: 'secret-password',
    })

    expect(register).toHaveBeenCalled()
    expect(createToken).not.toHaveBeenCalled()
    expect(auth.isAuthenticated).toBe(false)
  })

  it('sends the theme chosen before login to the profile', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, theme: 'dark' })
    guestTheme.value = 'dark'
    const auth = useAuthStore()

    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    expect(updateProfile).toHaveBeenCalledWith({ theme: 'dark' })
    expect(auth.user?.theme).toBe('dark')
    expect(guestTheme.value).toBeNull()
  })

  it('sends the theme and the language chosen before login to the profile at once', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, theme: 'dark', locale: 'en' })
    guestTheme.value = 'dark'
    guestLocale.value = 'en'
    const auth = useAuthStore()

    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    expect(updateProfile).toHaveBeenCalledExactlyOnceWith({ theme: 'dark', locale: 'en' })
    expect(auth.user?.locale).toBe('en')
    expect(guestLocale.value).toBeNull()
  })

  it('keeps the profile language when the guest chose the same one', async () => {
    guestLocale.value = 'pl'
    const auth = useAuthStore()

    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    expect(updateProfile).not.toHaveBeenCalled()
    expect(guestLocale.value).toBeNull()
  })

  it('keeps the profile theme when nothing was chosen before login', async () => {
    const auth = useAuthStore()

    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    expect(updateProfile).not.toHaveBeenCalled()
    expect(auth.user?.theme).toBe('light')
  })

  it('logs out locally even when the API request fails', async () => {
    vi.mocked(deleteToken).mockRejectedValue(new Error('Network error'))
    const auth = useAuthStore()
    await auth.login({ email: 'test@example.com', password: 'secret-password' })

    await auth.logout()

    expect(token.value).toBeNull()
    expect(auth.user).toBeNull()
  })

  it('loads the user only once', async () => {
    vi.mocked(getProfile).mockResolvedValue({ ...user, role: 'admin' })
    token.value = 'test-token'
    const auth = useAuthStore()

    await auth.loadUser()
    await auth.loadUser()

    expect(getProfile).toHaveBeenCalledTimes(1)
    expect(auth.isAdmin).toBe(true)
  })
})
