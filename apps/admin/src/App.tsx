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
import { ToastProvider } from "./components/primitives/Toast";
import { AuthSessionProvider } from "./contexts/AuthSessionProvider";

const adminNavItems: readonly AdminNavItem[] = [
  { id: "competitions", label: "Competitions" },
  { id: "seasons", label: "Seasons" },
  { id: "teams", label: "Teams" },
  { id: "fixtures", label: "Fixtures" },
  { id: "aliases", label: "Aliases" },
  { id: "rounds", label: "Rounds" },
];

function renderScreen(screen: AdminScreen) {
  switch (screen) {
    case "competitions":
      return <CompetitionsPage />;
    case "seasons":
      return <SeasonsPage />;
    case "teams":
      return <TeamsPage />;
    case "fixtures":
      return <FixturesPage />;
    case "aliases":
      return <TeamAliasesPage />;
    case "rounds":
      return <RoundsPage />;
  }
}

export function App() {
  const [activeScreen, setActiveScreen] = useState<AdminScreen>("competitions");

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
