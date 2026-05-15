export type AuthSession = {
  readonly token: string;
  readonly userLabel: string;
};

const KEY = "sportsrush.admin.auth";

type StorageLike = Pick<Storage, "getItem" | "setItem" | "removeItem">;

function storage(): StorageLike | null {
  return typeof window === "undefined" ? null : window.localStorage;
}

export function readAuthSession(store: StorageLike | null = storage()): AuthSession | null {
  const raw = store?.getItem(KEY);
  if (raw === undefined || raw === null) return null;
  try {
    const parsed = JSON.parse(raw) as Partial<AuthSession>;
    if (typeof parsed.token !== "string" || parsed.token.length === 0) return null;
    return {
      token: parsed.token,
      userLabel: typeof parsed.userLabel === "string" ? parsed.userLabel : "Admin user",
    };
  } catch {
    return null;
  }
}

export function writeAuthSession(session: AuthSession, store: StorageLike | null = storage()): void {
  store?.setItem(KEY, JSON.stringify(session));
}

export function clearAuthSession(store: StorageLike | null = storage()): void {
  store?.removeItem(KEY);
}
