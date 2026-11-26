import { useColorMode, useLocalStorage } from '@vueuse/core'

export type Theme = 'light' | 'dark'

export const guestTheme = useLocalStorage<Theme | null>('rth-guest-theme', null)

export function useTheme() {
  const theme = useColorMode<Theme>({
    initialValue: 'light',
    storageKey: 'rth-theme',
    modes: { light: '', dark: 'dark' },
  })

  return { theme }
}
