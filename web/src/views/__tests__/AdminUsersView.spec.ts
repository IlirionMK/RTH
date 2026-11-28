import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { axeViolations } from '@/__tests__/axe'
import { listAdminUsers, updateAdminUser } from '@/api/admin'
import { i18n } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import type { AdminUser } from '@/types/admin'
import type { User } from '@/types/user'
import AdminUsersView from '@/views/AdminUsersView.vue'

vi.mock('@/api/admin')

const me: User = {
  id: 1,
  name: 'Admin User',
  email: 'admin@example.com',
  pending_email: null,
  role: 'admin',
  is_public: true,
  locale: 'pl',
  theme: 'light',
}

function account(id: number, name: string, changes: Partial<AdminUser> = {}): AdminUser {
  return {
    id,
    name,
    email: `user${id}@example.com`,
    role: 'user',
    is_public: false,
    is_blocked: false,
    blocked_at: null,
    email_verified_at: '2026-11-01T10:00:00Z',
    created_at: '2026-11-01T10:00:00Z',
    trips_count: 3,
    ...changes,
  }
}

const admin = account(1, 'Admin User', { role: 'admin' })
const tester = account(2, 'Test User')

function mockAccounts(lastPage = 1): void {
  vi.mocked(listAdminUsers).mockResolvedValue({
    data: [admin, tester],
    meta: { current_page: 1, last_page: lastPage, total: 2 },
  })
}

async function mountUsers() {
  const pinia = createPinia()
  setActivePinia(pinia)
  useAuthStore().setUser(me)
  const wrapper = mount(AdminUsersView, { global: { plugins: [pinia, i18n] }, attachTo: document.body })
  await flushPromises()

  return wrapper
}

function button(label: string, root: ParentNode = document.body): HTMLButtonElement | undefined {
  return [...root.querySelectorAll('button')].find(
    (element) => element.getAttribute('aria-label') === label || element.textContent?.trim() === label,
  )
}

enableAutoUnmount(afterEach)

describe('AdminUsersView', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.resetAllMocks()
  })

  it('lists the accounts and locks the controls of the signed-in admin', async () => {
    mockAccounts()
    const wrapper = await mountUsers()

    expect(listAdminUsers).toHaveBeenCalledWith({ query: '', role: '', status: '', page: 1 })
    expect(wrapper.text()).toContain('user2@example.com')
    expect(wrapper.text()).toContain('Liczba kont: 2')
    expect(button('Zablokuj: Admin User')?.disabled).toBe(true)
    expect(button('Zablokuj: Test User')?.disabled).toBe(false)
    expect((wrapper.find('select[aria-label="Rola: Admin User"]').element as HTMLSelectElement).disabled).toBe(true)
  })

  it('has no accessibility errors', async () => {
    vi.useRealTimers()
    mockAccounts()
    const wrapper = await mountUsers()

    expect(await axeViolations(wrapper)).toEqual([])
  })

  it('filters by role and searches after a pause in typing', async () => {
    mockAccounts()
    const wrapper = await mountUsers()

    await wrapper.find('select[aria-label="Rola"]').setValue('admin')
    await flushPromises()
    expect(listAdminUsers).toHaveBeenLastCalledWith({ query: '', role: 'admin', status: '', page: 1 })

    await wrapper.find('input[type="search"]').setValue('test')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()
    expect(listAdminUsers).toHaveBeenLastCalledWith({ query: 'test', role: 'admin', status: '', page: 1 })
  })

  it('changes the role of an account', async () => {
    mockAccounts()
    vi.mocked(updateAdminUser).mockResolvedValue({ ...tester, role: 'admin' })
    const wrapper = await mountUsers()

    await wrapper.find('select[aria-label="Rola: Test User"]').setValue('admin')
    await flushPromises()

    expect(updateAdminUser).toHaveBeenCalledWith(2, { role: 'admin' })
  })

  it('blocks an account only after confirmation', async () => {
    mockAccounts()
    vi.mocked(updateAdminUser).mockResolvedValue({ ...tester, is_blocked: true, blocked_at: '2026-11-27T10:00:00Z' })
    const wrapper = await mountUsers()

    button('Zablokuj: Test User')?.click()
    await flushPromises()
    expect(updateAdminUser).not.toHaveBeenCalled()

    const dialog = document.body.querySelector('[role="dialog"]')
    expect(dialog?.textContent).toContain('Zablokować konto Test User?')
    button('Zablokuj', dialog ?? document.body)?.click()
    await flushPromises()

    expect(updateAdminUser).toHaveBeenCalledWith(2, { is_blocked: true })
    expect(wrapper.text()).toContain('Zablokowane')
  })

  it('opens the next page', async () => {
    mockAccounts(2)
    const wrapper = await mountUsers()

    await wrapper.findAll('button').find((item) => item.text() === 'Następna')?.trigger('click')
    await flushPromises()

    expect(listAdminUsers).toHaveBeenLastCalledWith({ query: '', role: '', status: '', page: 2 })
  })
})
