<script setup lang="ts">
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { toast } from 'vue-sonner'

import { updateProfile } from '@/api/profile'
import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { guestLocale, type Locale, setLocale } from '@/i18n'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const { t, locale } = useI18n()

const label = computed(() => t('locale.switch', { code: locale.value.toUpperCase() }))

async function toggle(): Promise<void> {
  const previous = locale.value as Locale
  const next: Locale = previous === 'pl' ? 'en' : 'pl'

  setLocale(next)

  if (!auth.isAuthenticated) {
    guestLocale.value = next

    return
  }

  try {
    auth.setUser(await updateProfile({ locale: next }))
  } catch {
    setLocale(previous)
    toast.error(t('common.error'))
  }
}
</script>

<template>
  <Tooltip>
    <TooltipTrigger as-child>
      <Button variant="ghost" size="icon" :aria-label="label" @click="toggle">
        <span class="text-xs font-semibold uppercase" aria-hidden="true">{{ locale }}</span>
      </Button>
    </TooltipTrigger>
    <TooltipContent>{{ label }}</TooltipContent>
  </Tooltip>
</template>
