import type { Role, TokenPayload } from "@sr/types";

/**
 * Numeric hierarchy for roles — higher number = more permissions.
 * Roles form a strict linear hierarchy: user < admin < superadmin.
 */
const ROLE_RANK: Record<Role, number> = {
  user: 0,
  admin: 1,
  superadmin: 2,
};

/**
 * Returns true if the token holder has at least the required role level.
 *
 * @example
 * hasRole(payload, 'admin')  // true for 'admin' and 'superadmin'
 * hasRole(payload, 'user')   // true for all roles
 */
export function hasRole(payload: TokenPayload, requiredRole: Role): boolean {
  return (ROLE_RANK[payload.role] ?? -1) >= (ROLE_RANK[requiredRole] ?? 0);
}

/**
 * Asserts that the token holder has at least the required role.
 * Throws a plain Error (not an HTTP response) — the caller wraps it in a 403.
 *
 * Use this in service functions, not in middleware (middleware should use hasRole).
 */
export function assertRole(payload: TokenPayload, requiredRole: Role): void {
  if (!hasRole(payload, requiredRole)) {
    throw new RoleError(requiredRole, payload.role);
  }
}

/**
 * Thrown by assertRole when the token holder lacks the required role.
 */
export class RoleError extends Error {
  override readonly name = "RoleError";
  readonly requiredRole: Role;
  readonly actualRole: Role;

  constructor(requiredRole: Role, actualRole: Role) {
    super(
      `Insufficient permissions. Required role: '${requiredRole}', actual role: '${actualRole}'`,
    );
    this.requiredRole = requiredRole;
    this.actualRole = actualRole;
  }
}

/**
 * Returns the human-readable display label for a role.
 */
export function formatRole(role: Role): string {
  const labels: Record<Role, string> = {
    user: "User",
    admin: "Admin",
    superadmin: "Super Admin",
  };
  return labels[role];
}
