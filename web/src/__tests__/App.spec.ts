import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { nextTick } from 'vue'

import App from '@/App.vue'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'

describe('App', () => {
  it('sends a guest to the login page in Polish', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    token.value = null

    await router.push('/')
    await router.isReady()

    const wrapper = mount(App, { global: { plugins: [pinia, router, i18n] } })

    expect(router.currentRoute.value.name).toBe('login')
    expect(wrapper.text()).toContain('RTH')
    expect(wrapper.text()).toContain('Zaloguj się')
  })

  it('applies the theme saved in the profile', async () => {
    const pinia = createPinia()
    setActivePinia(pinia)
    token.value = null
    await router.push('/')
    await router.isReady()
    mount(App, { global: { plugins: [pinia, router, i18n] } })

    useAuthStore().setUser({
      id: 1,
      name: 'Test User',
      email: 'test@example.com',
      pending_email: null,
      role: 'user',
      is_public: false,
      locale: 'pl',
      theme: 'dark',
    })
    await nextTick()
    await nextTick()

    expect(document.documentElement.classList.contains('dark')).toBe(true)
    document.documentElement.className = ''
    localStorage.clear()
  })
})
