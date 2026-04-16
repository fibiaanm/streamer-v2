import { onMounted, onUnmounted } from 'vue'
import { useSocket } from './useSocket'

type EventHandler = (...args: unknown[]) => void

// Registra un listener de socket scoped al ciclo de vida del componente.
// Se registra en onMounted y se limpia en onUnmounted automáticamente.
export const useScopeEffect = (event: string, handler: EventHandler) => {
  const { on, off } = useSocket()

  onMounted(() => on(event, handler))
  onUnmounted(() => off(event, handler))
}
