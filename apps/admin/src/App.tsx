import { AdminLayout } from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { ToastProvider } from "./components/primitives/Toast";
import { AuthSessionProvider } from "./contexts/AuthSessionProvider";

export function App() {
  return (
    <ToastProvider>
      <AuthSessionProvider>
        <ProtectedRoute>
          <AdminLayout>
            <CompetitionsPage />
          </AdminLayout>
        </ProtectedRoute>
      </AuthSessionProvider>
    </ToastProvider>
  );
}
