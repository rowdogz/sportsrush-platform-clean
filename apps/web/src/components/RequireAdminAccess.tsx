import type { ReactNode } from "react";
import { useAuth } from "../contexts/AuthContext";
import { canAccessAdmin } from "../lib/adminAccess";

export function RequireAdminAccess({
  children,
  fallback = null,
}: {
  readonly children: ReactNode;
  readonly fallback?: ReactNode;
}) {
  const { user } = useAuth();
  return canAccessAdmin(user) ? <>{children}</> : <>{fallback}</>;
}
