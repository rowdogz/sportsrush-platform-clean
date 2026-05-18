import { useMemo, useState, type ReactNode } from "react";
import { useAuthSession } from "../../contexts/AuthSessionProvider";
import { AdminSidebar } from "./AdminSidebar";
import { AdminTopbar } from "./AdminTopbar";

export type AdminScreen =
  | "dashboard"
  | "competitions"
  | "seasons"
  | "teams"
  | "private-leagues"
  | "users"
  | "audit"
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
  const { logout, userRole } = useAuthSession();
  const [navigationOpen, setNavigationOpen] = useState(false);
  const activeLabel = useMemo(
    () =>
      navItems.find((item) => item.id === activeScreen)?.label ??
      "Admin screen",
    [activeScreen, navItems],
  );

  return (
    <div className="admin-shell">
      <div
        className={
          navigationOpen
            ? "admin-shell-grid admin-shell-grid-open"
            : "admin-shell-grid"
        }
      >
        <div
          className="admin-nav-backdrop"
          aria-hidden="true"
          onClick={() => setNavigationOpen(false)}
        />
        <div className="admin-nav-rail">
          <AdminSidebar
            activeScreen={activeScreen}
            navItems={navItems}
            onNavigate={onNavigate}
            onClose={() => setNavigationOpen(false)}
          />
        </div>

        <div className="admin-content">
          <AdminTopbar
            currentLabel={activeLabel}
            onLogout={logout}
            onToggleNavigation={() => setNavigationOpen((open) => !open)}
            userRole={userRole}
          />
          <main className="admin-main">{children}</main>
        </div>
      </div>
    </div>
  );
}
