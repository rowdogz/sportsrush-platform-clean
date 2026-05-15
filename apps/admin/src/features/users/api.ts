import { apiRequest } from "../../lib/apiClient";
import type {
  AdminUser,
  UserListFilters,
  UserListResponse,
  UserRole,
} from "./types";

type RawUser = {
  readonly id: string;
  readonly email: string;
  readonly displayName?: string | null;
  readonly display_name?: string | null;
  readonly role: UserRole;
  readonly isActive?: boolean | number;
  readonly is_active?: boolean | number;
  readonly emailVerifiedAt?: string | null;
  readonly email_verified_at?: string | null;
  readonly createdAt?: string;
  readonly created_at?: string;
  readonly updatedAt?: string;
  readonly updated_at?: string;
  readonly profileUpdatedAt?: string | null;
  readonly profile_updated_at?: string | null;
  readonly legacyWpUserId?: number | string | null;
  readonly legacy_wp_user_id?: number | string | null;
};

type RawUserListResponse = {
  readonly data: readonly RawUser[];
  readonly meta: UserListResponse["meta"];
};

type RawUserResponse = {
  readonly data: RawUser;
};

function toBoolean(value: boolean | number | undefined): boolean {
  return value === true || value === 1;
}

function toNullableNumber(
  value: number | string | null | undefined,
): number | null {
  if (value === null || value === undefined || value === "") {
    return null;
  }

  return Number(value);
}

function normalizeUser(user: RawUser): AdminUser {
  return {
    id: user.id,
    email: user.email,
    displayName: user.displayName ?? user.display_name ?? null,
    role: user.role,
    isActive: toBoolean(user.isActive ?? user.is_active),
    emailVerifiedAt: user.emailVerifiedAt ?? user.email_verified_at ?? null,
    createdAt: user.createdAt ?? user.created_at ?? "",
    updatedAt: user.updatedAt ?? user.updated_at ?? "",
    profileUpdatedAt: user.profileUpdatedAt ?? user.profile_updated_at ?? null,
    legacyWpUserId: toNullableNumber(
      user.legacyWpUserId ?? user.legacy_wp_user_id,
    ),
  };
}

function appendOptionalParam(
  params: URLSearchParams,
  key: string,
  value: string | null | undefined,
) {
  const trimmedValue = value?.trim();
  if (trimmedValue) {
    params.set(key, trimmedValue);
  }
}

export async function listUsers(
  filters: UserListFilters = {},
): Promise<UserListResponse> {
  const params = new URLSearchParams();
  params.set("page", "1");
  params.set("limit", "50");
  appendOptionalParam(params, "search", filters.search);
  appendOptionalParam(params, "role", filters.role);
  if (filters.isActive !== null && filters.isActive !== undefined) {
    params.set("isActive", String(filters.isActive));
  }

  const response = await apiRequest<RawUserListResponse>(
    `/v1/admin/users?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeUser),
    meta: response.meta,
  };
}

export async function updateUserRole(
  id: string,
  role: UserRole,
): Promise<AdminUser> {
  const response = await apiRequest<RawUserResponse>(
    `/v1/admin/users/${id}/role`,
    {
      method: "PATCH",
      body: { role },
    },
  );
  return normalizeUser(response.data);
}

export async function updateUserStatus(
  id: string,
  isActive: boolean,
): Promise<AdminUser> {
  const response = await apiRequest<RawUserResponse>(
    `/v1/admin/users/${id}/status`,
    {
      method: "PATCH",
      body: { isActive },
    },
  );
  return normalizeUser(response.data);
}

export async function suspendUser(id: string): Promise<AdminUser> {
  const response = await apiRequest<RawUserResponse>(
    `/v1/admin/users/${id}/suspend`,
    { method: "POST" },
  );
  return normalizeUser(response.data);
}

export async function reactivateUser(id: string): Promise<AdminUser> {
  const response = await apiRequest<RawUserResponse>(
    `/v1/admin/users/${id}/reactivate`,
    { method: "POST" },
  );
  return normalizeUser(response.data);
}
