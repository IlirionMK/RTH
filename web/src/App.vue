<script setup lang="ts">
import { watch } from 'vue'

import { Toaster } from '@/components/ui/sonner'
import { TooltipProvider } from '@/components/ui/tooltip'
import { useTheme } from '@/composables/useTheme'
import { useAuthStore } from '@/stores/auth'

const { theme } = useTheme()
const auth = useAuthStore()

watch(
  () => auth.user?.theme,
  (value) => {
    if (value) {
      theme.value = value
    }
  },
  { immediate: true },
)
</script>

<template>
  <TooltipProvider>
    <RouterView />
  </TooltipProvider>
  <Toaster />
</template>
