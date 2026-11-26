import { enableAutoUnmount, flushPromises, mount } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { searchPlaces } from '@/api/places'
import PlaceSearch from '@/components/PlaceSearch.vue'
import { i18n } from '@/i18n'
import type { Place } from '@/types/trip'

vi.mock('@/api/places')

const berlin: Place = { name: 'Berlin', label: 'Berlin, Germany', country_code: 'DE', latitude: 52.52, longitude: 13.405 }

function mountSearch() {
  return mount(PlaceSearch, { global: { plugins: [i18n] }, attachTo: document.body })
}

enableAutoUnmount(afterEach)

describe('PlaceSearch', () => {
  beforeEach(() => {
    vi.useFakeTimers()
    Element.prototype.scrollIntoView = vi.fn<() => void>()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.resetAllMocks()
  })

  it('waits for two letters and a pause in typing', async () => {
    vi.mocked(searchPlaces).mockResolvedValue([berlin])
    const wrapper = mountSearch()

    await wrapper.find('#place-search').setValue('B')
    await vi.advanceTimersByTimeAsync(300)
    expect(searchPlaces).not.toHaveBeenCalled()

    await wrapper.find('#place-search').setValue('Berl')
    await vi.advanceTimersByTimeAsync(299)
    expect(searchPlaces).not.toHaveBeenCalled()

    await vi.advanceTimersByTimeAsync(1)
    expect(searchPlaces).toHaveBeenCalledTimes(1)
    expect(searchPlaces).toHaveBeenCalledWith('Berl')
  })

  it('gives the chosen place to the trip', async () => {
    vi.mocked(searchPlaces).mockResolvedValue([berlin])
    const wrapper = mountSearch()

    await wrapper.find('#place-search').setValue('Berl')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    const option = [...document.body.querySelectorAll('[role="option"]')].find((element) =>
      element.textContent?.includes('Berlin, Germany'),
    ) as HTMLElement | undefined
    option?.click()
    await flushPromises()

    expect(wrapper.emitted('select')).toEqual([[berlin]])
  })

  it('says when the search is not available', async () => {
    vi.mocked(searchPlaces).mockRejectedValue(new Error('Service Unavailable'))
    const wrapper = mountSearch()

    await wrapper.find('#place-search').setValue('Berl')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(wrapper.find('#place-search-error').text()).toBe('Wyszukiwanie jest teraz niedostępne. Spróbuj później.')
  })
})
