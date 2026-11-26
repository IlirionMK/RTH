import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { listTrips } from '@/api/trips'
import { i18n } from '@/i18n'
import type { Page, Point, Trip } from '@/types/trip'
import TripsView from '@/views/TripsView.vue'

vi.mock('@/api/trips')

function point(id: number, name: string, countryCode: string): Point {
  return { id, trip_id: 1, name, latitude: 50, longitude: 10, country_code: countryCode, position: id }
}

const summer: Trip = {
  id: 1,
  name: 'Summer Trip',
  starts_on: '2026-07-12',
  ends_on: '2026-07-19',
  description: null,
  distance_km: 4115.4,
  countries: ['DE', 'ES'],
  transports: ['car', 'plane'],
  points: [point(1, 'Berlin', 'DE'), point(2, 'Palma', 'ES')],
}

const winter: Trip = { ...summer, id: 2, name: 'Winter Trip', points: [], countries: [], transports: [] }

function page(data: Trip[], currentPage = 1, lastPage = 1): Page<Trip> {
  return { data, meta: { current_page: currentPage, last_page: lastPage, total: data.length } }
}

function mountTrips() {
  return mount(TripsView, { global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } } })
}

enableAutoUnmount(afterEach)

describe('TripsView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('shows a placeholder while the trips load', () => {
    vi.mocked(listTrips).mockReturnValue(new Promise(() => {}))
    const wrapper = mountTrips()

    expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true)
  })

  it('shows each trip with its route, countries, transport and distance', async () => {
    vi.mocked(listTrips).mockResolvedValue(page([summer]))
    const wrapper = mountTrips()
    await flushPromises()

    expect(listTrips).toHaveBeenCalledWith(1)
    expect(wrapper.text()).toContain('Summer Trip')
    expect(wrapper.text()).toContain('Berlin → Palma')
    expect(wrapper.text()).toContain('Niemcy')
    expect(wrapper.text()).toContain('Hiszpania')
    expect(wrapper.text()).toContain('Samochód')
    expect(wrapper.text()).toMatch(/4\s?115\s+km/)
    const link = wrapper.findAllComponents(RouterLinkStub).find((item) => item.text() === 'Summer Trip')
    expect(link?.props('to')).toEqual({ name: 'trips.edit', params: { id: 1 } })
  })

  it('invites to add the first trip', async () => {
    vi.mocked(listTrips).mockResolvedValue(page([]))
    const wrapper = mountTrips()
    await flushPromises()

    expect(wrapper.text()).toContain('Nie masz jeszcze podróży')
  })

  it('shows an error and loads the trips again', async () => {
    vi.mocked(listTrips).mockRejectedValueOnce(new Error('Network Error')).mockResolvedValueOnce(page([summer]))
    const wrapper = mountTrips()
    await flushPromises()

    expect(wrapper.text()).toContain('Nie udało się wczytać podróży.')

    await wrapper.findAll('button').find((button) => button.text() === 'Spróbuj ponownie')?.trigger('click')
    await flushPromises()

    expect(listTrips).toHaveBeenCalledTimes(2)
    expect(wrapper.text()).toContain('Summer Trip')
  })

  it('adds the next page below the first one', async () => {
    vi.mocked(listTrips).mockResolvedValueOnce(page([summer], 1, 2)).mockResolvedValueOnce(page([winter], 2, 2))
    const wrapper = mountTrips()
    await flushPromises()

    await wrapper.findAll('button').find((button) => button.text() === 'Pokaż więcej')?.trigger('click')
    await flushPromises()

    expect(listTrips).toHaveBeenLastCalledWith(2)
    expect(wrapper.text()).toContain('Summer Trip')
    expect(wrapper.text()).toContain('Winter Trip')
    expect(wrapper.findAll('button').some((button) => button.text() === 'Pokaż więcej')).toBe(false)
  })
})
