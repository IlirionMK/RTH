import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { axeViolations } from '@/__tests__/axe'
import { getStatistics } from '@/api/statistics'
import { i18n } from '@/i18n'
import type { Statistics } from '@/types/map'
import StatsView from '@/views/StatsView.vue'

vi.mock('@/api/statistics')

const statistics: Statistics = {
  trips_count: 3,
  countries_count: 2,
  countries: ['ES', 'DE'],
  places_count: 9,
  distance_km: 10065,
  distance_by_transport: { car: 875, plane: 8560, train: 630, other: 0 },
  years: [2026, 2025],
  distance_by_year: [
    { year: 2025, car: 100, plane: 5000, train: 270, other: 0 },
    { year: 2026, car: 775, plane: 3560, train: 360, other: 0 },
  ],
}

async function mountStats() {
  const wrapper = mount(StatsView, { global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } } })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('StatsView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('shows the totals, the distance by transport and the visited countries', async () => {
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountStats()
    const text = wrapper.text()

    expect(getStatistics).toHaveBeenCalledWith(null)
    expect(text).toContain('Cała historia')
    expect(text).toMatch(/Łączny dystans\s*10\s?065\s*km/)
    expect(text).toMatch(/Samolot\s*8\s?560\s+km/)
    expect(text).toContain('Hiszpania')
    expect(text).toContain('Niemcy')
  })

  it('has no accessibility errors', async () => {
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountStats()

    expect(await axeViolations(wrapper)).toEqual([])
  })

  it('keeps a table with the distance of each year for screen readers', async () => {
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountStats()

    const rows = wrapper.findAll('table tbody tr').map((row) => row.findAll('th, td').map((cell) => cell.text()))
    expect(rows).toHaveLength(2)
    expect(rows[0]?.[0]).toBe('2025')
    expect(rows[1]?.[5]).toMatch(/4\s?695\s+km/)
  })

  it('loads the statistics of the chosen year', async () => {
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountStats()

    await wrapper.findAll('button').find((button) => button.text() === '2025')?.trigger('click')
    await flushPromises()

    expect(getStatistics).toHaveBeenLastCalledWith(2025)
    expect(wrapper.text()).toContain('Rok 2025')
  })

  it('says when the statistics cannot be loaded', async () => {
    vi.mocked(getStatistics).mockRejectedValue(new Error('Network Error'))
    const wrapper = await mountStats()

    expect(wrapper.text()).toContain('Nie udało się wczytać statystyk.')
  })
})
