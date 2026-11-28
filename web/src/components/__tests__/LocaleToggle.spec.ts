import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { updateProfile } from '@/api/profile'
import LocaleToggle from '@/components/LocaleToggle.vue'
import { TooltipProvider } from '@/components/ui/tooltip'
import { guestLocale, i18n, setLocale } from '@/i18n'
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

  return mount(TooltipProvider, { slots: { default: LocaleToggle }, global: { plugins: [pinia, i18n] } })
}

enableAutoUnmount(afterEach)

describe('LocaleToggle', () => {
  afterEach(() => {
    token.value = null
    setLocale('pl')
    guestLocale.value = null
    localStorage.clear()
    vi.resetAllMocks()
  })

  it('switches a guest to English and remembers the choice in the browser', async () => {
    const wrapper = mountToggle()

    expect(wrapper.find('button').text()).toBe('pl')
    expect(wrapper.find('button').attributes('aria-label')).toBe('PL — zmień język na angielski')

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(i18n.global.locale.value).toBe('en')
    expect(document.documentElement.lang).toBe('en')
    expect(localStorage.getItem('rth-locale')).toBe('en')
    expect(guestLocale.value).toBe('en')
    expect(updateProfile).not.toHaveBeenCalled()
    expect(wrapper.find('button').attributes('aria-label')).toBe('EN — switch the language to Polish')
  })

  it('saves the language in the profile of a logged-in user', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, locale: 'en' })
    token.value = 'test-token'
    const wrapper = mountToggle()
    useAuthStore().setUser(user)

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith({ locale: 'en' })
    expect(useAuthStore().user?.locale).toBe('en')
    expect(i18n.global.locale.value).toBe('en')
    expect(guestLocale.value).toBeNull()
  })

  it('goes back to the previous language when the profile cannot be saved', async () => {
    vi.mocked(updateProfile).mockRejectedValue(new Error('Network error'))
    token.value = 'test-token'
    const wrapper = mountToggle()
    useAuthStore().setUser(user)

    await wrapper.find('button').trigger('click')
    await flushPromises()

    expect(i18n.global.locale.value).toBe('pl')
    expect(document.documentElement.lang).toBe('pl')
  })
})
