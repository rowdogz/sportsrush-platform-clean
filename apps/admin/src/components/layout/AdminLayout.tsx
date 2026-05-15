import type { ReactNode } from "react";
import { useAuthSession } from "../../contexts/AuthSessionProvider";

type AdminLayoutProps = {
  readonly children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
  const { logout } = useAuthSession();

  return (
    <div className="admin-shell">
      <header className="admin-header">
        <h1>SportsRush Admin</h1>
        <button className="secondary-button" type="button" onClick={logout}>
          Log out
        </button>
      </header>
      <main className="admin-main">{children}</main>
    </div>
  );
}
