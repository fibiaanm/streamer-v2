import { computed } from 'vue'
import { useSession } from '@/composables/core/useSession'
import type { AssistantLimits } from '@/types'

export const useAssistantCapabilities = () => {
  const { user } = useSession()

  const limits = computed(() =>
    (user.value?.enterprise?.products?.assistant?.limits ?? null) as AssistantLimits | null
  )

  const planName   = computed(() => user.value?.enterprise?.products?.assistant?.plan ?? 'Free')
  const isPro      = computed(() => planName.value === 'Pro')
  const isPremium  = computed(() => planName.value === 'Premium')
  const isPaid     = computed(() => isPro.value || isPremium.value)

  const lim = (key: keyof AssistantLimits) => {
    const l = limits.value?.[key]
    if (!l) return 0
    return 'max' in l ? l.max : 0
  }
  const flag = (key: keyof AssistantLimits) => {
    const l = limits.value?.[key]
    if (!l) return false
    return 'value' in l ? l.value : false
  }

  const messagesDaily       = computed(() => lim('messages_daily'))
  const memoryTotal         = computed(() => lim('memory_total'))
  const memoryCategories    = computed(() => lim('memory_categories'))
  const friendsMax          = computed(() => lim('friends'))
  const friendCategories    = computed(() => lim('friend_categories'))
  const listsMax            = computed(() => lim('lists'))
  const listItemsMax        = computed(() => lim('list_items_max'))
  const eventsMonthly       = computed(() => lim('events_monthly'))
  const remindersActive     = computed(() => lim('reminders_active'))
  const contextMessages     = computed(() => lim('context_messages'))
  const storageMb           = computed(() => lim('storage_mb'))
  const uploadMonthlyMb     = computed(() => lim('upload_monthly_mb'))
  const uploadMaxMb         = computed(() => lim('upload_max_mb'))

  const canUploadFiles        = computed(() => uploadMaxMb.value > 0)
  const canUseExpenses        = computed(() => flag('expenses_enabled'))
  const canUseCustomInstructions = computed(() => flag('custom_instructions'))
  const canExport             = computed(() => flag('export_enabled'))

  return {
    planName,
    isPro,
    isPremium,
    isPaid,
    messagesDaily,
    memoryTotal,
    memoryCategories,
    friendsMax,
    friendCategories,
    listsMax,
    listItemsMax,
    eventsMonthly,
    remindersActive,
    contextMessages,
    storageMb,
    uploadMonthlyMb,
    uploadMaxMb,
    canUploadFiles,
    canUseExpenses,
    canUseCustomInstructions,
    canExport,
  }
}
