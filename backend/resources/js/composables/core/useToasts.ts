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
  duration?: number      // ms — undefined = no auto-dismiss
  actions?: ToastAction[]
  onTimeout?: () => void // se ejecuta si el timer llega a cero; no corre si el usuario cancela
}

export type ToastInput = Omit<Toast, 'id'>

function factory() {
  const toasts = ref<Toast[]>([])

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
