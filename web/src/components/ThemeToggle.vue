<script setup lang="ts">
import { Moon, Sun } from '@lucide/vue'
import { computed, nextTick } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { updateProfile } from '@/api/profile'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { guestTheme, type Theme, useTheme } from '@/composables/useTheme'
import { revealFrom } from '@/lib/transitions'
import { useAuthStore } from '@/stores/auth'

const { theme } = useTheme()
const auth = useAuthStore()
const { t } = useI18n()

const label = computed(() => t(theme.value === 'dark' ? 'theme.toLight' : 'theme.toDark'))

async function toggle(event: MouseEvent): Promise<void> {
  const previous = theme.value
  const next: Theme = previous === 'dark' ? 'light' : 'dark'

  await revealFrom(event.currentTarget as HTMLElement, async () => {
    theme.value = next
    await nextTick()
  })

  if (!auth.isAuthenticated) {
    guestTheme.value = next

    return
  }

  try {
    auth.setUser(await updateProfile({ theme: next }))
  } catch {
    theme.value = previous
    toast.error(t('common.error'))
  }
}
</script>

<template>
  <Tooltip>
    <TooltipTrigger as-child>
      <Button variant="ghost" size="icon" :aria-label="label" @click="toggle">
        <Sun v-if="theme === 'dark'" aria-hidden="true" />
        <Moon v-else aria-hidden="true" />
      </Button>
    </TooltipTrigger>
    <TooltipContent>{{ label }}</TooltipContent>
  </Tooltip>
</template>
