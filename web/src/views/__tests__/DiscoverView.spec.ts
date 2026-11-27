import { enableAutoUnmount, flushPromises, mount, RouterLinkStub } from '@vue/test-utils'
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest'

import { listPublicProfiles } from '@/api/publicProfiles'
import { i18n } from '@/i18n'
import type { PublicProfile } from '@/types/profile'
import type { Page } from '@/types/trip'
import DiscoverView from '@/views/DiscoverView.vue'

vi.mock('@/api/publicProfiles')

function page(data: PublicProfile[]): Page<PublicProfile> {
  return { data, meta: { current_page: 1, last_page: 1, total: data.length } }
}

const anna: PublicProfile = { id: 3, name: 'Anna Test', trips_count: 12, countries_count: 9 }

async function mountDiscover() {
  const wrapper = mount(DiscoverView, { global: { plugins: [i18n], stubs: { RouterLink: RouterLinkStub } } })
  await flushPromises()

  return wrapper
}

enableAutoUnmount(afterEach)

describe('DiscoverView', () => {
  beforeEach(() => {
    vi.useFakeTimers()
  })

  afterEach(() => {
    vi.useRealTimers()
    vi.resetAllMocks()
  })

  it('lists public profiles with their trips and countries', async () => {
    vi.mocked(listPublicProfiles).mockResolvedValue(page([anna]))
    const wrapper = await mountDiscover()

    expect(listPublicProfiles).toHaveBeenCalledWith('', 1)
    expect(wrapper.text()).toContain('Anna Test')
    expect(wrapper.text()).toContain('AT')
    expect(wrapper.text()).toContain('Podróże: 12 · Państwa: 9')
    expect(wrapper.findComponent(RouterLinkStub).props('to')).toEqual({ name: 'discover.show', params: { id: 3 } })
  })

  it('searches after two letters and a pause in typing', async () => {
    vi.mocked(listPublicProfiles).mockResolvedValue(page([anna]))
    const wrapper = await mountDiscover()

    await wrapper.find('#profile-search').setValue('A')
    await vi.advanceTimersByTimeAsync(300)
    expect(listPublicProfiles).toHaveBeenCalledTimes(1)

    await wrapper.find('#profile-search').setValue('Ann')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(listPublicProfiles).toHaveBeenLastCalledWith('Ann', 1)
  })

  it('says when nobody matches', async () => {
    vi.mocked(listPublicProfiles).mockResolvedValueOnce(page([anna])).mockResolvedValueOnce(page([]))
    const wrapper = await mountDiscover()

    await wrapper.find('#profile-search').setValue('Zed')
    await vi.advanceTimersByTimeAsync(300)
    await flushPromises()

    expect(wrapper.text()).toContain('Nie znaleziono profili')
  })
})
