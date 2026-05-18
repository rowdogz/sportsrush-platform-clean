import type { AuthUser } from "../features/types";

const ADMIN_ROLES = new Set(["admin", "superadmin"]);

export function canAccessAdmin(user: AuthUser | null): boolean {
  return Boolean(user?.role && ADMIN_ROLES.has(user.role));
}

export function resolveAdminAppUrl(): string {
  // Temporary adapter until the public and admin apps share one router/shell.
  const configuredUrl = import.meta.env.VITE_ADMIN_APP_URL?.trim();
  if (configuredUrl && configuredUrl.length > 0) return configuredUrl;
  if (import.meta.env.MODE !== "test" && import.meta.env.DEV) {
    return "http://localhost:3001";
  }
  return "/admin";
}
