import { useState } from "react";
import { AdminLayout } from "./components/layout/AdminLayout";
import type {
  AdminNavItem,
  AdminScreen,
} from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { DashboardPage } from "./pages/DashboardPage";
import { FixturesPage } from "./pages/FixturesPage";
import { RoundsPage } from "./pages/RoundsPage";
import { SeasonsPage } from "./pages/SeasonsPage";
import { TeamAliasesPage } from "./pages/TeamAliasesPage";
import { TeamsPage } from "./pages/TeamsPage";
import { UsersPage } from "./pages/UsersPage";
import { AuditLogPage } from "./pages/AuditLogPage";
import { ToastProvider } from "./components/primitives/Toast";
import { AuthSessionProvider } from "./contexts/AuthSessionProvider";
import { useAuthSession } from "./contexts/AuthSessionProvider";
import { canViewAuditLog } from "./lib/adminPermissions";
import { AdminTableError } from "./components/admin/AdminTableState";

const adminNavItems: readonly AdminNavItem[] = [
  { id: "dashboard", label: "Dashboard" },
  { id: "competitions", label: "Competitions" },
  { id: "seasons", label: "Seasons" },
  { id: "teams", label: "Teams" },
  { id: "users", label: "Users" },
  { id: "audit", label: "Audit Log" },
  { id: "fixtures", label: "Fixtures" },
  { id: "aliases", label: "Aliases" },
  { id: "rounds", label: "Rounds" },
];

const screenPaths: Partial<Record<AdminScreen, string>> = {
  dashboard: "/",
  audit: "/audit",
};

function getInitialScreen(): AdminScreen {
  const pathname = window.location.pathname;
  const matchingEntry = Object.entries(screenPaths).find(
    ([, path]) => path === pathname,
  );
  return (matchingEntry?.[0] as AdminScreen | undefined) ?? "dashboard";
}

function renderForbiddenScreen() {
  return (
    <AdminTableError
      title="Forbidden"
      message="Your admin role is not permitted to view this screen."
    />
  );
}

function renderScreen(screen: AdminScreen) {
  switch (screen) {
    case "dashboard":
      return <DashboardPage />;
    case "competitions":
      return <CompetitionsPage />;
    case "seasons":
      return <SeasonsPage />;
    case "teams":
      return <TeamsPage />;
    case "users":
      return <UsersPage />;
    case "audit":
      return <AuditLogPage />;
    case "fixtures":
      return <FixturesPage />;
    case "aliases":
      return <TeamAliasesPage />;
    case "rounds":
      return <RoundsPage />;
  }
}

function AdminAppShell() {
  const { userRole } = useAuthSession();
  const [activeScreen, setActiveScreen] =
    useState<AdminScreen>(getInitialScreen);
  const navItems = adminNavItems.filter(
    (item) => item.id !== "audit" || canViewAuditLog(userRole),
  );
  const activeScreenIsAllowed =
    activeScreen !== "audit" || canViewAuditLog(userRole);

  return (
    <AdminLayout
      activeScreen={activeScreen}
      navItems={navItems}
      onNavigate={setActiveScreen}
    >
      {activeScreenIsAllowed
        ? renderScreen(activeScreen)
        : renderForbiddenScreen()}
    </AdminLayout>
  );
}

export function App() {
  return (
    <ToastProvider>
      <AuthSessionProvider>
        <ProtectedRoute>
          <AdminAppShell />
        </ProtectedRoute>
      </AuthSessionProvider>
    </ToastProvider>
  );
}
