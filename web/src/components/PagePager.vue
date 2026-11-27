<script setup lang="ts">
import { computed } from 'vue'

import { Button } from '@/components/ui/button'

const props = defineProps<{
  page: number
  lastPage: number
  total: number
  perPage: number
}>()

const emit = defineEmits<{
  change: [page: number]
}>()

const range = computed(() => {
  const first = props.total === 0 ? 0 : (props.page - 1) * props.perPage + 1

  return { first, last: Math.min(props.page * props.perPage, props.total), total: props.total }
})
</script>

<template>
  <nav class="flex flex-wrap items-center justify-between gap-3" :aria-label="$t('admin.pages')">
    <p class="text-sm text-muted-foreground tabular-nums">{{ $t('admin.range', range) }}</p>
    <div class="flex gap-2">
      <Button variant="outline" size="sm" :disabled="page <= 1" @click="emit('change', page - 1)">
        {{ $t('admin.previous') }}
      </Button>
      <Button variant="outline" size="sm" :disabled="page >= lastPage" @click="emit('change', page + 1)">
        {{ $t('admin.next') }}
      </Button>
    </div>
  </nav>
</template>
