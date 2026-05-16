export type AuditChange = {
  readonly before: unknown;
  readonly after: unknown;
};

export type AdminAuditEvent = {
  readonly id: string;
  readonly actorUserId: string | null;
  readonly actorEmail: string | null;
  readonly actorDisplayName: string | null;
  readonly action: string;
  readonly entityType: string;
  readonly entityId: string | null;
  readonly summary: string;
  readonly beforeMetadata: unknown;
  readonly afterMetadata: unknown;
  readonly changes: Record<string, AuditChange>;
  readonly createdAt: string;
  readonly correlationId: string | null;
};

export type AuditEventListFilters = {
  readonly actorUserId?: string | null;
  readonly entityType?: string | null;
  readonly entityId?: string | null;
  readonly action?: string | null;
  readonly dateFrom?: string | null;
  readonly dateTo?: string | null;
};

export type AuditEventListOptions = {
  readonly page?: number;
  readonly limit?: number;
};

export type AuditEventListMeta = {
  readonly page: number;
  readonly limit: number;
  readonly total: number;
  readonly hasMore: boolean;
};

export type AuditEventListResponse = {
  readonly data: readonly AdminAuditEvent[];
  readonly meta: AuditEventListMeta;
};
