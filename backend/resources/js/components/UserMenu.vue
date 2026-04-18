<template>
  <AppDropdown align="right">
    <template #trigger>
      <button
        class="w-8 h-8 rounded-full bg-white/10 border border-white/15
               flex items-center justify-center
               text-xs font-semibold text-white/70 uppercase tracking-wide
               hover:bg-white/15 hover:border-white/25 transition-colors
               focus:outline-none cursor-pointer"
        :title="user?.name"
      >
        {{ initials }}
      </button>
    </template>

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
import { computed } from 'vue'
import { useRouter } from 'vue-router'
import AppDropdown     from '@/components/AppDropdown.vue'
import AppDropdownItem from '@/components/AppDropdownItem.vue'
import SunMoonIcon     from '@/components/SunMoonIcon.vue'
import { useSession }        from '@/composables/core/useSession'
import { useEnterpriseStore } from '@/stores/enterpriseStore'
import { useApi }            from '@/lib/api'
import { useTheme }          from '@/composables/core/useTheme'

const router          = useRouter()
const session         = useSession()
const enterpriseStore = useEnterpriseStore()
const api             = useApi()
const { isDark, toggle } = useTheme()

const user = session.user

const initials = computed(() => {
  const name = user.value?.name ?? ''
  return name
    .split(' ')
    .slice(0, 2)
    .map(w => w[0] ?? '')
    .join('')
    .toUpperCase()
})

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
