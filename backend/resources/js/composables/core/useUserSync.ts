import { watch } from 'vue'
import { useSocket } from './useSocket'
import { useSession } from './useSession'
import { useToasts } from './useToasts'
import type { MemberKickedPayload, RoleSocketPayload } from '@/types'

export const useUserSync = () => {
  const { on, connected }      = useSocket()
  const { user, setUser }      = useSession()
  const { add: addToast }      = useToasts()

  let registered = false

  watch(connected, (isConnected) => {
    if (!isConnected || registered) return

    on('member.kicked', (data) => {
      const { enterpriseId, enterpriseName } = data as MemberKickedPayload
      const isCurrentEnterprise = user.value?.enterprise.id === enterpriseId

      addToast({
        type:     'warning',
        title:    `Te han removido de ${enterpriseName}`,
        duration: 8000,
        onRemove: () => { if (isCurrentEnterprise) window.location.reload() },
      })
    })

    on('role.updated', (data) => {
      const { name, permissions } = data as RoleSocketPayload
      if (!user.value) return

      setUser({
        ...user.value,
        enterprise: { ...user.value.enterprise, role: name, permissions },
      })

      addToast({ type: 'info', title: 'Los permisos de tu rol han sido actualizados', duration: 5000 })
    })

    registered = true
  }, { immediate: true })
}
