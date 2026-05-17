import type { UserRole } from "../features/users/types";

const roleRank: Record<UserRole, number> = {
  user: 0,
  admin: 1,
  superadmin: 2,
};

type DecodedAccessToken = {
  readonly role: UserRole | null;
  readonly expiresAt: number | null;
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

function decodeAccessToken(token: string | null): DecodedAccessToken {
  if (!token) return { role: null, expiresAt: null };

  const [, payload] = token.split(".");
  if (!payload) return { role: null, expiresAt: null };

  try {
    const parsed = JSON.parse(decodeBase64Url(payload)) as {
      readonly role?: unknown;
      readonly exp?: unknown;
    };
    return {
      role: isUserRole(parsed.role) ? parsed.role : null,
      expiresAt: typeof parsed.exp === "number" ? parsed.exp : null,
    };
  } catch {
    return { role: null, expiresAt: null };
  }
}

export function getRoleFromAccessToken(token: string | null): UserRole | null {
  return decodeAccessToken(token).role;
}

export function isAccessTokenExpired(
  token: string | null,
  nowInSeconds = Math.floor(Date.now() / 1000),
): boolean {
  const { expiresAt } = decodeAccessToken(token);
  return expiresAt !== null && expiresAt <= nowInSeconds;
}

export function isAccessTokenUsable(token: string | null): boolean {
  return Boolean(token) && !isAccessTokenExpired(token);
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
