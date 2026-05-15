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
import { setAccessTokenProvider } from "../lib/apiClient";

const ACCESS_TOKEN_STORAGE_KEY = "sr_admin_access_token";
const REFRESH_TOKEN_STORAGE_KEY = "sr_admin_refresh_token";

type AuthSession = {
  readonly accessToken: string | null;
  readonly refreshToken: string | null;
};

type AuthSessionContextValue = {
  readonly isAuthenticated: boolean;
  readonly accessToken: string | null;
  readonly login: (request: AdminLoginRequest) => Promise<void>;
  readonly logout: () => void;
};

const AuthSessionContext = createContext<AuthSessionContextValue | undefined>(
  undefined,
);

function getStoredSession(): AuthSession {
  if (typeof window === "undefined") {
    return { accessToken: null, refreshToken: null };
  }

  return {
    accessToken: window.localStorage.getItem(ACCESS_TOKEN_STORAGE_KEY),
    refreshToken: window.localStorage.getItem(REFRESH_TOKEN_STORAGE_KEY),
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

  useEffect(() => {
    setAccessTokenProvider(() => session.accessToken);

    return () => {
      setAccessTokenProvider(() => null);
    };
  }, [session.accessToken]);

  const login = useCallback(async (request: AdminLoginRequest) => {
    const response = await loginAdmin(request);
    const nextSession = {
      accessToken: response.accessToken,
      refreshToken: response.refreshToken,
    };
    persistSession(nextSession);
    setSession(nextSession);
  }, []);

  const logout = useCallback(() => {
    const nextSession = { accessToken: null, refreshToken: null };
    persistSession(nextSession);
    setSession(nextSession);
  }, []);

  const value = useMemo<AuthSessionContextValue>(
    () => ({
      isAuthenticated: Boolean(session.accessToken),
      accessToken: session.accessToken,
      login,
      logout,
    }),
    [login, logout, session.accessToken],
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
