// Simple client-side helpers to reflect backend permissions model.
// Note: Backend authorization is the source of truth. These helpers are for UI only.

export type AuthInfo = {
  roles: string[]
  isAdmin: boolean
  isRoot: boolean
}

const toLowerSet = (arr: string[]) => new Set(arr.map((r) => r.toLowerCase()))

export function makeAuthHelpers(auth: AuthInfo) {
  const roles = toLowerSet(auth.roles || [])
  const isRoot = auth.isRoot || roles.has('root')
  const isAdmin = auth.isAdmin || roles.has('admin') || isRoot

  // Map UI abilities to our permission policy
  const canViewUsers = () => isAdmin || isRoot
  const canManageUsers = () => isAdmin || isRoot

  const canViewRoles = () => isAdmin || isRoot
  // Only root can manage the root role; but for general UI, show role management
  // to admin and root. Backend will still block root-only actions.
  const canManageRoles = () => isAdmin || isRoot

  const canViewSettings = () => isAdmin || isRoot
  const canManageSettings = () => isAdmin || isRoot

  return {
    isRoot,
    isAdmin,
    canViewUsers,
    canManageUsers,
    canViewRoles,
    canManageRoles,
    canViewSettings,
    canManageSettings,
  }
}
