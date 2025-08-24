export const TOOLTIP = {
  onlyRootManageRootUser: 'Only root can manage root users',
  onlyRootManageRootRole: 'Only root can manage the root role',
  cannotDeleteSelf: 'You cannot delete yourself',
  insufficientPermissions: 'Insufficient permissions',
} as const;

export type TooltipKey = keyof typeof TOOLTIP;
