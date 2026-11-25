import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { register } from '@/api/auth'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import router from '@/router'
import RegisterView from '@/views/RegisterView.vue'

vi.mock('@/api/auth')

async function mountRegister() {
  const pinia = createPinia()
  setActivePinia(pinia)
  await router.replace({ name: 'register' })

  const wrapper = mount(RegisterView, { global: { plugins: [pinia, router, i18n] } })
  await wrapper.find('#name').setValue('Test User')
  await wrapper.find('#email').setValue('test@example.com')
  await wrapper.find('#password').setValue('secret-password')
  await wrapper.find('#password_confirmation').setValue('secret-password')

  return wrapper
}

enableAutoUnmount(afterEach)

describe('RegisterView', () => {
  beforeEach(() => {
    token.value = null
  })

  afterEach(() => {
    token.value = null
    vi.resetAllMocks()
  })

  it('shows the API errors under their fields', async () => {
    vi.mocked(register).mockRejectedValue(
      new AxiosError('Unprocessable', 'ERR_BAD_REQUEST', undefined, null, {
        status: 422,
        statusText: '',
        headers: {},
        config: {} as InternalAxiosRequestConfig,
        data: { errors: { password: ['The password field confirmation does not match.'] } },
      }),
    )
    const wrapper = await mountRegister()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('#password-error').text()).toBe('The password field confirmation does not match.')
    expect(wrapper.find('#password').attributes('aria-invalid')).toBe('true')
  })

  it('asks to confirm the e-mail after registration', async () => {
    vi.mocked(register).mockResolvedValue()
    const wrapper = await mountRegister()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(register).toHaveBeenCalledWith({
      name: 'Test User',
      email: 'test@example.com',
      password: 'secret-password',
      password_confirmation: 'secret-password',
    })
    expect(wrapper.find('form').exists()).toBe(false)
    expect(wrapper.text()).toContain('test@example.com')
    expect(token.value).toBeNull()
    expect(router.currentRoute.value.name).toBe('register')
  })
})
