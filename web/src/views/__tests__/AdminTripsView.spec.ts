import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { axeViolations } from '@/__tests__/axe'
import { listAdminTrips } from '@/api/admin'
import { deleteTrip } from '@/api/trips'
import { TooltipProvider } from '@/components/ui/tooltip'
import { i18n } from '@/i18n'
import type { AdminTrip } from '@/types/admin'
import AdminTripsView from '@/views/AdminTripsView.vue'

vi.mock('@/api/admin')
vi.mock('@/api/trips')

const trip: AdminTrip = {
  id: 5,
  name: 'Summer Trip',
  starts_on: '2026-07-12',
  ends_on: null,
  created_at: '2026-11-01T10:00:00Z',
  distance_km: 1690.4,
  countries: ['DE', 'ES'],
  user: { id: 2, name: 'Test User', email: 'test@example.com' },
}

function mockTrips(): void {
  vi.mocked(listAdminTrips).mockResolvedValue({ data: [trip], meta: { current_page: 1, last_page: 1, total: 1 } })
}

async function mountTrips() {
  const wrapper = mount(TooltipProvider, {
    slots: { default: AdminTripsView },
    global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } },
    attachTo: document.body,
  })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('AdminTripsView', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.resetAllMocks()
  })

  it('lists all trips with author, countries and distance', async () => {
    mockTrips()
    const wrapper = await mountTrips()

    expect(listAdminTrips).toHaveBeenCalledWith('', 1)
    expect(wrapper.text()).toContain('Summer Trip')
    expect(wrapper.text()).toContain('Test User')
    expect(wrapper.text()).toContain('test@example.com')
    expect(wrapper.text()).toContain('Niemcy, Hiszpania')
    expect(wrapper.text()).toMatch(/1\s?690\s+km/)
  })

  it('has no accessibility errors', async () => {
    vi.useRealTimers()
    mockTrips()
    const wrapper = await mountTrips()

    expect(await axeViolations(wrapper)).toEqual([])
  })

  it('opens the trip map from its row', async () => {
    mockTrips()
    const wrapper = await mountTrips()

    const link = wrapper.findComponent(RouterLinkStub)

    expect(link.props('to')).toEqual({ name: 'trips.map', params: { id: 5 } })
    expect(link.text()).toContain('Summer Trip')
  })

  it('deletes a trip only after confirmation', async () => {
    mockTrips()
    const wrapper = await mountTrips()

    await wrapper.find('button[aria-label="Usuń podróż Summer Trip"]').trigger('click')
    await flushPromises()
    expect(deleteTrip).not.toHaveBeenCalled()

    const dialog = document.body.querySelector('[role="dialog"]')
    expect(dialog?.textContent).toContain('Test User')
    ;[...(dialog?.querySelectorAll('button') ?? [])].find((item) => item.textContent?.trim() === 'Usuń podróż')?.click()
    await flushPromises()

    expect(deleteTrip).toHaveBeenCalledWith(5)
    expect(listAdminTrips).toHaveBeenCalledTimes(2)
  })

  it('searches by trip name or author after a pause in typing', async () => {
    mockTrips()
    const wrapper = await mountTrips()

    await wrapper.find('input[type="search"]').setValue('rome')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(listAdminTrips).toHaveBeenLastCalledWith('rome', 1)
  })
})
