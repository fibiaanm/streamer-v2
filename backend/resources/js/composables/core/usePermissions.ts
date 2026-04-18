import { computed } from 'vue'
import { useSession } from './useSession'

export const usePermissions = () => {
  const { user } = useSession()

  const enterprise  = computed(() => user.value?.enterprise ?? null)
  const permissions = computed(() => enterprise.value?.permissions ?? [])
  const limits      = computed(() => enterprise.value?.plan?.limits ?? null)

  const can = (p: string) => permissions.value.includes(p)

  const isGuest      = computed(() => user.value?.enterprise?.role === 'guest')
  const isEnterprise = computed(() => enterprise.value?.type === 'enterprise')
  const haveATeam    = computed(() => (limits.value?.members.max ?? 0) > 1)
  const membersMax   = computed(() => limits.value?.members.max ?? null)

  const canViewSettings  = computed(() => isEnterprise.value && can('enterprise.settings.view'))
  const canEditSettings  = computed(() => can('enterprise.settings.edit'))
  const canViewMembers   = computed(() => can('enterprise.members.view') && haveATeam.value)
  const canInviteMembers = computed(() => can('enterprise.members.invite'))
  const canRemoveMembers = computed(() => can('enterprise.members.remove'))
  const canAddRoles      = computed(() => can('enterprise.roles.add') && haveATeam.value)
  const canEditRoles     = computed(() => can('enterprise.roles.edit') && haveATeam.value)
  const canRemoveRoles   = computed(() => can('enterprise.roles.remove') && haveATeam.value)
  const canAssignRoles   = computed(() => can('enterprise.roles.assign') && haveATeam.value)
  const canManageRoles   = computed(() => (canAddRoles.value || canEditRoles.value || canRemoveRoles.value) && haveATeam.value)
  const canViewBilling   = computed(() => can('enterprise.billing.view'))

  return {
    permissions,
    isGuest,
    isEnterprise,
    haveATeam,
    membersMax,
    canViewSettings,
    canEditSettings,
    canViewMembers,
    canInviteMembers,
    canRemoveMembers,
    canAddRoles,
    canEditRoles,
    canRemoveRoles,
    canAssignRoles,
    canManageRoles,
    canViewBilling,
  }
}
