import { computed } from 'vue'
import { useSession } from './useSession'

export const usePermissions = () => {
  const { user } = useSession()

  const enterprise  = computed(() => user.value?.enterprise ?? null)
  const permissions = computed(() => enterprise.value?.permissions ?? [])
  const limits      = computed(() => enterprise.value?.plan?.limits ?? null)

  const can = (p: string) => permissions.value.includes(p)

  const isEnterprise = computed(() => enterprise.value?.type === 'enterprise')
  const haveATeam    = computed(() => (limits.value?.members.max ?? 0) > 1)
  const membersMax   = computed(() => limits.value?.members.max ?? null)

  const canViewSettings  = computed(() => isEnterprise.value && can('enterprise.settings.view'))
  const canEditSettings  = computed(() => can('enterprise.settings.edit'))
  const canViewMembers   = computed(() => can('enterprise.members.view') && haveATeam.value)
  const canInviteMembers = computed(() => can('enterprise.members.invite'))
  const canRemoveMembers = computed(() => can('enterprise.members.remove'))
  const canManageRoles   = computed(() => can('enterprise.roles.manage') && haveATeam.value)
  const canViewBilling   = computed(() => can('enterprise.billing.view'))

  return {
    isEnterprise,
    haveATeam,
    membersMax,
    canViewSettings,
    canEditSettings,
    canViewMembers,
    canInviteMembers,
    canRemoveMembers,
    canManageRoles,
    canViewBilling,
  }
}
