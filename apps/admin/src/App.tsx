import { useState } from "react";
import { AdminLayout } from "./components/layout/AdminLayout";
import type {
  AdminNavItem,
  AdminScreen,
} from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { FixturesPage } from "./pages/FixturesPage";
import { RoundsPage } from "./pages/RoundsPage";
import { SeasonsPage } from "./pages/SeasonsPage";
import { TeamAliasesPage } from "./pages/TeamAliasesPage";
import { TeamsPage } from "./pages/TeamsPage";
import { UsersPage } from "./pages/UsersPage";
import { AuditLogPage } from "./pages/AuditLogPage";
import { ToastProvider } from "./components/primitives/Toast";
import { AuthSessionProvider } from "./contexts/AuthSessionProvider";

const adminNavItems: readonly AdminNavItem[] = [
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
  audit: "/audit",
};

function getInitialScreen(): AdminScreen {
  const pathname = window.location.pathname;
  const matchingEntry = Object.entries(screenPaths).find(
    ([, path]) => path === pathname,
  );
  return (matchingEntry?.[0] as AdminScreen | undefined) ?? "competitions";
}

function renderScreen(screen: AdminScreen) {
  switch (screen) {
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

export function App() {
  const [activeScreen, setActiveScreen] =
    useState<AdminScreen>(getInitialScreen);

  return (
    <ToastProvider>
      <AuthSessionProvider>
        <ProtectedRoute>
          <AdminLayout
            activeScreen={activeScreen}
            navItems={adminNavItems}
            onNavigate={setActiveScreen}
          >
            {renderScreen(activeScreen)}
          </AdminLayout>
        </ProtectedRoute>
      </AuthSessionProvider>
    </ToastProvider>
  );
}
