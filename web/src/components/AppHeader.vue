<script setup lang="ts">
import { PanelLeft } from '@lucide/vue'

import AppLogo from '@/components/AppLogo.vue'
import { Button } from '@/components/ui/button'
import { useSidebar } from '@/components/ui/sidebar'

const { toggleSidebar } = useSidebar()
</script>

<template>
  <header class="flex h-14 items-center gap-2 border-b px-4 md:hidden">
    <Button variant="ghost" size="icon" :aria-label="$t('nav.openMenu')" @click="toggleSidebar">
      <PanelLeft aria-hidden="true" />
    </Button>
    <AppLogo />
  </header>
</template>
