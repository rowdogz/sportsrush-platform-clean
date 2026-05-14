import type { ReactNode } from "react";

type ProtectedRouteProps = {
  readonly children: ReactNode;
};

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  return <>{children}</>;
}
