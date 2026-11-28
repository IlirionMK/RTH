import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, describe, expect, it, vi } from 'vitest'

import {
  getPublicProfile,
  getPublicProfileMap,
  getPublicProfileStatistics,
  listPublicProfileTrips,
} from '@/api/publicProfiles'
import { axeViolations } from '@/__tests__/axe'
import { i18n } from '@/i18n'
import type { Statistics, TripsMap } from '@/types/map'
import type { Trip } from '@/types/trip'
import PublicProfileView from '@/views/PublicProfileView.vue'

vi.mock('@/api/publicProfiles')
vi.mock('mapbox-gl', () => ({ default: {} }))

const statistics: Statistics = {
  trips_count: 1,
  countries_count: 2,
  countries: ['DE', 'ES'],
  places_count: 2,
  distance_km: 1690,
  distance_by_transport: { car: 0, plane: 1690, train: 0, other: 0 },
  years: [2026],
  distance_by_year: [{ year: 2026, car: 0, plane: 1690, train: 0, other: 0 }],
}

const tripsMap: TripsMap = {
  type: 'FeatureCollection',
  features: [
    {
      type: 'Feature',
      id: 30,
      geometry: { type: 'LineString', coordinates: [[13.405, 52.52], [2.6502, 39.5696]] },
      properties: { trip_id: 5, trip_name: 'Summer Trip', transport: 'plane', distance_km: 1690 },
    },
  ],
}

const trip: Trip = { id: 5, user_id: 3, name: 'Summer Trip', starts_on: '2026-07-12', ends_on: null, description: null, distance_km: 1690 }

function mockProfile(): void {
  vi.mocked(getPublicProfile).mockResolvedValue({ id: 3, name: 'Anna Test' })
  vi.mocked(listPublicProfileTrips).mockResolvedValue({ data: [trip], meta: { current_page: 1, last_page: 1, total: 1 } })
  vi.mocked(getPublicProfileMap).mockResolvedValue(tripsMap)
  vi.mocked(getPublicProfileStatistics).mockResolvedValue(statistics)
}

async function mountProfile() {
  const wrapper = mount(PublicProfileView, {
    props: { id: 3 },
    global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub, TripMap: true } },
  })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('PublicProfileView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('shows the profile read only with statistics, map and trips', async () => {
    mockProfile()
    const wrapper = await mountProfile()

    expect(wrapper.text()).toContain('Anna Test')
    expect(wrapper.text()).toContain('Tylko do odczytu')
    expect(wrapper.text()).toMatch(/Łączny dystans\s*1\s?690\s*km/)
    expect(wrapper.findComponent({ name: 'TripMap' }).props('countries')).toEqual(['DE', 'ES'])
    expect(wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))).toContainEqual({
      name: 'trips.map',
      params: { id: 5 },
    })
    expect(wrapper.find('form').exists()).toBe(false)
  })

  it('has no accessibility errors', async () => {
    mockProfile()
    const wrapper = await mountProfile()

    expect(await axeViolations(wrapper)).toEqual([])
  })

  it('loads the statistics and map of the chosen year', async () => {
    mockProfile()
    const wrapper = await mountProfile()

    await wrapper.findAll('button').find((button) => button.text() === '2026')?.trigger('click')
    await flushPromises()

    expect(getPublicProfileMap).toHaveBeenLastCalledWith(3, 2026)
    expect(getPublicProfileStatistics).toHaveBeenLastCalledWith(3, 2026)
  })

  it('says when the profile is private or missing', async () => {
    mockProfile()
    vi.mocked(getPublicProfile).mockRejectedValue(new Error('Not Found'))
    const wrapper = await mountProfile()

    expect(wrapper.text()).toContain('Profil nie istnieje albo jest prywatny.')
  })
})
