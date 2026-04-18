import { watch } from 'vue'
import { useSession } from './useSession'
import { useSocket } from './useSocket'
import type { EnterpriseUpdatedPayload } from '@/types'

export const useEnterpriseSync = () => {
  const { user }                    = useSession()
  const { emit, on, connected }     = useSocket()

  let listenerRegistered = false

  function onEnterpriseUpdated(data: unknown) {
    const { name } = data as EnterpriseUpdatedPayload
    if (user.value) user.value.enterprise.name = name
  }

  // Cuando el socket conecta (o reconecta): re-unirse al room y registrar listeners
  watch(connected, (isConnected) => {
    if (!isConnected) return
    const id = user.value?.enterprise.id
    if (id) emit('join_enterprise', { enterpriseId: id })

    if (!listenerRegistered) {
      on('enterprise.updated', onEnterpriseUpdated)
      listenerRegistered = true
    }
  }, { immediate: true })

  // Si cambia la empresa activa (switch), salir del room anterior y entrar al nuevo
  watch(() => user.value?.enterprise.id, (newId, oldId) => {
    if (!connected.value || !newId) return
    if (oldId) emit('leave_enterprise', { enterpriseId: oldId })
    emit('join_enterprise', { enterpriseId: newId })
  })
}
