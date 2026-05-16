import { apiRequest } from "../../lib/apiClient";
import { isUserRole } from "../../lib/adminPermissions";
import type { AdminLoginRequest, AdminLoginResponse } from "./types";

type RawLoginResponse = {
  readonly accessToken?: string;
  readonly access_token?: string;
  readonly refreshToken?: string;
  readonly refresh_token?: string;
  readonly user?: {
    readonly id?: string;
    readonly email?: string;
    readonly role?: unknown;
  };
};

export async function loginAdmin(
  request: AdminLoginRequest,
): Promise<AdminLoginResponse> {
  const response = await apiRequest<RawLoginResponse>("/v1/auth/login", {
    method: "POST",
    body: request,
  });

  return {
    accessToken: response.accessToken ?? response.access_token ?? "",
    refreshToken: response.refreshToken ?? response.refresh_token ?? "",
    user:
      response.user?.id && response.user.email && isUserRole(response.user.role)
        ? {
            id: response.user.id,
            email: response.user.email,
            role: response.user.role,
          }
        : null,
  };
}
