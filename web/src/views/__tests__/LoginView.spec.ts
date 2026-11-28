import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { axeViolations } from '@/__tests__/axe'
import { createToken } from '@/api/auth'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import router from '@/router'
import type { User } from '@/types/user'
import LoginView from '@/views/LoginView.vue'

vi.mock('@/api/auth')

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

async function mountLogin() {
  const pinia = createPinia()
  setActivePinia(pinia)
  await router.replace({ name: 'login' })

  const wrapper = mount(LoginView, { global: { plugins: [pinia, router, i18n] } })
  await wrapper.find('#email').setValue('test@example.com')
  await wrapper.find('#password').setValue('secret-password')

  return wrapper
}

enableAutoUnmount(afterEach)

describe('LoginView', () => {
  beforeEach(() => {
    token.value = null
  })

  afterEach(() => {
    token.value = null
    vi.resetAllMocks()
  })

  it('shows the API error under the e-mail field', async () => {
    vi.mocked(createToken).mockRejectedValue(
      new AxiosError('Unprocessable', 'ERR_BAD_REQUEST', undefined, null, {
        status: 422,
        statusText: '',
        headers: {},
        config: {} as InternalAxiosRequestConfig,
        data: { errors: { email: ['These credentials do not match our records.'] } },
      }),
    )
    const wrapper = await mountLogin()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('#email-error').text()).toBe('These credentials do not match our records.')
    expect(wrapper.find('#email').attributes('aria-invalid')).toBe('true')
    expect(wrapper.find('#email').attributes('aria-describedby')).toBe('email-error')
  })

  it('has no accessibility errors', async () => {
    const wrapper = await mountLogin()

    expect(await axeViolations(wrapper)).toEqual([])
  })

  it('opens the trips after login', async () => {
    vi.mocked(createToken).mockResolvedValue({ token: 'test-token', user })
    const wrapper = await mountLogin()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(createToken).toHaveBeenCalledWith({ email: 'test@example.com', password: 'secret-password' })
    expect(router.currentRoute.value.name).toBe('trips.index')
  })
})
