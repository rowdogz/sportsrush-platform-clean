import { apiRequest, apiTextRequest } from "../../lib/apiClient";
import type {
  AdminAuditEvent,
  AuditEventListFilters,
  AuditEventListResponse,
} from "./types";

type RawAuditEvent = {
  readonly id: string;
  readonly actorUserId?: string | null;
  readonly actor_user_id?: string | null;
  readonly actorEmail?: string | null;
  readonly actor_email?: string | null;
  readonly actorDisplayName?: string | null;
  readonly actor_display_name?: string | null;
  readonly action: string;
  readonly entityType?: string;
  readonly entity_type?: string;
  readonly targetType?: string;
  readonly target_type?: string;
  readonly entityId?: string | null;
  readonly entity_id?: string | null;
  readonly targetId?: string | null;
  readonly target_id?: string | null;
  readonly summary?: string;
  readonly beforeMetadata?: unknown;
  readonly before_metadata?: unknown;
  readonly afterMetadata?: unknown;
  readonly after_metadata?: unknown;
  readonly changes?: Record<string, { before: unknown; after: unknown }>;
  readonly createdAt?: string;
  readonly created_at?: string;
  readonly correlationId?: string | null;
  readonly correlation_id?: string | null;
};

type RawAuditEventListResponse = {
  readonly data: readonly RawAuditEvent[];
  readonly meta: AuditEventListResponse["meta"];
};

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

function normalizeAuditEvent(event: RawAuditEvent): AdminAuditEvent {
  const entityType =
    event.entityType ??
    event.entity_type ??
    event.targetType ??
    event.target_type ??
    "";
  const entityId =
    event.entityId ??
    event.entity_id ??
    event.targetId ??
    event.target_id ??
    null;

  return {
    id: event.id,
    actorUserId: event.actorUserId ?? event.actor_user_id ?? null,
    actorEmail: event.actorEmail ?? event.actor_email ?? null,
    actorDisplayName:
      event.actorDisplayName ?? event.actor_display_name ?? null,
    action: event.action,
    entityType,
    entityId,
    summary:
      event.summary ??
      `${event.action} on ${entityType}${entityId ? ` ${entityId}` : ""}`,
    beforeMetadata: event.beforeMetadata ?? event.before_metadata ?? null,
    afterMetadata: event.afterMetadata ?? event.after_metadata ?? null,
    changes: event.changes ?? {},
    createdAt: event.createdAt ?? event.created_at ?? "",
    correlationId: event.correlationId ?? event.correlation_id ?? null,
  };
}

function buildAuditEventParams(
  filters: AuditEventListFilters,
  options: { readonly includePagination: boolean },
): URLSearchParams {
  const params = new URLSearchParams();
  if (options.includePagination) {
    params.set("page", "1");
    params.set("limit", "50");
  }
  appendOptionalParam(params, "actorUserId", filters.actorUserId);
  appendOptionalParam(params, "entityType", filters.entityType);
  appendOptionalParam(params, "entityId", filters.entityId);
  appendOptionalParam(params, "action", filters.action);
  appendOptionalParam(params, "dateFrom", filters.dateFrom);
  appendOptionalParam(params, "dateTo", filters.dateTo);
  return params;
}

function filenameFromDisposition(value: string | null): string {
  const match = value?.match(/filename="?([^";]+)"?/i);
  return match?.[1] ?? "audit-events.csv";
}

export async function listAuditEvents(
  filters: AuditEventListFilters = {},
): Promise<AuditEventListResponse> {
  const params = buildAuditEventParams(filters, { includePagination: true });

  const response = await apiRequest<RawAuditEventListResponse>(
    `/v1/admin/audit-events?${params.toString()}`,
  );

  return {
    data: response.data.map(normalizeAuditEvent),
    meta: response.meta,
  };
}

export async function exportAuditEvents(
  filters: AuditEventListFilters = {},
): Promise<{ readonly csv: string; readonly filename: string }> {
  const params = buildAuditEventParams(filters, { includePagination: false });
  const queryString = params.toString();
  const response = await apiTextRequest(
    `/v1/admin/audit-events/export${queryString ? `?${queryString}` : ""}`,
  );

  return {
    csv: response.text,
    filename: filenameFromDisposition(
      response.headers.get("Content-Disposition"),
    ),
  };
}
