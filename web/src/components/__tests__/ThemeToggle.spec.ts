import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { updateProfile } from '@/api/profile'
import ThemeToggle from '@/components/ThemeToggle.vue'
import { TooltipProvider } from '@/components/ui/tooltip'
import { guestTheme } from '@/composables/useTheme'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import { useAuthStore } from '@/stores/auth'
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

function mountToggle() {
  const pinia = createPinia()
  setActivePinia(pinia)

  return mount(TooltipProvider, { slots: { default: ThemeToggle }, global: { plugins: [pinia, i18n] } })
}

enableAutoUnmount(afterEach)

describe('ThemeToggle', () => {
  afterEach(() => {
    token.value = null
    guestTheme.value = null
    localStorage.clear()
    document.documentElement.className = ''
    vi.resetAllMocks()
  })

  it('switches a guest to the dark theme and remembers the choice in the browser', async () => {
    const wrapper = mountToggle()

    expect(wrapper.find('button').attributes('aria-label')).toBe('Włącz ciemny motyw')

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(guestTheme.value).toBe('dark')
    expect(updateProfile).not.toHaveBeenCalled()
    expect(wrapper.find('button').attributes('aria-label')).toBe('Włącz jasny motyw')
  })

  it('saves the theme in the profile of a logged-in user', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, theme: 'dark' })
    token.value = 'test-token'
    const wrapper = mountToggle()
    useAuthStore().setUser(user)

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith({ theme: 'dark' })
    expect(useAuthStore().user?.theme).toBe('dark')
    expect(document.documentElement.classList.contains('dark')).toBe(true)
    expect(guestTheme.value).toBeNull()
  })
})
