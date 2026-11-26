<script setup lang="ts">
defineProps<{
  title: string
  description?: string
}>()
</script>

<template>
  <header class="flex flex-col gap-1">
    <h1 class="text-2xl font-semibold text-balance md:text-3xl">{{ title }}</h1>
    <p v-if="description" class="text-pretty text-muted-foreground">{{ description }}</p>
  </header>
</template>
