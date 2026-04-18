import { ref } from 'vue'
import { useRolesApi } from '@/composables/api/useRolesApi'
import type { Role } from '@/composables/api/useRolesApi'

export const useRolesState = () => {
  const { listRoles, listPermissions } = useRolesApi()

  const roles                = ref<Role[]>([])
  const availablePermissions = ref<string[]>([])

  const loadData = async () => {
    const [rolesRes, permsRes] = await Promise.all([listRoles(), listPermissions()])
    roles.value                = rolesRes.data.data
    availablePermissions.value = permsRes.data.data
  }

  return { roles, availablePermissions, loadData }
}
