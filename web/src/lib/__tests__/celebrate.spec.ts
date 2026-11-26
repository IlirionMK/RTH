import { afterEach, describe, expect, it, vi } from 'vitest'

import { celebrate, effectsEnabled, type SakugaEffects } from '@/lib/celebrate'

function fakeEffects(): SakugaEffects {
  return {
    at: vi.fn<SakugaEffects['at']>(() => ({ x: 100, y: 200, w: 80, h: 36 })),
    burst: vi.fn<SakugaEffects['burst']>(),
    puff: vi.fn<SakugaEffects['puff']>(),
    ticks: vi.fn<SakugaEffects['ticks']>(),
    kira: vi.fn<SakugaEffects['kira']>(),
    sfx: vi.fn<SakugaEffects['sfx']>(),
  }
}

function reducedMotion(matches: boolean): void {
  vi.stubGlobal('matchMedia', () => ({ matches }))
}

describe('celebrate', () => {
  afterEach(() => {
    vi.unstubAllGlobals()
    Reflect.deleteProperty(window, 'SakugaFX')
    document.head.querySelectorAll('script').forEach((script) => script.remove())
  })

  it('draws a burst with a sound word at the element', async () => {
    reducedMotion(false)
    const effects = fakeEffects()
    window.SakugaFX = effects
    const button = document.createElement('button')

    await celebrate(button, 'don')

    expect(effects.at).toHaveBeenCalledWith(button)
    expect(effects.burst).toHaveBeenCalledWith(100, 200, expect.any(Object))
    expect(effects.sfx).toHaveBeenCalledWith('don', expect.any(Number), expect.any(Number), expect.any(Object))
  })

  it('loads the effects only when they are first needed', async () => {
    reducedMotion(false)
    const pending = celebrate(document.createElement('button'), 'pon')

    const script = document.head.querySelector<HTMLScriptElement>('script[src="/sakuga-vfx/core.js"]')
    expect(script).not.toBeNull()
    const effects = fakeEffects()
    window.SakugaFX = effects
    script?.dispatchEvent(new Event('load'))
    await pending

    expect(effects.sfx).toHaveBeenCalledWith('pon', expect.any(Number), expect.any(Number), expect.any(Object))
  })

  it('stays still when the effects are switched off', async () => {
    reducedMotion(false)
    effectsEnabled.value = false

    await celebrate(document.createElement('button'), 'don')

    expect(document.head.querySelector('script[src="/sakuga-vfx/core.js"]')).toBeNull()
    effectsEnabled.value = true
  })

  it('stays still when the system asks for reduced motion', async () => {
    reducedMotion(true)

    await celebrate(document.createElement('button'), 'don')

    expect(document.head.querySelector('script[src="/sakuga-vfx/core.js"]')).toBeNull()
  })
})
