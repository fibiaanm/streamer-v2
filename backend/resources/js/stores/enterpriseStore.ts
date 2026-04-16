import { defineStore } from 'pinia'
import { computed } from 'vue'
import { useSession } from '@/composables/core/useSession'

export const useEnterpriseStore = defineStore('enterprise', () => {
  const { user } = useSession()

  const activeEnterprise    = computed(() => user.value?.enterprise ?? null)
  const activeEnterpriseId  = computed(() => user.value?.enterprise?.id ?? null)
  const activeRole          = computed(() => user.value?.enterprise?.role ?? null)
  const activePermissions   = computed(() => user.value?.enterprise?.permissions ?? [])

  const can = (permission: string) => activePermissions.value.includes(permission)

  return {
    activeEnterprise,
    activeEnterpriseId,
    activeRole,
    activePermissions,
    can,
  }
})
