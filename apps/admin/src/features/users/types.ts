export const userRoles = ["user", "admin", "superadmin"] as const;

export type UserRole = (typeof userRoles)[number];

export type AdminUser = {
  readonly id: string;
  readonly email: string;
  readonly displayName: string | null;
  readonly role: UserRole;
  readonly isActive: boolean;
  readonly emailVerifiedAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly profileUpdatedAt: string | null;
  readonly legacyWpUserId: number | null;
};

export type UserListFilters = {
  readonly search?: string | null;
  readonly role?: UserRole | null;
  readonly isActive?: boolean | null;
};

export type UserListMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type UserListResponse = {
  readonly data: readonly AdminUser[];
  readonly meta: UserListMeta;
};
