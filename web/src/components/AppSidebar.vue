<script setup lang="ts">
import { ChartColumn, Compass, LayoutGrid, LogOut, MapIcon, MapPin, Route, User, Users } from '@lucide/vue'
import { computed, watch } from 'vue'
import { useRoute } from 'vue-router'

import AppLogo from '@/components/AppLogo.vue'
import { Avatar, AvatarFallback } from '@/components/ui/avatar'
import { Button } from '@/components/ui/button'
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarGroup,
  SidebarGroupContent,
  SidebarGroupLabel,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/ui/sidebar'
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip'
import { useAuthStore } from '@/stores/auth'

const auth = useAuthStore()
const route = useRoute()
const { setOpenMobile } = useSidebar()

const mainItems = [
  { name: 'trips.index', label: 'nav.trips', icon: Route },
  { name: 'map', label: 'nav.map', icon: MapIcon },
  { name: 'stats', label: 'nav.stats', icon: ChartColumn },
  { name: 'discover', label: 'nav.discover', icon: Compass },
  { name: 'profile', label: 'nav.profile', icon: User },
]

const adminItems = [
  { name: 'admin.dashboard', label: 'nav.dashboard', icon: LayoutGrid },
  { name: 'admin.users', label: 'nav.users', icon: Users },
  { name: 'admin.trips', label: 'nav.userTrips', icon: MapPin },
]

const initials = computed(() =>
  (auth.user?.name ?? '')
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((word) => word[0]?.toUpperCase())
    .join(''),
)

watch(() => route.fullPath, () => setOpenMobile(false))
</script>

<template>
  <Sidebar>
    <SidebarHeader class="px-4 py-3">
      <AppLogo />
    </SidebarHeader>

    <SidebarContent>
      <SidebarGroup>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in mainItems" :key="item.name">
              <SidebarMenuButton as-child :is-active="route.name === item.name">
                <RouterLink :to="{ name: item.name }">
                  <component :is="item.icon" aria-hidden="true" />
                  <span>{{ $t(item.label) }}</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>

      <SidebarGroup v-if="auth.isAdmin">
        <SidebarGroupLabel>{{ $t('nav.admin') }}</SidebarGroupLabel>
        <SidebarGroupContent>
          <SidebarMenu>
            <SidebarMenuItem v-for="item in adminItems" :key="item.name">
              <SidebarMenuButton as-child :is-active="route.name === item.name">
                <RouterLink :to="{ name: item.name }">
                  <component :is="item.icon" aria-hidden="true" />
                  <span>{{ $t(item.label) }}</span>
                </RouterLink>
              </SidebarMenuButton>
            </SidebarMenuItem>
          </SidebarMenu>
        </SidebarGroupContent>
      </SidebarGroup>
    </SidebarContent>

    <SidebarFooter>
      <div class="flex items-center gap-2 border-t border-sidebar-border px-2 pt-3">
        <Avatar>
          <AvatarFallback>{{ initials }}</AvatarFallback>
        </Avatar>
        <div class="min-w-0 flex-1">
          <p class="truncate text-sm font-medium">{{ auth.user?.name }}</p>
          <p class="truncate text-xs text-muted-foreground">{{ auth.user?.email }}</p>
        </div>
        <Tooltip>
          <TooltipTrigger as-child>
            <Button variant="ghost" size="icon" :aria-label="$t('nav.logout')" @click="auth.logout()">
              <LogOut aria-hidden="true" />
            </Button>
          </TooltipTrigger>
          <TooltipContent>{{ $t('nav.logout') }}</TooltipContent>
        </Tooltip>
      </div>
    </SidebarFooter>
  </Sidebar>
</template>
