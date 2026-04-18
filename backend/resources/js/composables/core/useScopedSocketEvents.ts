import { onMounted, onUnmounted } from 'vue'
import { useSocket } from './useSocket'

export const useScopedSocketEvents = (
  handlers: Record<string, (data: unknown) => void>,
) => {
  const { on, off } = useSocket()
  onMounted(() => Object.entries(handlers).forEach(([e, h]) => on(e, h)))
  onUnmounted(() => Object.entries(handlers).forEach(([e, h]) => off(e, h)))
}
