import { describe, expect, it } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'

import App from '@/App.vue'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import router from '@/router'

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
})
