import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { confirmEmailChange, verifyEmail } from '@/api/email'
import { getProfile } from '@/api/profile'
import { i18n } from '@/i18n'
import { token } from '@/lib/api'
import router from '@/router'
import { useAuthStore } from '@/stores/auth'
import type { User } from '@/types/user'
import EmailConfirmationView from '@/views/EmailConfirmationView.vue'

vi.mock('@/api/email')
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

const link = { user: '1', hash: 'test-hash', expires: '1795600000', signature: 'test-signature' }

async function mountConfirmation(name: 'email-verification' | 'email-change') {
  const pinia = createPinia()
  setActivePinia(pinia)
  await router.replace({
    name,
    params: { user: link.user, hash: link.hash },
    query: { expires: link.expires, signature: link.signature },
  })

  const wrapper = mount(EmailConfirmationView, {
    props: { kind: name === 'email-verification' ? 'verification' : 'change' },
    global: { plugins: [pinia, router, i18n] },
  })
  await flushPromises()

  return wrapper
}

function apiError(status: number) {
  return new AxiosError('Request failed', 'ERR_BAD_REQUEST', undefined, null, {
    status,
    statusText: '',
    headers: {},
    config: {} as InternalAxiosRequestConfig,
    data: { message: 'Invalid signature.' },
  })
}

enableAutoUnmount(afterEach)

describe('EmailConfirmationView', () => {
  afterEach(() => {
    token.value = null
    vi.resetAllMocks()
  })

  it('confirms the e-mail from the link and offers to log in', async () => {
    vi.mocked(verifyEmail).mockResolvedValue()

    const wrapper = await mountConfirmation('email-verification')

    expect(verifyEmail).toHaveBeenCalledWith(link)
    expect(wrapper.text()).toContain('Adres potwierdzony')
    expect(wrapper.find('a[href="/login"]').exists()).toBe(true)
  })

  it('explains that the link is invalid or expired', async () => {
    vi.mocked(verifyEmail).mockRejectedValue(apiError(403))

    const wrapper = await mountConfirmation('email-verification')

    expect(wrapper.text()).toContain('Link nie działa')
  })

  it('explains that the new e-mail was taken in the meantime', async () => {
    vi.mocked(confirmEmailChange).mockRejectedValue(apiError(409))

    const wrapper = await mountConfirmation('email-change')

    expect(wrapper.text()).toContain('Adres jest zajęty')
  })

  it('refreshes the logged-in user after the new e-mail is confirmed', async () => {
    vi.mocked(confirmEmailChange).mockResolvedValue()
    vi.mocked(getProfile).mockResolvedValue({ ...user, email: 'renamed@example.com' })
    token.value = 'test-token'

    const wrapper = await mountConfirmation('email-change')

    expect(confirmEmailChange).toHaveBeenCalledWith(link)
    expect(useAuthStore().user?.email).toBe('renamed@example.com')
    expect(wrapper.find('a[href="/profile"]').exists()).toBe(true)
  })
})
