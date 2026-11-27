import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { getMap } from '@/api/map'
import { getStatistics } from '@/api/statistics'
import { i18n } from '@/i18n'
import type { Statistics, TripsMap } from '@/types/map'
import MapView from '@/views/MapView.vue'

vi.mock('@/api/map')
vi.mock('@/api/statistics')
vi.mock('mapbox-gl', () => ({ default: {} }))

const tripsMap: TripsMap = {
  type: 'FeatureCollection',
  features: [
    {
      type: 'Feature',
      id: 30,
      geometry: { type: 'LineString', coordinates: [[13.405, 52.52], [2.6502, 39.5696]] },
      properties: { trip_id: 1, trip_name: 'Summer Trip', transport: 'plane', distance_km: 1690.2 },
    },
  ],
}

const statistics: Statistics = {
  trips_count: 2,
  countries_count: 2,
  countries: ['ES', 'DE'],
  places_count: 3,
  distance_km: 1690.2,
  distance_by_transport: { car: 0, plane: 1690.2, train: 0, other: 0 },
  years: [2026, 2025],
  distance_by_year: [],
}

async function mountMap() {
  const wrapper = mount(MapView, {
    global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub, TripMap: true } },
  })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('MapView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('shows all routes with the visited countries and the years to choose', async () => {
    vi.mocked(getMap).mockResolvedValue(tripsMap)
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountMap()

    expect(getMap).toHaveBeenCalledWith(null)
    expect(getStatistics).toHaveBeenCalledWith(null)
    expect(wrapper.text()).toContain('Podróże: 2 · Państwa: 2')
    expect(wrapper.findAll('li').map((item) => item.text())).toEqual(
      expect.arrayContaining(['Hiszpania', 'Niemcy']),
    )
    expect(wrapper.findAll('button').map((button) => button.text())).toEqual(
      expect.arrayContaining(['Wszystkie', '2026', '2025']),
    )
    expect(wrapper.findComponent({ name: 'TripMap' }).props('countries')).toEqual(['ES', 'DE'])
  })

  it('loads the routes and countries of the chosen year', async () => {
    vi.mocked(getMap).mockResolvedValue(tripsMap)
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountMap()

    await wrapper.findAll('button').find((button) => button.text() === '2025')?.trigger('click')
    await flushPromises()

    expect(getMap).toHaveBeenLastCalledWith(2025)
    expect(getStatistics).toHaveBeenLastCalledWith(2025)
  })

  it('shows which trip the chosen route belongs to', async () => {
    vi.mocked(getMap).mockResolvedValue(tripsMap)
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountMap()

    wrapper.findComponent({ name: 'TripMap' }).vm.$emit('selectLine', 30)
    await flushPromises()

    const panel = wrapper.find('[aria-live="polite"]')
    expect(panel.text()).toContain('Summer Trip')
    expect(panel.text()).toContain('Samolot')
    expect(panel.findComponent(RouterLinkStub).props('to')).toEqual({ name: 'trips.map', params: { id: 1 } })
  })

  it('says when the map cannot be loaded', async () => {
    vi.mocked(getMap).mockRejectedValue(new Error('Network Error'))
    vi.mocked(getStatistics).mockResolvedValue(statistics)
    const wrapper = await mountMap()

    expect(wrapper.text()).toContain('Nie udało się wczytać mapy.')
  })
})
