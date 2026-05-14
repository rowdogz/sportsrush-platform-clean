import type { ReactNode } from "react";

type AdminLayoutProps = {
  readonly children: ReactNode;
};

export function AdminLayout({ children }: AdminLayoutProps) {
  return (
    <div>
      <header>
        <h1>SportsRush Admin</h1>
      </header>
      <main>{children}</main>
    </div>
  );
}
