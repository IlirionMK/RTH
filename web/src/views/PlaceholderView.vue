<script setup lang="ts">
import PageHeader from '@/components/PageHeader.vue'

defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <div class="flex flex-col gap-6">
    <PageHeader :title="$t(title)" :description="description ? $t(description) : undefined" />
    <p class="text-muted-foreground">{{ $t('common.comingSoon') }}</p>
  </div>
</template>
