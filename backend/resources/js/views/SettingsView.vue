<template>
  <AppLayout>
    <template #header-left>
      <AppMenuSwitcher />
    </template>

    <template #header-right>
      <UserMenu />
    </template>

    <div class="flex items-start p-6 gap-6">

      <SettingsSidebar />

      <!-- Content -->
      <div class="flex-1 rounded-2xl border border-white/8 bg-white/3 overflow-hidden">
        <GeneralPanel   v-if="activeNav === 'general'" />
        <EnterprisePanel v-else-if="activeNav === 'enterprise'" />
        <MembersPanel   v-else-if="activeNav === 'members'" />
        <RolesPanel     v-else-if="activeNav === 'roles'" />
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePermissions } from '@/composables/core/usePermissions'
import AppLayout       from '@/components/AppLayout.vue'
import AppMenuSwitcher from '@/components/AppMenuSwitcher.vue'
import UserMenu        from '@/components/UserMenu.vue'
import SettingsSidebar  from './Settings/SettingsSidebar.vue'
import GeneralPanel     from './Settings/GeneralPanel.vue'
import EnterprisePanel  from './Settings/EnterprisePanel.vue'
import MembersPanel     from './Settings/MembersPanel.vue'
import RolesPanel       from './Settings/RolesPanel.vue'

const route  = useRoute()
const router = useRouter()
const { canViewSettings, canViewMembers, canManageRoles } = usePermissions()

const NAV_GUARDS: Record<string, () => boolean> = {
  enterprise: () => canViewSettings.value,
  members:    () => canViewMembers.value,
  roles:      () => canManageRoles.value,
}

const activeNav = computed(() => {
  const nav = route.query.nav as string
  const guard = NAV_GUARDS[nav]
  if (guard && !guard()) return 'general'
  return nav || 'general'
})

watch(activeNav, (nav) => {
  if (nav !== route.query.nav) {
    router.replace({ query: { nav } })
  }
}, { immediate: true })
</script>
