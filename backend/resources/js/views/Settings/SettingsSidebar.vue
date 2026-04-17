<template>
  <nav class="w-52 shrink-0 flex flex-col gap-0.5 pt-1">
    <SettingsSidebarItem
      v-for="item in items"
      :key="item.nav"
      :icon="item.icon"
      :label="item.label"
      :is-active="activeNav === item.nav"
      @select="navigate(item.nav)"
    />
  </nav>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePermissions } from '@/composables/core/usePermissions'
import SettingsSidebarItem from './SettingsSidebarItem.vue'

const route  = useRoute()
const router = useRouter()
const { canViewSettings, canViewMembers, canManageRoles } = usePermissions()

const activeNav = computed(() => (route.query.nav as string) || 'general')

const items = computed(() => {
  const list = [
    { nav: 'general', icon: 'ui/settings', label: 'General' },
  ]

  if (canViewSettings.value) {
    list.push({ nav: 'enterprise', icon: 'ui/building', label: 'Empresa' })
  }

  if (canViewMembers.value) {
    list.push({ nav: 'members', icon: 'ui/users', label: 'Miembros' })
  }

  if (canManageRoles.value) {
    list.push({ nav: 'roles', icon: 'ui/shield', label: 'Roles' })
  }

  return list
})

function navigate(nav: string) {
  router.replace({ path: '/app/settings', query: { nav } })
}
</script>
