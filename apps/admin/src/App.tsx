import { useState } from "react";
import { AdminLayout } from "./components/layout/AdminLayout";
import type {
  AdminNavItem,
  AdminScreen,
} from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { PlaceholderPage } from "./pages/PlaceholderPage";
import { ToastProvider } from "./components/primitives/Toast";
import { AuthSessionProvider } from "./contexts/AuthSessionProvider";

const adminNavItems: readonly AdminNavItem[] = [
  { id: "competitions", label: "Competitions" },
  { id: "teams", label: "Teams" },
  { id: "fixtures", label: "Fixtures" },
  { id: "aliases", label: "Aliases" },
  { id: "rounds", label: "Rounds" },
];

function renderScreen(screen: AdminScreen) {
  switch (screen) {
    case "competitions":
      return <CompetitionsPage />;
    case "teams":
      return <PlaceholderPage title="Teams" />;
    case "fixtures":
      return <PlaceholderPage title="Fixtures" />;
    case "aliases":
      return <PlaceholderPage title="Aliases" />;
    case "rounds":
      return <PlaceholderPage title="Rounds" />;
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
