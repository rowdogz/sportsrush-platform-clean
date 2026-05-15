import type { ReactNode } from "react";
import { useAuthSession } from "../../contexts/AuthSessionProvider";
import { LoginPage } from "../../pages/LoginPage";

type ProtectedRouteProps = {
  readonly children: ReactNode;
};

export function ProtectedRoute({ children }: ProtectedRouteProps) {
  const { isAuthenticated } = useAuthSession();

  if (!isAuthenticated) {
    return <LoginPage />;
  }

  return <>{children}</>;
}
