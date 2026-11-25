import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { updatePassword, updateProfile } from '@/api/profile'
import { i18n } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/user'
import ProfileView from '@/views/ProfileView.vue'

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

function mountProfile() {
  const pinia = createPinia()
  setActivePinia(pinia)
  useAuthStore().setUser(user)

  return mount(ProfileView, { global: { plugins: [pinia, i18n] }, attachTo: document.body })
}

enableAutoUnmount(afterEach)

describe('ProfileView', () => {
  afterEach(() => {
    vi.resetAllMocks()
    document.body.innerHTML = ''
  })

  it('fills the form with the current name and e-mail', () => {
    const wrapper = mountProfile()

    expect((wrapper.find('#name').element as HTMLInputElement).value).toBe('Test User')
    expect((wrapper.find('#email').element as HTMLInputElement).value).toBe('test@example.com')
  })

  it('saves the new name without touching the e-mail', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, name: 'Renamed User' })
    const wrapper = mountProfile()

    await wrapper.find('#name').setValue('Renamed User')
    await wrapper.findAll('form')[0]?.trigger('submit')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith({ name: 'Renamed User' })
    expect(useAuthStore().user?.name).toBe('Renamed User')
  })

  it('keeps the current e-mail until the new one is confirmed', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, pending_email: 'renamed@example.com' })
    const wrapper = mountProfile()

    await wrapper.find('#email').setValue('renamed@example.com')
    await wrapper.findAll('form')[0]?.trigger('submit')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith({ name: 'Test User', email: 'renamed@example.com' })
    expect((wrapper.find('#email').element as HTMLInputElement).value).toBe('test@example.com')
    expect(wrapper.find('#email-description').text()).toContain('renamed@example.com')
  })

  it('makes the profile public', async () => {
    vi.mocked(updateProfile).mockResolvedValue({ ...user, is_public: true })
    const wrapper = mountProfile()

    await wrapper.find('#visibility-public').trigger('click')
    await flushPromises()

    expect(updateProfile).toHaveBeenCalledWith({ is_public: true })
    expect(useAuthStore().user?.is_public).toBe(true)
  })

  it('shows a wrong current password under its field', async () => {
    vi.mocked(updatePassword).mockRejectedValue(
      new AxiosError('Unprocessable', 'ERR_BAD_REQUEST', undefined, null, {
        status: 422,
        statusText: '',
        headers: {},
        config: {} as InternalAxiosRequestConfig,
        data: { errors: { current_password: ['The password is incorrect.'] } },
      }),
    )
    const wrapper = mountProfile()

    await wrapper.find('#current_password').setValue('wrong-password')
    await wrapper.findAll('form')[1]?.trigger('submit')
    await flushPromises()

    expect(wrapper.find('#current_password-error').text()).toBe('The password is incorrect.')
  })
})
