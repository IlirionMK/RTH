import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { getSystemStatistics, listAdminUsers } from '@/api/admin'
import { i18n } from '@/i18n'
import type { AdminUser, SystemStatistics } from '@/types/admin'
import AdminDashboardView from '@/views/AdminDashboardView.vue'

vi.mock('@/api/admin')

const statistics: SystemStatistics = {
  users_count: 12,
  trips_count: 45,
  months: Array.from({ length: 12 }, (_, index) => ({
    month: `2026-${String(index + 1).padStart(2, '0')}`,
    users: index,
    trips: index * 2,
  })),
}

function account(id: number): AdminUser {
  return {
    id,
    name: `User ${id}`,
    email: `user${id}@example.com`,
    role: 'user',
    is_public: false,
    is_blocked: false,
    blocked_at: null,
    email_verified_at: '2026-11-01T10:00:00Z',
    created_at: '2026-11-01T10:00:00Z',
    trips_count: id,
  }
}

async function mountDashboard() {
  const wrapper = mount(AdminDashboardView, { global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } } })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('AdminDashboardView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('shows the totals, this month and the newest accounts', async () => {
    vi.mocked(getSystemStatistics).mockResolvedValue(statistics)
    vi.mocked(listAdminUsers).mockResolvedValue({
      data: [1, 2, 3, 4, 5, 6].map(account),
      meta: { current_page: 1, last_page: 1, total: 6 },
    })
    const wrapper = await mountDashboard()
    const tiles = wrapper.findAll('dl dd').map((tile) => tile.text())

    expect(listAdminUsers).toHaveBeenCalledWith({ query: '', role: '', status: '', page: 1 })
    expect(tiles).toEqual(['12', '45', '11', '22'])
    expect(wrapper.text()).toContain('user5@example.com')
    expect(wrapper.text()).not.toContain('user6@example.com')
  })

  it('keeps a table of the monthly chart for screen readers', async () => {
    vi.mocked(getSystemStatistics).mockResolvedValue(statistics)
    vi.mocked(listAdminUsers).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } })
    const wrapper = await mountDashboard()
    const rows = wrapper.findAll('table.sr-only tbody tr')

    expect(rows).toHaveLength(12)
    expect(rows[11]?.findAll('td').map((cell) => cell.text())).toEqual(['11', '22'])
  })

  it('says when the data cannot be loaded', async () => {
    vi.mocked(getSystemStatistics).mockRejectedValue(new Error('Network Error'))
    vi.mocked(listAdminUsers).mockResolvedValue({ data: [], meta: { current_page: 1, last_page: 1, total: 0 } })
    const wrapper = await mountDashboard()

    expect(wrapper.text()).toContain('Nie udało się wczytać danych.')
  })
})
