import { enableAutoUnmount, mount } from '@vue/test-utils'
import { afterEach, describe, expect, it } from 'vitest'

import EffectsToggle from '@/components/EffectsToggle.vue'
import { TooltipProvider } from '@/components/ui/tooltip'
import { i18n } from '@/i18n'
import { effectsEnabled } from '@/lib/celebrate'

enableAutoUnmount(afterEach)

describe('EffectsToggle', () => {
  afterEach(() => {
    effectsEnabled.value = true
    localStorage.clear()
  })

  it('switches the animated effects off and on', async () => {
    const wrapper = mount(TooltipProvider, { slots: { default: EffectsToggle }, global: { plugins: [i18n] } })
    const button = wrapper.find('button')

    expect(button.attributes('aria-label')).toBe('Efekty animowane')
    expect(button.attributes('aria-pressed')).toBe('true')

    await button.trigger('click')

    expect(effectsEnabled.value).toBe(false)
    expect(button.attributes('aria-pressed')).toBe('false')

    await button.trigger('click')

    expect(effectsEnabled.value).toBe(true)
  })
})
