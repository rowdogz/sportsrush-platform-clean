export type AuthSession = {
  readonly token: string;
  readonly userLabel: string;
};

const AUTH_STORAGE_KEY = "sportsrush.admin.auth";

type StorageLike = Pick<Storage, "getItem" | "setItem" | "removeItem">;

function getStorage(): StorageLike | null {
  if (typeof window === "undefined") return null;
  return window.localStorage;
}

export function readAuthSession(storage: StorageLike | null = getStorage()): AuthSession | null {
  const raw = storage?.getItem(AUTH_STORAGE_KEY);
  if (raw === undefined || raw === null) return null;
  try {
    const parsed = JSON.parse(raw) as Partial<AuthSession>;
    if (typeof parsed.token !== "string" || parsed.token.length === 0) return null;
    return {
      token: parsed.token,
      userLabel:
        typeof parsed.userLabel === "string" && parsed.userLabel.length > 0
          ? parsed.userLabel
          : "Admin user",
    };
  } catch {
    return null;
  }
}

export function writeAuthSession(
  session: AuthSession,
  storage: StorageLike | null = getStorage(),
): void {
  storage?.setItem(AUTH_STORAGE_KEY, JSON.stringify(session));
}

export function clearAuthSession(storage: StorageLike | null = getStorage()): void {
  storage?.removeItem(AUTH_STORAGE_KEY);
}
