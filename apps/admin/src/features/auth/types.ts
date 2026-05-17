import type { UserRole } from "../users/types";

export type AdminLoginRequest = {
  readonly email: string;
  readonly password: string;
};

export type AdminLoginResponse = {
  readonly accessToken: string;
  readonly refreshToken: string;
  readonly user: {
    readonly id: string;
    readonly email: string;
    readonly role: UserRole;
  } | null;
  readonly profile: unknown | null;
  readonly session: unknown | null;
};
