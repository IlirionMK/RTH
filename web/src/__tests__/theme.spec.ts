import { afterEach, describe, expect, it } from 'vitest'
import { nextTick } from 'vue'

import { useTheme } from '@/composables/useTheme'

describe('useTheme', () => {
  afterEach(() => {
    localStorage.clear()
    document.documentElement.className = ''
  })

  it('starts with the light theme', () => {
    const { theme } = useTheme()

    expect(theme.value).toBe('light')
    expect(document.documentElement.classList.contains('dark')).toBe(false)
  })

  it('applies the dark theme to the page', async () => {
    const { theme } = useTheme()

    theme.value = 'dark'
    await nextTick()

    expect(document.documentElement.classList.contains('dark')).toBe(true)
  })

  it('remembers the theme in the browser', async () => {
    useTheme().theme.value = 'dark'
    await nextTick()

    expect(useTheme().theme.value).toBe('dark')
  })
})
