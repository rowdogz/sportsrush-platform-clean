import {
  createContext,
  useCallback,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import type { AuthResponse, AuthUser } from "../features/types";
import { login, refresh, register } from "../lib/apiClient";

const ACCESS_TOKEN_KEY = "sr_user_access_token";
const REFRESH_TOKEN_KEY = "sr_user_refresh_token";
const USER_KEY = "sr_user";

type Session = {
  readonly accessToken: string | null;
  readonly refreshToken: string | null;
  readonly user: AuthUser | null;
};

type AuthContextValue = Session & {
  readonly isAuthenticated: boolean;
  readonly isLoading: boolean;
  readonly signIn: (email: string, password: string) => Promise<void>;
  readonly signUp: (
    email: string,
    password: string,
    displayName?: string,
  ) => Promise<void>;
  readonly signOut: () => void;
  readonly refreshSession: () => Promise<void>;
  readonly applyAuthResponse: (response: AuthResponse) => void;
};

const AuthContext = createContext<AuthContextValue | undefined>(undefined);

function readStoredSession(): Session {
  if (typeof window === "undefined") {
    return { accessToken: null, refreshToken: null, user: null };
  }
  const userJson = window.localStorage.getItem(USER_KEY);
  return {
    accessToken: window.localStorage.getItem(ACCESS_TOKEN_KEY),
    refreshToken: window.localStorage.getItem(REFRESH_TOKEN_KEY),
    user: userJson ? (JSON.parse(userJson) as AuthUser) : null,
  };
}

function persistSession(session: Session): void {
  if (typeof window === "undefined") return;
  if (session.accessToken) {
    window.localStorage.setItem(ACCESS_TOKEN_KEY, session.accessToken);
  } else {
    window.localStorage.removeItem(ACCESS_TOKEN_KEY);
  }
  if (session.refreshToken) {
    window.localStorage.setItem(REFRESH_TOKEN_KEY, session.refreshToken);
  } else {
    window.localStorage.removeItem(REFRESH_TOKEN_KEY);
  }
  if (session.user) {
    window.localStorage.setItem(USER_KEY, JSON.stringify(session.user));
  } else {
    window.localStorage.removeItem(USER_KEY);
  }
}

function fromAuthResponse(response: AuthResponse): Session {
  return {
    accessToken: response.accessToken,
    refreshToken: response.refreshToken,
    user: response.user ?? null,
  };
}

export function AuthProvider({ children }: { readonly children: ReactNode }) {
  const [session, setSession] = useState<Session>(readStoredSession);
  const [isLoading, setIsLoading] = useState(false);

  const applySession = useCallback((nextSession: Session) => {
    persistSession(nextSession);
    setSession(nextSession);
  }, []);

  const signOut = useCallback(() => {
    applySession({ accessToken: null, refreshToken: null, user: null });
  }, [applySession]);

  const applyAuthResponse = useCallback(
    (response: AuthResponse) => {
      applySession(fromAuthResponse(response));
    },
    [applySession],
  );

  const refreshSession = useCallback(async () => {
    if (!session.refreshToken) return;
    setIsLoading(true);
    try {
      const response = await refresh(session.refreshToken);
      applySession({
        accessToken: response.accessToken,
        refreshToken: response.refreshToken,
        user: response.user ?? session.user,
      });
    } catch {
      signOut();
    } finally {
      setIsLoading(false);
    }
  }, [applySession, session.refreshToken, session.user, signOut]);

  useEffect(() => {
    if (session.refreshToken && !session.accessToken) {
      void refreshSession();
    }
  }, [refreshSession, session.accessToken, session.refreshToken]);

  const signIn = useCallback(
    async (email: string, password: string) => {
      setIsLoading(true);
      try {
        applySession(fromAuthResponse(await login(email, password)));
      } finally {
        setIsLoading(false);
      }
    },
    [applySession],
  );

  const signUp = useCallback(
    async (email: string, password: string, displayName?: string) => {
      setIsLoading(true);
      try {
        applySession(
          fromAuthResponse(await register(email, password, displayName)),
        );
      } finally {
        setIsLoading(false);
      }
    },
    [applySession],
  );

  const value = useMemo<AuthContextValue>(
    () => ({
      ...session,
      isAuthenticated: Boolean(session.accessToken),
      isLoading,
      signIn,
      signUp,
      signOut,
      refreshSession,
      applyAuthResponse,
    }),
    [
      applyAuthResponse,
      isLoading,
      refreshSession,
      session,
      signIn,
      signOut,
      signUp,
    ],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error("useAuth must be used within AuthProvider");
  }
  return context;
}
