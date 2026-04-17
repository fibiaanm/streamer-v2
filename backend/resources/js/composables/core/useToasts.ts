import { ref } from 'vue'
import { createSharedComposableById } from '@/composables/core/createSharedComposableById'

export interface ToastAction {
  label: string
  onClick: () => void
}

export interface Toast {
  id: string
  type: 'success' | 'error' | 'warning' | 'info'
  title: string
  message?: string
  duration?: number   // ms — undefined = no auto-dismiss
  actions?: ToastAction[]
}

export type ToastInput = Omit<Toast, 'id'>

function factory() {
  const toasts = ref<Toast[]>([
    // ── Dev dummies — remove before production ────────────────────────────
    {
      id: 'dummy-1',
      type: 'info',
      title: 'Sala activa',
      message: 'La sala "Design Review" tiene 4 participantes en este momento.',
    },
    {
      id: 'dummy-2',
      type: 'warning',
      title: 'Exportación pendiente',
      message: 'Tienes 3 imágenes esperando en la cola de exportación.',
      actions: [{ label: 'Ver cola', onClick: () => {} }],
    },
    {
      id: 'dummy-3',
      type: 'success',
      title: 'Cambios guardados',
      message: 'Los ajustes del workspace han sido actualizados correctamente.',
      duration: 12000,
    },
  ])

  const add = (input: ToastInput): string => {
    const id = `toast-${Date.now()}-${Math.random().toString(36).slice(2, 7)}`
    toasts.value.push({ ...input, id })
    return id
  }

  const remove = (id: string): void => {
    const idx = toasts.value.findIndex(t => t.id === id)
    if (idx !== -1) toasts.value.splice(idx, 1)
  }

  const clear = (): void => {
    toasts.value = []
  }

  return { toasts, add, remove, clear }
}

export const useToasts = () => createSharedComposableById('toasts', factory)
