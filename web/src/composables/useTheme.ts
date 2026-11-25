import { useColorMode } from '@vueuse/core'

export type Theme = 'light' | 'dark'

export function useTheme() {
  const theme = useColorMode<Theme>({
    initialValue: 'light',
    storageKey: 'rth-theme',
    modes: { light: '', dark: 'dark' },
  })

  return { theme }
}
