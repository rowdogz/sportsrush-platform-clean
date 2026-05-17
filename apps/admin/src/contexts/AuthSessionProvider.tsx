import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import { loginAdmin } from "../features/auth/api";
import type { AdminLoginRequest } from "../features/auth/types";
import {
  setAccessTokenProvider,
  setUnauthorizedHandler,
} from "../lib/apiClient";
import {
  getRoleFromAccessToken,
  isAccessTokenUsable,
} from "../lib/adminPermissions";
import type { UserRole } from "../features/users/types";

const ACCESS_TOKEN_STORAGE_KEY = "sr_admin_access_token";
const REFRESH_TOKEN_STORAGE_KEY = "sr_admin_refresh_token";

type AuthSession = {
  readonly accessToken: string | null;
  readonly refreshToken: string | null;
  readonly role: UserRole | null;
};

type AuthSessionContextValue = {
  readonly isAuthenticated: boolean;
  readonly accessToken: string | null;
  readonly userRole: UserRole | null;
  readonly login: (request: AdminLoginRequest) => Promise<void>;
  readonly logout: () => void;
};

const AuthSessionContext = createContext<AuthSessionContextValue | undefined>(
  undefined,
);

function getStoredSession(): AuthSession {
  if (typeof window === "undefined") {
    return { accessToken: null, refreshToken: null, role: null };
  }

  const accessToken = window.localStorage.getItem(ACCESS_TOKEN_STORAGE_KEY);
  if (!isAccessTokenUsable(accessToken)) {
    window.localStorage.removeItem(ACCESS_TOKEN_STORAGE_KEY);
    window.localStorage.removeItem(REFRESH_TOKEN_STORAGE_KEY);
    return { accessToken: null, refreshToken: null, role: null };
  }

  return {
    accessToken,
    refreshToken: window.localStorage.getItem(REFRESH_TOKEN_STORAGE_KEY),
    role: getRoleFromAccessToken(accessToken),
  };
}

function persistSession(session: AuthSession): void {
  if (typeof window === "undefined") return;

  if (session.accessToken) {
    window.localStorage.setItem(ACCESS_TOKEN_STORAGE_KEY, session.accessToken);
  } else {
    window.localStorage.removeItem(ACCESS_TOKEN_STORAGE_KEY);
  }

  if (session.refreshToken) {
    window.localStorage.setItem(
      REFRESH_TOKEN_STORAGE_KEY,
      session.refreshToken,
    );
  } else {
    window.localStorage.removeItem(REFRESH_TOKEN_STORAGE_KEY);
  }
}

type AuthSessionProviderProps = {
  readonly children: ReactNode;
};

export function AuthSessionProvider({ children }: AuthSessionProviderProps) {
  const [session, setSession] = useState<AuthSession>(getStoredSession);

  const clearSession = useCallback(() => {
    const nextSession = { accessToken: null, refreshToken: null, role: null };
    persistSession(nextSession);
    setSession(nextSession);
  }, []);

  useEffect(() => {
    setAccessTokenProvider(() => session.accessToken);
    setUnauthorizedHandler(clearSession);

    return () => {
      setAccessTokenProvider(() => null);
      setUnauthorizedHandler(null);
    };
  }, [clearSession, session.accessToken]);

  const login = useCallback(async (request: AdminLoginRequest) => {
    const response = await loginAdmin(request);
    const nextSession = {
      accessToken: response.accessToken,
      refreshToken: response.refreshToken,
      role: response.user?.role ?? getRoleFromAccessToken(response.accessToken),
    };
    persistSession(nextSession);
    setSession(nextSession);
  }, []);

  const logout = clearSession;

  const value = useMemo<AuthSessionContextValue>(
    () => ({
      isAuthenticated: Boolean(session.accessToken),
      accessToken: session.accessToken,
      userRole: session.role,
      login,
      logout,
    }),
    [login, logout, session.accessToken, session.role],
  );

  return (
    <AuthSessionContext.Provider value={value}>
      {children}
    </AuthSessionContext.Provider>
  );
}

export function useAuthSession(): AuthSessionContextValue {
  const context = useContext(AuthSessionContext);
  if (!context) {
    throw new Error("useAuthSession must be used within AuthSessionProvider");
  }

  return context;
}
