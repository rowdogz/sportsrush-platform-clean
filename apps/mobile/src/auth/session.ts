import AsyncStorage from "@react-native-async-storage/async-storage";

const ACCESS_TOKEN_KEY = "sr_user_access_token";
const REFRESH_TOKEN_KEY = "sr_user_refresh_token";

export type MobileSession = {
  readonly accessToken: string | null;
  readonly refreshToken: string | null;
};

export async function readSession(): Promise<MobileSession> {
  const [accessToken, refreshToken] = await Promise.all([
    AsyncStorage.getItem(ACCESS_TOKEN_KEY),
    AsyncStorage.getItem(REFRESH_TOKEN_KEY),
  ]);
  return { accessToken, refreshToken };
}

export async function persistSession(session: MobileSession): Promise<void> {
  await Promise.all([
    session.accessToken
      ? AsyncStorage.setItem(ACCESS_TOKEN_KEY, session.accessToken)
      : AsyncStorage.removeItem(ACCESS_TOKEN_KEY),
    session.refreshToken
      ? AsyncStorage.setItem(REFRESH_TOKEN_KEY, session.refreshToken)
      : AsyncStorage.removeItem(REFRESH_TOKEN_KEY),
  ]);
}
