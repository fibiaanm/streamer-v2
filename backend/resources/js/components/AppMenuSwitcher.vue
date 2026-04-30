<template>
  <div class="flex items-center gap-3">
    <AppDropdown align="left">
      <template #trigger>
        <button
          class="flex items-center justify-center w-7 h-7 rounded-lg text-white/40
                 hover:text-white/70 hover:bg-white/6 transition-colors cursor-pointer"
        >
          <AppIcon name="ui/menu" size="sm" />
        </button>
      </template>

      <div class="py-1">
        <AppDropdownItem
          v-for="app in apps"
          :key="app.path"
          :icon="app.icon"
          @click="navigate(app.path)"
        >
          <span :class="isActive(app) ? 'text-white/90' : ''">{{ app.name }}</span>
          <template v-if="isActive(app)" #icon>
            <AppIcon :name="app.icon" size="sm" class="text-brand-400" />
          </template>
        </AppDropdownItem>
      </div>
    </AppDropdown>

    <div class="flex items-center gap-2.5">
      <div class="w-5 h-5 rounded-md bg-brand-500 shadow-sm shadow-brand-500/40 shrink-0" />
      <span class="text-sm font-semibold text-white tracking-tight">{{ activeApp.name }}</span>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AppDropdown     from '@/components/AppDropdown.vue'
import AppDropdownItem from '@/components/AppDropdownItem.vue'
import AppIcon         from '@/components/AppIcon.vue'
import { usePermissions } from '@/composables/core/usePermissions'
import { useAppsEnabled } from '@/composables/core/useAppsEnabled'

const route  = useRoute()
const router = useRouter()
const { isGuest }      = usePermissions()
const { hasAssistant, hasCore } = useAppsEnabled()

const allAppRoutes = router.getRoutes()
  .filter(r => r.meta.appName)
  .map(r => ({
    name:        r.meta.appName as string,
    path:        r.path,
    icon:        r.meta.appIcon as string,
    requiresAuth: !!r.meta.requiresAuth,
    appMenu:     !!r.meta.appMenu,
    appProduct:  r.meta.appProduct as string | undefined,
  }))
  .sort((a, b) => a.path.localeCompare(b.path))

const productEnabled = (product?: string) => {
  if (!product) return true
  if (product === 'assistant') return hasAssistant.value
  if (product === 'core')      return hasCore.value
  return false
}

const apps = computed(() =>
  allAppRoutes.filter(r =>
    r.appMenu &&
    (!r.requiresAuth || !isGuest.value) &&
    productEnabled(r.appProduct)
  )
)

function isActive(app: typeof allAppRoutes[0]) {
  return route.path === app.path || route.path.startsWith(app.path + '/')
}

const activeApp = computed(() =>
  [...allAppRoutes].sort((a, b) => b.path.length - a.path.length).find(isActive) ?? allAppRoutes[0]
)

function navigate(path: string) {
  router.push(path)
}
</script>
