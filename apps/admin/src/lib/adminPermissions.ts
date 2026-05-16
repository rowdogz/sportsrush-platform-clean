import type { UserRole } from "../features/users/types";

const roleRank: Record<UserRole, number> = {
  user: 0,
  admin: 1,
  superadmin: 2,
};

export function isUserRole(value: unknown): value is UserRole {
  return value === "user" || value === "admin" || value === "superadmin";
}

function decodeBase64Url(value: string): string {
  const normalized = value.replace(/-/g, "+").replace(/_/g, "/");
  const padded = normalized.padEnd(
    normalized.length + ((4 - (normalized.length % 4)) % 4),
    "=",
  );
  return window.atob(padded);
}

export function getRoleFromAccessToken(token: string | null): UserRole | null {
  if (!token) return null;

  const [, payload] = token.split(".");
  if (!payload) return null;

  try {
    const parsed = JSON.parse(decodeBase64Url(payload)) as { role?: unknown };
    return isUserRole(parsed.role) ? parsed.role : null;
  } catch {
    return null;
  }
}

export function hasMinimumAdminRole(
  role: UserRole | null,
  minimumRole: UserRole,
): boolean {
  if (!role) return false;
  return roleRank[role] >= roleRank[minimumRole];
}

export function canManageAdminUsers(role: UserRole | null): boolean {
  return hasMinimumAdminRole(role, "superadmin");
}

export function canViewAuditLog(role: UserRole | null): boolean {
  return hasMinimumAdminRole(role, "superadmin");
}
