import type { ReactNode } from "react";
import { useAuthSession } from "../../contexts/AuthSessionProvider";

export type AdminScreen =
  | "competitions"
  | "seasons"
  | "teams"
  | "users"
  | "fixtures"
  | "aliases"
  | "rounds";

export type AdminNavItem = {
  readonly id: AdminScreen;
  readonly label: string;
};

type AdminLayoutProps = {
  readonly children: ReactNode;
  readonly activeScreen: AdminScreen;
  readonly navItems: readonly AdminNavItem[];
  readonly onNavigate: (screen: AdminScreen) => void;
};

export function AdminLayout({
  children,
  activeScreen,
  navItems,
  onNavigate,
}: AdminLayoutProps) {
  const { logout } = useAuthSession();

  return (
    <div className="admin-shell">
      <header className="admin-header">
        <h1>SportsRush Admin</h1>
        <button className="secondary-button" type="button" onClick={logout}>
          Log out
        </button>
      </header>
      <nav className="admin-nav" aria-label="Admin navigation">
        {navItems.map((item) => (
          <button
            key={item.id}
            className={
              item.id === activeScreen
                ? "nav-button nav-button-active"
                : "nav-button"
            }
            type="button"
            aria-current={item.id === activeScreen ? "page" : undefined}
            onClick={() => onNavigate(item.id)}
          >
            {item.label}
          </button>
        ))}
      </nav>
      <main className="admin-main">{children}</main>
    </div>
  );
}
