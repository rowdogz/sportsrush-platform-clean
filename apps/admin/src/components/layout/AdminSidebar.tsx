import type { AdminNavItem, AdminScreen } from "./AdminLayout";

export function AdminSidebar({
  activeScreen,
  navItems,
  onNavigate,
  onClose,
}: {
  readonly activeScreen: AdminScreen;
  readonly navItems: readonly AdminNavItem[];
  readonly onNavigate: (screen: AdminScreen) => void;
  readonly onClose?: () => void;
}) {
  return (
    <aside className="admin-sidebar">
      <div className="admin-sidebar-brand">
        <span className="admin-sidebar-eyebrow">SportsRush</span>
        <strong>Admin Console</strong>
        <p>Operations, data management, and audit tooling.</p>
      </div>

      <nav className="admin-sidebar-nav" aria-label="Admin navigation">
        {navItems.map((item) => (
          <button
            key={item.id}
            className={
              item.id === activeScreen
                ? "admin-sidebar-link admin-sidebar-link-active"
                : "admin-sidebar-link"
            }
            type="button"
            aria-current={item.id === activeScreen ? "page" : undefined}
            onClick={() => {
              onNavigate(item.id);
              onClose?.();
            }}
          >
            <span>{item.label}</span>
          </button>
        ))}
      </nav>

      <div className="admin-sidebar-footer">
        <span>Shared admin shell foundation</span>
      </div>
    </aside>
  );
}
