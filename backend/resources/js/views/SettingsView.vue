<template>
  <AppLayout>
    <template #header-right>
      <UserMenu />
    </template>

    <div class="flex items-start p-6 gap-6">

      <SettingsSidebar />

      <!-- Content -->
      <div class="flex-1 rounded-2xl border border-white/8 bg-white/3 overflow-hidden">
        <EnterprisePanel v-if="activeNav === 'enterprise'" />
        <MembersPanel   v-else-if="activeNav === 'members'" />
        <RolesPanel     v-else-if="activeNav === 'roles'" />
        <div v-else class="h-full flex items-center justify-center">
          <p class="text-sm text-white/25">{{ activeNav }}</p>
        </div>
      </div>

    </div>
  </AppLayout>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePermissions } from '@/composables/core/usePermissions'
import AppLayout       from '@/components/AppLayout.vue'
import UserMenu        from '@/components/UserMenu.vue'
import SettingsSidebar  from './Settings/SettingsSidebar.vue'
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
