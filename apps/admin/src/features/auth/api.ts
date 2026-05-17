import { apiRequest } from "../../lib/apiClient";
import { isUserRole } from "../../lib/adminPermissions";
import type { AdminLoginRequest, AdminLoginResponse } from "./types";

type RawLoginResponse = {
  readonly data?: RawLoginPayload;
} & RawLoginPayload;

type RawLoginPayload = {
  readonly accessToken?: string;
  readonly access_token?: string;
  readonly refreshToken?: string;
  readonly refresh_token?: string;
  readonly user?: {
    readonly id?: string;
    readonly email?: string;
    readonly role?: unknown;
  };
  readonly profile?: unknown;
  readonly session?: unknown;
};

export async function loginAdmin(
  request: AdminLoginRequest,
): Promise<AdminLoginResponse> {
  const response = await apiRequest<RawLoginResponse>("/v1/auth/login", {
    method: "POST",
    body: request,
  });

  const payload = response.data ?? response;

  return {
    accessToken: payload.accessToken ?? payload.access_token ?? "",
    refreshToken: payload.refreshToken ?? payload.refresh_token ?? "",
    user:
      payload.user?.id && payload.user.email && isUserRole(payload.user.role)
        ? {
            id: payload.user.id,
            email: payload.user.email,
            role: payload.user.role,
          }
        : null,
    profile: payload.profile,
    session: payload.session,
  };
}
