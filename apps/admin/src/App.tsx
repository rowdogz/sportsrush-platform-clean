import { AuthProvider } from "./auth/AuthContext";
import { AdminLayout } from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { ToastProvider } from "./components/primitives/Toast";

export function App() {
  return (
    <ToastProvider>
      <AuthProvider>
        <ProtectedRoute>
          <AdminLayout>
            <CompetitionsPage />
          </AdminLayout>
        </ProtectedRoute>
      </AuthProvider>
    </ToastProvider>
  );
}
