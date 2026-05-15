import { apiRequest } from "../../lib/apiClient";
import type { AdminLoginRequest, AdminLoginResponse } from "./types";

type RawLoginResponse = {
  readonly accessToken?: string;
  readonly access_token?: string;
  readonly refreshToken?: string;
  readonly refresh_token?: string;
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
  };
}
