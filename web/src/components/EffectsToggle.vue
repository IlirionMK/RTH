<script setup lang="ts">
import { Sparkle, Sparkles } from '@lucide/vue'

import { Button } from '@/components/ui/button'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { effectsEnabled } from '@/lib/celebrate'
</script>

<template>
  <Tooltip>
    <TooltipTrigger as-child>
      <Button
        variant="ghost"
        size="icon"
        :aria-label="$t('effects.label')"
        :aria-pressed="effectsEnabled"
        @click="effectsEnabled = !effectsEnabled"
      >
        <Sparkles v-if="effectsEnabled" aria-hidden="true" />
        <Sparkle v-else class="opacity-60" aria-hidden="true" />
      </Button>
    </TooltipTrigger>
    <TooltipContent>{{ $t(effectsEnabled ? 'effects.on' : 'effects.off') }}</TooltipContent>
  </Tooltip>
</template>
