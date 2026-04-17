import { defineStore } from 'pinia'
import { ref, computed } from 'vue'
import { useSession } from '@/composables/core/useSession'

const ENTERPRISE_KEY = 'active_enterprise_id'

export const useEnterpriseStore = defineStore('enterprise', () => {
  const { user } = useSession()

  const selectedId = ref<string | null>(localStorage.getItem(ENTERPRISE_KEY))

  const activeEnterprise   = computed(() => user.value?.enterprise ?? null)
  const activeEnterpriseId = computed(() => selectedId.value)
  const activeRole         = computed(() => user.value?.enterprise?.role ?? null)
  const activePermissions  = computed(() => user.value?.enterprise?.permissions ?? [])

  const set = (id: string) => {
    selectedId.value = id
    localStorage.setItem(ENTERPRISE_KEY, id)
  }

  const clear = () => {
    selectedId.value = null
    localStorage.removeItem(ENTERPRISE_KEY)
  }

  const can = (permission: string) => activePermissions.value.includes(permission)

  return { activeEnterprise, activeEnterpriseId, activeRole, activePermissions, set, clear, can }
})
