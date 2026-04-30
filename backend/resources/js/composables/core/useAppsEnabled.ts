import { computed } from 'vue'
import { useSession } from './useSession'

export const useAppsEnabled = () => {
  const { user } = useSession()

  const products = computed(() => user.value?.enterprise?.products ?? {})

  const hasAssistant = computed(() => 'assistant' in products.value)
  const hasCore      = computed(() => 'core' in products.value)

  return { hasAssistant, hasCore }
}
