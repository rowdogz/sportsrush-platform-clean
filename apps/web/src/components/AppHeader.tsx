import { resolveAdminAppUrl } from "../lib/adminAccess";
import type { AuthUser } from "../features/types";
import { RequireAdminAccess } from "./RequireAdminAccess";
import { ThemeToggle } from "./ThemeToggle";

type NavItem<TScreen extends string> = {
  readonly screen: TScreen;
  readonly label: string;
};

function userInitials(user: AuthUser | null): string {
  if (!user?.email) return "SR";
  return user.email.slice(0, 2).toUpperCase();
}

export function AppHeader<TScreen extends string>({
  currentScreen,
  navItems,
  onNavigate,
  isAuthenticated,
  user,
  onLogin,
  onRegister,
  onLogout,
}: {
  readonly currentScreen: TScreen;
  readonly navItems: readonly NavItem<TScreen>[];
  readonly onNavigate: (screen: TScreen) => void;
  readonly isAuthenticated: boolean;
  readonly user: AuthUser | null;
  readonly onLogin: () => void;
  readonly onRegister: () => void;
  readonly onLogout: () => void;
}) {
  const adminHref = resolveAdminAppUrl();

  return (
    <header className="site-header">
      <div className="site-header-row">
        <button
          aria-label="SportsRush home"
          className="brand"
          type="button"
          onClick={() => onNavigate(navItems[0]?.screen ?? currentScreen)}
        >
          <img
            alt="SportsRush"
            className="brand-logo brand-logo-light"
            src="/sportsrush-logo-light.png"
          />
          <img
            alt="SportsRush"
            className="brand-logo brand-logo-dark"
            src="/sportsrush-logo-dark.png"
          />
        </button>

        <nav
          aria-label="Primary navigation"
          className="site-nav site-nav-desktop"
        >
          {navItems.map((item) => (
            <button
              key={item.screen}
              aria-current={currentScreen === item.screen ? "page" : undefined}
              className="site-nav-item"
              type="button"
              onClick={() => onNavigate(item.screen)}
            >
              {item.label}
            </button>
          ))}
          <RequireAdminAccess>
            <a className="admin-chip" href={adminHref}>
              Admin
            </a>
          </RequireAdminAccess>
        </nav>

        <div className="header-actions">
          <ThemeToggle />
          {isAuthenticated ? (
            <div className="account-menu-placeholder">
              <div className="avatar-badge" aria-hidden="true">
                {userInitials(user)}
              </div>
              <div className="account-copy">
                <strong>{user?.email ?? "Signed in"}</strong>
                <span>Profile and session controls available</span>
              </div>
              <button
                className="button secondary compact"
                type="button"
                onClick={() => onNavigate("profile" as TScreen)}
              >
                Profile
              </button>
              <button
                className="button secondary"
                type="button"
                onClick={onLogout}
              >
                Logout
              </button>
            </div>
          ) : (
            <div className="auth-actions">
              <button
                className="button secondary"
                type="button"
                onClick={onLogin}
              >
                Login
              </button>
              <button className="button" type="button" onClick={onRegister}>
                Register
              </button>
            </div>
          )}
        </div>
      </div>

      <nav aria-label="Mobile navigation" className="site-nav site-nav-mobile">
        {navItems.map((item) => (
          <button
            key={item.screen}
            aria-current={currentScreen === item.screen ? "page" : undefined}
            className="site-nav-item"
            type="button"
            onClick={() => onNavigate(item.screen)}
          >
            {item.label}
          </button>
        ))}
        <RequireAdminAccess>
          <a className="admin-chip" href={adminHref}>
            Admin
          </a>
        </RequireAdminAccess>
      </nav>
    </header>
  );
}
