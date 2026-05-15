import type { ReactNode } from "react";

type AdminLayoutProps = {
  readonly children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
  return (
    <div className="admin-shell">
      <header className="admin-header">
        <h1>SportsRush Admin</h1>
      </header>
      <main className="admin-main">{children}</main>
    </div>
  );
}
