import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { AxiosError, type InternalAxiosRequestConfig } from 'axios'
import { afterEach, describe, expect, it, vi } from 'vitest'
import { h } from 'vue'

import { addPoint, movePoint } from '@/api/points'
import { updateSegment } from '@/api/segments'
import { createTrip, deleteTrip, getTrip } from '@/api/trips'
import PlaceSearch from '@/components/PlaceSearch.vue'
import { TooltipProvider } from '@/components/ui/tooltip'
import { i18n } from '@/i18n'
import type { Place, Trip } from '@/types/trip'
import TripEditView from '@/views/TripEditView.vue'

const { push, replace } = vi.hoisted(() => ({
  push: vi.fn<(to: unknown) => Promise<void>>(),
  replace: vi.fn<(to: unknown) => Promise<void>>(),
}))

vi.mock('vue-router', async (importOriginal) => ({
  ...(await importOriginal<typeof import('vue-router')>()),
  useRouter: () => ({ push, replace }),
}))
vi.mock('@/api/trips')
vi.mock('@/api/points')
vi.mock('@/api/segments')
vi.mock('@/api/places')
vi.mock('@/lib/celebrate')

const trip: Trip = {
  id: 1,
  name: 'Summer Trip',
  starts_on: '2026-07-12',
  ends_on: null,
  description: null,
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
      geometry: { type: 'LineString', coordinates: [] },
    },
  ],
}

function mountEditor(id?: number) {
  return mount(TooltipProvider, {
    slots: { default: () => h(TripEditView, { id }) },
    global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } },
    attachTo: document.body,
  })
}

function button(label: string, root: ParentNode = document.body): HTMLButtonElement | undefined {
  return [...root.querySelectorAll('button')].find(
    (element) => element.textContent?.trim() === label || element.getAttribute('aria-label') === label,
  )
}

enableAutoUnmount(afterEach)

describe('TripEditView', () => {
  afterEach(() => {
    vi.resetAllMocks()
  })

  it('creates a trip and opens its editor', async () => {
    vi.mocked(createTrip).mockResolvedValue({ ...trip, id: 5, points: undefined, segments: undefined })
    const wrapper = mountEditor()

    await wrapper.find('#trip-name').setValue('Summer Trip')
    await wrapper.find('#trip-starts-on').setValue('2026-07-12')
    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(createTrip).toHaveBeenCalledWith({
      name: 'Summer Trip',
      starts_on: '2026-07-12',
      ends_on: null,
      description: null,
    })
    expect(replace).toHaveBeenCalledWith({ name: 'trips.edit', params: { id: 5 } })
  })

  it('shows the error under the field', async () => {
    vi.mocked(createTrip).mockRejectedValue(
      new AxiosError('Unprocessable', '422', undefined, undefined, {
        status: 422,
        statusText: 'Unprocessable Content',
        headers: {},
        config: {} as InternalAxiosRequestConfig,
        data: { errors: { name: ['Pole nazwa jest wymagane.'] } },
      }),
    )
    const wrapper = mountEditor()

    await wrapper.find('form').trigger('submit')
    await flushPromises()

    expect(wrapper.find('#trip-name-error').text()).toBe('Pole nazwa jest wymagane.')
  })

  it('shows the points and segments of the trip', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = mountEditor(1)
    await flushPromises()

    expect(getTrip).toHaveBeenCalledWith(1)
    expect((wrapper.find('#trip-name').element as HTMLInputElement).value).toBe('Summer Trip')
    expect(wrapper.text()).toContain('Berlin')
    expect(wrapper.text()).toContain('Francja')
    expect(wrapper.text()).toMatch(/878\s+km/)
  })

  it('adds a found place and reloads the trip', async () => {
    const place: Place = { name: 'Rome', label: 'Rome, Italy', country_code: 'IT', latitude: 41.9, longitude: 12.5 }
    vi.mocked(getTrip).mockResolvedValue(trip)
    const wrapper = mountEditor(1)
    await flushPromises()

    wrapper.findComponent(PlaceSearch).vm.$emit('select', place)
    await flushPromises()

    expect(addPoint).toHaveBeenCalledWith(1, place)
    expect(getTrip).toHaveBeenCalledTimes(2)
  })

  it('moves a point down', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    mountEditor(1)
    await flushPromises()

    button('Przesuń niżej: Berlin')?.click()
    await flushPromises()

    expect(movePoint).toHaveBeenCalledWith(10, 2)
    expect(getTrip).toHaveBeenCalledTimes(2)
  })

  it('changes the transport of a segment', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    mountEditor(1)
    await flushPromises()

    button('Samolot')?.click()
    await flushPromises()

    expect(updateSegment).toHaveBeenCalledWith(20, 'plane')
  })

  it('deletes the trip only after confirmation', async () => {
    vi.mocked(getTrip).mockResolvedValue(trip)
    mountEditor(1)
    await flushPromises()

    button('Usuń podróż')?.click()
    await flushPromises()

    expect(deleteTrip).not.toHaveBeenCalled()

    const dialog = document.body.querySelector('[role="dialog"]')
    expect(dialog?.textContent).toContain('Usunąć podróż?')
    button('Usuń podróż', dialog ?? document.body)?.click()
    await flushPromises()

    expect(deleteTrip).toHaveBeenCalledWith(1)
    expect(push).toHaveBeenCalledWith({ name: 'trips.index' })
  })
})
