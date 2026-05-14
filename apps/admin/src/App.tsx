import { AdminLayout } from "./components/layout/AdminLayout";
import { ProtectedRoute } from "./components/auth/ProtectedRoute";
import { CompetitionsPage } from "./pages/CompetitionsPage";
import { ToastProvider } from "./components/primitives/Toast";

export function App() {
  return (
    <ToastProvider>
      <ProtectedRoute>
        <AdminLayout>
          <CompetitionsPage />
        </AdminLayout>
      </ProtectedRoute>
    </ToastProvider>
  );
}
