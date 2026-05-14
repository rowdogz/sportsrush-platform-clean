import {
  createContext,
  useCallback,
  useContext,
  useMemo,
  useState,
  type ReactNode,
} from "react";
import {
  clearAuthSession,
  readAuthSession,
  writeAuthSession,
  type AuthSession,
} from "./storage";

type LoginInput = {
  readonly email: string;
  readonly password: string;
};

type AuthContextValue = {
  readonly session: AuthSession | null;
  readonly isAuthenticated: boolean;
  readonly login: (input: LoginInput) => Promise<void>;
  readonly logout: () => void;
  readonly getToken: () => string | null;
};

const AuthContext = createContext<AuthContextValue | null>(null);

type AuthProviderProps = {
  readonly children: ReactNode;
  readonly initialSession?: AuthSession | null;
};

export function AuthProvider({ children, initialSession }: AuthProviderProps) {
  const [session, setSession] = useState<AuthSession | null>(() =>
    initialSession === undefined ? readAuthSession() : initialSession,
  );

  const login = useCallback(async (input: LoginInput) => {
    const email = input.email.trim();
    if (email.length === 0 || input.password.length === 0) {
      throw new Error("Email and password are required");
    }

    const nextSession: AuthSession = {
      token: `placeholder-admin-token:${email}`,
      userLabel: email,
    };
    writeAuthSession(nextSession);
    setSession(nextSession);
  }, []);

  const logout = useCallback(() => {
    clearAuthSession();
    setSession(null);
  }, []);

  const getToken = useCallback(() => session?.token ?? null, [session]);

  const value = useMemo<AuthContextValue>(
    () => ({
      session,
      isAuthenticated: session !== null,
      login,
      logout,
      getToken,
    }),
    [getToken, login, logout, session],
  );

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
}

export function useAuth(): AuthContextValue {
  const context = useContext(AuthContext);
  if (context === null) {
    throw new Error("useAuth must be used inside AuthProvider");
  }
  return context;
}
