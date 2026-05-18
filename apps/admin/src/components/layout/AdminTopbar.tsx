import { AdminBreadcrumbs } from "./AdminBreadcrumbs";

export function AdminTopbar({
  currentLabel,
  onToggleNavigation,
  onLogout,
  userRole,
}: {
  readonly currentLabel: string;
  readonly onToggleNavigation: () => void;
  readonly onLogout: () => void;
  readonly userRole: string | null;
}) {
  return (
    <header className="admin-topbar">
      <div className="admin-topbar-primary">
        <button
          aria-label="Toggle admin navigation"
          className="secondary-button compact-button admin-menu-toggle"
          type="button"
          onClick={onToggleNavigation}
        >
          Menu
        </button>
        <div className="admin-topbar-copy">
          <AdminBreadcrumbs currentLabel={currentLabel} />
          <h1>SportsRush Admin</h1>
        </div>
      </div>

      <div className="admin-topbar-actions">
        <span className="admin-role-badge">{userRole ?? "admin"}</span>
        <button className="secondary-button" type="button" onClick={onLogout}>
          Log out
        </button>
      </div>
    </header>
  );
}
