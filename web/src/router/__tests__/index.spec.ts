import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { getProfile } from '@/api/profile'
import { token } from '@/lib/api'
import router from '@/router'
import type { User } from '@/types/user'

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

describe('router', () => {
  beforeEach(async () => {
    setActivePinia(createPinia())
    token.value = null
    await router.replace({ name: 'login' })
  })

  afterEach(() => {
    token.value = null
    vi.resetAllMocks()
  })

  it('sends a guest from a protected page to the login page', async () => {
    await router.push({ name: 'profile' })

    expect(router.currentRoute.value.name).toBe('login')
  })

  it('sends a logged-in user from the login page to the trips', async () => {
    vi.mocked(getProfile).mockResolvedValue(user)
    token.value = 'test-token'

    await router.push({ name: 'register' })

    expect(router.currentRoute.value.name).toBe('trips.index')
  })

  it('keeps a regular user out of the administration', async () => {
    vi.mocked(getProfile).mockResolvedValue(user)
    token.value = 'test-token'

    await router.push({ name: 'admin.users' })

    expect(router.currentRoute.value.name).toBe('trips.index')
  })

  it('lets an administrator into the administration', async () => {
    vi.mocked(getProfile).mockResolvedValue({ ...user, role: 'admin' })
    token.value = 'test-token'

    await router.push({ name: 'admin.users' })

    expect(router.currentRoute.value.name).toBe('admin.users')
  })

  it('opens e-mail confirmation links for guests and logged-in users', async () => {
    await router.push({ name: 'email-verification', params: { user: '1', hash: 'test-hash' } })

    expect(router.currentRoute.value.name).toBe('email-verification')

    vi.mocked(getProfile).mockResolvedValue(user)
    token.value = 'test-token'

    await router.push({ name: 'email-change', params: { user: '1', hash: 'test-hash' } })

    expect(router.currentRoute.value.name).toBe('email-change')
  })

  it('logs out when the profile cannot be loaded', async () => {
    vi.mocked(getProfile).mockRejectedValue(new Error('Network error'))
    token.value = 'test-token'

    await router.push({ name: 'profile' })

    expect(router.currentRoute.value.name).toBe('login')
    expect(token.value).toBeNull()
  })
})
