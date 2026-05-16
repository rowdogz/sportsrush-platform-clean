import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  exportAuditEvents,
  listAuditEvents,
} from "../features/audit-events/api";
import type {
  AdminAuditEvent,
  AuditEventListFilters,
  AuditEventListMeta,
} from "../features/audit-events/types";
import { ApiError } from "../lib/apiClient";

type AuditLogState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly events: readonly AdminAuditEvent[];
      readonly meta: AuditEventListMeta;
    }
  | { readonly status: "error"; readonly message: string };

type FilterValues = {
  readonly actorUserId: string;
  readonly entityType: string;
  readonly entityId: string;
  readonly action: string;
  readonly dateFrom: string;
  readonly dateTo: string;
};

const emptyFilterValues: FilterValues = {
  actorUserId: "",
  entityType: "",
  entityId: "",
  action: "",
  dateFrom: "",
  dateTo: "",
};

const pageSizeOptions = [25, 50, 100] as const;

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load audit events.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function toFilters(values: FilterValues): AuditEventListFilters {
  return {
    actorUserId: toOptionalValue(values.actorUserId),
    entityType: toOptionalValue(values.entityType),
    entityId: toOptionalValue(values.entityId),
    action: toOptionalValue(values.action),
    dateFrom: toOptionalValue(values.dateFrom),
    dateTo: toOptionalValue(values.dateTo),
  };
}

function getActorLabel(event: AdminAuditEvent): string {
  return (
    event.actorDisplayName ??
    event.actorEmail ??
    event.actorUserId ??
    "Unknown actor"
  );
}

function formatJson(value: unknown): string {
  return JSON.stringify(value ?? null, null, 2);
}

export function AuditLogPage() {
  const [state, setState] = useState<AuditLogState>({ status: "loading" });
  const [filters, setFilters] = useState<FilterValues>(emptyFilterValues);
  const [appliedFilters, setAppliedFilters] =
    useState<FilterValues>(emptyFilterValues);
  const [pageSize, setPageSize] = useState(50);
  const [exportState, setExportState] = useState<
    | { readonly status: "idle" }
    | { readonly status: "exporting" }
    | { readonly status: "error"; readonly message: string }
  >({ status: "idle" });

  const loadAuditEvents = useCallback(
    async (
      nextFilters: FilterValues,
      {
        page = 1,
        limit = 50,
        showLoading = false,
      }: {
        readonly page?: number;
        readonly limit?: number;
        readonly showLoading?: boolean;
      } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }

      try {
        const response = await listAuditEvents(toFilters(nextFilters), {
          page,
          limit,
        });
        setState({
          status: "success",
          events: response.data,
          meta: response.meta,
        });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadAuditEvents(emptyFilterValues, { showLoading: true });
  }, [loadAuditEvents]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAppliedFilters(filters);
    await loadAuditEvents(filters, {
      page: 1,
      limit: pageSize,
      showLoading: true,
    });
  }

  async function handlePageChange(page: number) {
    await loadAuditEvents(appliedFilters, {
      page,
      limit: pageSize,
      showLoading: true,
    });
  }

  async function handlePageSizeChange(nextPageSize: number) {
    setPageSize(nextPageSize);
    await loadAuditEvents(appliedFilters, {
      page: 1,
      limit: nextPageSize,
      showLoading: true,
    });
  }

  async function handleExport() {
    setExportState({ status: "exporting" });

    try {
      const response = await exportAuditEvents(toFilters(filters));
      const blob = new Blob([response.csv], { type: "text/csv" });
      const url = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = url;
      link.download = response.filename;
      document.body.append(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
      setExportState({ status: "idle" });
    } catch (error) {
      setExportState({ status: "error", message: getErrorMessage(error) });
    }
  }

  return (
    <section aria-labelledby="audit-log-title">
      <div className="page-heading">
        <h2 id="audit-log-title">Audit Log</h2>
        <p>Review admin mutations across SportsRush.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter audit events</h3>
        </div>
        <div className="form-grid">
          <label>
            Actor user ID
            <input
              value={filters.actorUserId}
              onChange={(event) =>
                setFilters({ ...filters, actorUserId: event.target.value })
              }
            />
          </label>
          <label>
            Entity type
            <input
              value={filters.entityType}
              onChange={(event) =>
                setFilters({ ...filters, entityType: event.target.value })
              }
            />
          </label>
          <label>
            Entity ID
            <input
              value={filters.entityId}
              onChange={(event) =>
                setFilters({ ...filters, entityId: event.target.value })
              }
            />
          </label>
          <label>
            Action
            <input
              value={filters.action}
              onChange={(event) =>
                setFilters({ ...filters, action: event.target.value })
              }
            />
          </label>
          <label>
            Date from
            <input
              type="datetime-local"
              value={filters.dateFrom}
              onChange={(event) =>
                setFilters({ ...filters, dateFrom: event.target.value })
              }
            />
          </label>
          <label>
            Date to
            <input
              type="datetime-local"
              value={filters.dateTo}
              onChange={(event) =>
                setFilters({ ...filters, dateTo: event.target.value })
              }
            />
          </label>
        </div>
        <div className="form-actions">
          <button className="secondary-button" type="submit">
            Apply filters
          </button>
          <button
            className="secondary-button"
            type="button"
            onClick={handleExport}
            disabled={exportState.status === "exporting"}
          >
            {exportState.status === "exporting" ? "Exporting…" : "Export CSV"}
          </button>
        </div>
      </form>

      {exportState.status === "error" ? (
        <div className="state-panel error-panel" role="alert">
          <strong>Unable to export audit events</strong>
          <span>{exportState.message}</span>
        </div>
      ) : null}

      {state.status === "loading" ? (
        <div className="state-panel" role="status">
          Loading audit events…
        </div>
      ) : null}

      {state.status === "error" ? (
        <div className="state-panel error-panel" role="alert">
          <strong>Unable to load audit events</strong>
          <span>{state.message}</span>
        </div>
      ) : null}

      {state.status === "success" && state.events.length === 0 ? (
        <div className="state-panel">
          <strong>No audit events found</strong>
          <span>Admin mutations will appear here after they are recorded.</span>
        </div>
      ) : null}

      {state.status === "success" && state.events.length > 0 ? (
        <>
          <AuditPagination
            meta={state.meta}
            pageSize={pageSize}
            onPageChange={handlePageChange}
            onPageSizeChange={handlePageSizeChange}
          />
          <div className="admin-table-wrapper">
            <table className="admin-table audit-table">
              <thead>
                <tr>
                  <th scope="col">Created</th>
                  <th scope="col">Actor</th>
                  <th scope="col">Action</th>
                  <th scope="col">Entity</th>
                  <th scope="col">Summary</th>
                  <th scope="col">Details</th>
                </tr>
              </thead>
              <tbody>
                {state.events.map((event) => (
                  <AuditEventRow key={event.id} event={event} />
                ))}
              </tbody>
            </table>
          </div>
          <AuditPagination
            meta={state.meta}
            pageSize={pageSize}
            onPageChange={handlePageChange}
            onPageSizeChange={handlePageSizeChange}
          />
        </>
      ) : null}
    </section>
  );
}

function AuditPagination({
  meta,
  pageSize,
  onPageChange,
  onPageSizeChange,
}: {
  readonly meta: AuditEventListMeta;
  readonly pageSize: number;
  readonly onPageChange: (page: number) => void;
  readonly onPageSizeChange: (pageSize: number) => void;
}) {
  const firstItem = meta.total === 0 ? 0 : (meta.page - 1) * meta.limit + 1;
  const lastItem = Math.min(meta.page * meta.limit, meta.total);

  return (
    <div className="pagination-bar" aria-label="Audit event pagination">
      <div>
        <strong>Page {meta.page}</strong>
        <span>
          Showing {firstItem}–{lastItem} of {meta.total}
        </span>
      </div>
      <label>
        Page size
        <select
          value={pageSize}
          onChange={(event) => onPageSizeChange(Number(event.target.value))}
        >
          {pageSizeOptions.map((option) => (
            <option key={option} value={option}>
              {option}
            </option>
          ))}
        </select>
      </label>
      <div className="row-actions">
        <button
          className="secondary-button"
          type="button"
          onClick={() => onPageChange(meta.page - 1)}
          disabled={meta.page <= 1}
        >
          Previous
        </button>
        <button
          className="secondary-button"
          type="button"
          onClick={() => onPageChange(meta.page + 1)}
          disabled={!meta.hasMore}
        >
          Next
        </button>
      </div>
    </div>
  );
}

function AuditEventRow({ event }: { readonly event: AdminAuditEvent }) {
  return (
    <tr>
      <td>{event.createdAt}</td>
      <td>
        <span>{getActorLabel(event)}</span>
        {event.actorEmail ? <small>{event.actorEmail}</small> : null}
      </td>
      <td>{event.action}</td>
      <td>
        <span>{event.entityType}</span>
        <small>{event.entityId ?? "—"}</small>
      </td>
      <td>{event.summary}</td>
      <td>
        <details>
          <summary>View event details</summary>
          <div className="audit-detail-panel">
            <dl className="audit-detail-list">
              <div>
                <dt>Timestamp</dt>
                <dd>{event.createdAt}</dd>
              </div>
              <div>
                <dt>Actor</dt>
                <dd>
                  {getActorLabel(event)}
                  {event.actorUserId ? ` (${event.actorUserId})` : ""}
                </dd>
              </div>
              <div>
                <dt>Action</dt>
                <dd>{event.action}</dd>
              </div>
              <div>
                <dt>Entity</dt>
                <dd>
                  {event.entityType} {event.entityId ?? "—"}
                </dd>
              </div>
              <div>
                <dt>Correlation ID</dt>
                <dd>{event.correlationId ?? "—"}</dd>
              </div>
            </dl>
            <div className="metadata-grid">
              <div>
                <strong>Changes</strong>
                <pre>{formatJson(event.changes)}</pre>
              </div>
              <div>
                <strong>Before</strong>
                <pre>{formatJson(event.beforeMetadata)}</pre>
              </div>
              <div>
                <strong>After</strong>
                <pre>{formatJson(event.afterMetadata)}</pre>
              </div>
            </div>
          </div>
        </details>
      </td>
    </tr>
  );
}
