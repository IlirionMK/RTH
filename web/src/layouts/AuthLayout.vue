<script setup lang="ts">
import AppLogo from '@/components/AppLogo.vue'
</script>

<template>
  <main class="flex min-h-svh flex-col items-center justify-center gap-6 bg-muted/40 p-4">
    <AppLogo />
    <div class="flex w-full max-w-sm flex-col gap-6">
      <RouterView />
    </div>
  </main>
</template>
