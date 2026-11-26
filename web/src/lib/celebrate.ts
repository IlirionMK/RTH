import { useLocalStorage } from '@vueuse/core'

type Point = { x: number; y: number; w: number; h: number }

export interface SakugaEffects {
  at: (element: Element) => Point
  burst: (x: number, y: number, options?: { r?: number; life?: number }) => void
  puff: (x: number, y: number, options?: { size?: number; vx?: number; vy?: number }) => void
  ticks: (x: number, y: number, options?: { dir?: number; n?: number; r?: number; len?: number; fan?: number }) => void
  kira: (x: number, y: number, options?: { r?: number }) => void
  sfx: (word: SoundWord, x: number, y: number, options?: { size?: number; rot?: number }) => void
}

export type SoundWord = 'don' | 'pon'

declare global {
  interface Window {
    SakugaFX?: SakugaEffects
  }
}

export const effectsEnabled = useLocalStorage('rth-effects', true)

let loading: Promise<SakugaEffects | undefined> | null = null

function loadEffects(): Promise<SakugaEffects | undefined> {
  if (window.SakugaFX) {
    return Promise.resolve(window.SakugaFX)
  }

  loading ??= new Promise((resolve) => {
    const script = document.createElement('script')
    script.src = '/sakuga-vfx/core.js'
    script.addEventListener('load', () => resolve(window.SakugaFX))
    script.addEventListener('error', () => {
      loading = null
      resolve(undefined)
    })
    document.head.append(script)
  })

  return loading
}

export async function celebrate(element: Element, word: SoundWord): Promise<void> {
  if (!effectsEnabled.value || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    return
  }

  const effects = await loadEffects()

  if (!effects) {
    return
  }

  const { x, y, w } = effects.at(element)
  effects.burst(x, y, { r: 64, life: 0.42 })
  effects.puff(x - w / 2 - 6, y, { size: 12, vx: -40, vy: -10 })
  effects.puff(x + w / 2 + 6, y, { size: 12, vx: 40, vy: -10 })
  effects.ticks(x, y - 26, { dir: -Math.PI / 2, n: 5, r: 16, len: 18, fan: 0.5 })
  effects.sfx(word, x + 12, y - 70, { size: 120, rot: -0.12 })
  effects.kira(x + w / 2, y - 18, { r: 10 })
}
