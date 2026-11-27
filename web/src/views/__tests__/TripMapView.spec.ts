import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import { afterEach, describe, expect, it, vi } from 'vitest'

import { getTrip } from '@/api/trips'
import { i18n } from '@/i18n'
import { useAuthStore } from '@/stores/auth'
import type { LineFeature, PointFeature } from '@/types/map'
import type { Trip } from '@/types/trip'
import type { User } from '@/types/user'
import TripMapView from '@/views/TripMapView.vue'

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

vi.mock('@/api/trips')
vi.mock('mapbox-gl', () => ({ default: {} }))

const trip: Trip = {
  id: 1,
  user_id: 1,
  name: 'Summer Trip',
  starts_on: '2026-07-12',
  ends_on: null,
  description: null,
  countries: ['DE', 'FR'],
  points: [
    { id: 10, trip_id: 1, name: 'Berlin', latitude: 52.52, longitude: 13.405, country_code: 'DE', position: 1 },
    { id: 11, trip_id: 1, name: 'Paris', latitude: 48.8566, longitude: 2.3522, country_code: 'FR', position: 2 },
  ],
  segments: [
    {
      id: 20,
      from_point_id: 10,
      to_point_id: 11,
      transport: 'car',
      distance_km: 877.5,
      is_approximate: false,
      geometry: { type: 'LineString', coordinates: [[13.405, 52.52], [2.3522, 48.8566]] },
    },
  ],
}

async function mountMap() {
  const pinia = createPinia()
  setActivePinia(pinia)
  useAuthStore().setUser(user)
  const wrapper = mount(TripMapView, {
    props: { id: 1 },
    global: { plugins: [pinia, i18n], stubs: { RouterLink: RouterLinkStub, TripMap: true } },
  })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('TripMapView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('gives the map the segments, points and countries of the trip', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = await mountMap()
    const map = wrapper.findComponent({ name: 'TripMap' })

    expect(getTrip).toHaveBeenCalledWith(1)
    expect((map.props('lines') as LineFeature[]).map((line) => [line.id, line.properties.transport])).toEqual([
      [20, 'car'],
    ])
    expect((map.props('points') as PointFeature[]).map((point) => point.geometry.coordinates)).toEqual([
      [13.405, 52.52],
      [2.3522, 48.8566],
    ])
    expect(map.props('countries')).toEqual(['DE', 'FR'])
  })

  it('lists the segments next to the map with the trip summary and legend', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = await mountMap()

    expect(wrapper.text()).toContain('Summer Trip')
    expect(wrapper.text()).toContain('Niemcy, Francja')
    expect(wrapper.text()).toContain('1. Berlin → Paris')
    expect(wrapper.text()).toMatch(/878\s+km/)
    expect(wrapper.text()).toContain('Legenda')
  })

  it('shows the transport and distance of the chosen segment', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = await mountMap()

    await wrapper.find('button[aria-pressed]').trigger('click')

    expect(wrapper.find('[aria-live="polite"]').text()).toContain('Odcinek 1 z 1')
    expect(wrapper.find('[aria-live="polite"]').text()).toContain('Samochód')
    expect(wrapper.findComponent({ name: 'TripMap' }).props('selectedLine')).toBe(20)
  })

  it('shows the name and country of the chosen point', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = await mountMap()

    wrapper.findComponent({ name: 'TripMap' }).vm.$emit('selectPoint', 11)
    await flushPromises()

    expect(wrapper.find('[aria-live="polite"]').text()).toContain('Paris')
    expect(wrapper.find('[aria-live="polite"]').text()).toContain('Francja')
  })

  it('shows a trip of another user read only with a way back to the profile', async () => {
    vi.mocked(getTrip).mockResolvedValue({ ...trip, user_id: 7 })
    const wrapper = await mountMap()
    const links = wrapper.findAllComponents(RouterLinkStub).map((link) => link.props('to'))

    expect(links).toContainEqual({ name: 'discover.show', params: { id: 7 } })
    expect(links).not.toContainEqual({ name: 'trips.edit', params: { id: 1 } })
    expect(wrapper.text()).toContain('Profil autora')
  })

  it('says when the trip cannot be loaded', async () => {
    vi.mocked(getTrip).mockRejectedValue(new Error('Network Error'))
    const wrapper = await mountMap()

    expect(wrapper.text()).toContain('Nie udało się wczytać mapy.')
  })
})
