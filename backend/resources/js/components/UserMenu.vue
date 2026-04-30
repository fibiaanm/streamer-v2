<template>
  <!-- Guest: botón de login simple -->
  <template v-if="isGuest">
    <a
      href="/login"
      class="px-3 py-1.5 rounded-lg text-xs font-medium text-white/60
             border border-white/15 hover:border-white/30 hover:text-white/80
             transition-colors"
    >
      Iniciar sesión
    </a>
  </template>

  <!-- Auth: menú completo -->
  <AppDropdown v-else align="right">
    <template #trigger>
      <button class="flex items-center justify-center rounded-full focus:outline-none cursor-pointer hover:opacity-80 transition-opacity" :title="user?.name">
        <UserAvatar v-if="user" :user="user" size="sm" />
      </button>
    </template>

    <!-- Back navigation (optional, e.g. from assistant) -->
    <div v-if="backUrl" class="py-1 border-b border-white/8">
      <AppDropdownItem icon="ui/chevron-left" @click="router.push(backUrl!)">
        Volver
      </AppDropdownItem>
    </div>

    <!-- Identity -->
    <div class="px-4 py-3 border-b border-white/8">
      <p class="text-sm font-medium text-white/80 truncate">{{ user?.name }}</p>
      <p class="text-xs text-white/35 truncate mt-0.5">{{ user?.email }}</p>
    </div>

    <!-- Main actions -->
    <div class="py-1">
      <AppDropdownItem icon="ui/settings" @click="goToSettings">
        Configuración
      </AppDropdownItem>
      <AppDropdownItem @click="toggle">
        <template #icon><SunMoonIcon :moon="isDark" /></template>
        {{ isDark ? 'Modo claro' : 'Modo oscuro' }}
      </AppDropdownItem>
    </div>

    <!-- Destructive -->
    <div class="border-t border-white/8 py-1">
      <AppDropdownItem icon="ui/switch-enterprise" @click="onSwitchEnterprise">
        Cambiar empresa
      </AppDropdownItem>

      <AppDropdownItem icon="ui/log-out" variant="danger" @click="onLogout">
        Cerrar sesión
      </AppDropdownItem>
    </div>
  </AppDropdown>
</template>

<script setup lang="ts">
import { useRouter } from 'vue-router'

const props = defineProps<{ backUrl?: string }>()
import AppDropdown     from '@/components/AppDropdown.vue'
import AppDropdownItem from '@/components/AppDropdownItem.vue'
import SunMoonIcon     from '@/components/SunMoonIcon.vue'
import UserAvatar      from '@/components/UserAvatar.vue'
import { useSession }        from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'
import { usePermissions }    from '@/composables/core/usePermissions'
import { useApi }            from '@/lib/api'
import { useTheme }          from '@/composables/core/useTheme'

const router             = useRouter()
const session            = useSession()
const enterpriseStore    = useEnterpriseStore()
const api                = useApi()
const { isDark, toggle } = useTheme()
const { isGuest }        = usePermissions()

const user = session.user

const goToSettings = () => router.push('/app/settings')

const onSwitchEnterprise = () => {
  enterpriseStore.clear()
  window.location.href = '/switch'
}

const onLogout = async () => {
  try {
    await api.post('/auth/logout')
  } finally {
    session.clear()
    enterpriseStore.clear()
    window.location.href = '/login'
  }
}
</script>
