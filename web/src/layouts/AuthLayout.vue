<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue'
import ThemeToggle from '@/components/ThemeToggle.vue'
</script>

<template>
  <main class="relative flex min-h-svh flex-col items-center justify-center gap-6 bg-muted/40 p-4">
    <div class="absolute top-4 right-4">
      <ThemeToggle />
    </div>
    <AppLogo />
    <div class="flex w-full max-w-sm flex-col gap-6 [view-transition-name:auth-card]">
      <RouterView />
    </div>
  </main>
</template>
