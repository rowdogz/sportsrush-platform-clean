import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  exportAuditEvents,
  listAuditEvents,
} from "../features/audit-events/api";
import type {
  AdminAuditEvent,
  AuditEventListFilters,
} from "../features/audit-events/types";
import { ApiError } from "../lib/apiClient";

type AuditLogState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly events: readonly AdminAuditEvent[];
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
  const [exportState, setExportState] = useState<
    | { readonly status: "idle" }
    | { readonly status: "exporting" }
    | { readonly status: "error"; readonly message: string }
  >({ status: "idle" });

  const loadAuditEvents = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }

      try {
        const response = await listAuditEvents(toFilters(nextFilters));
        setState({ status: "success", events: response.data });
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
    await loadAuditEvents(filters, { showLoading: true });
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
        <div className="admin-table-wrapper">
          <table className="admin-table">
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
                <tr key={event.id}>
                  <td>{event.createdAt}</td>
                  <td>
                    <span>{getActorLabel(event)}</span>
                    {event.actorEmail ? (
                      <small>{event.actorEmail}</small>
                    ) : null}
                  </td>
                  <td>{event.action}</td>
                  <td>
                    <span>{event.entityType}</span>
                    <small>{event.entityId ?? "—"}</small>
                  </td>
                  <td>{event.summary}</td>
                  <td>
                    <details>
                      <summary>View metadata</summary>
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
                        {event.correlationId ? (
                          <div>
                            <strong>Correlation ID</strong>
                            <pre>{event.correlationId}</pre>
                          </div>
                        ) : null}
                      </div>
                    </details>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </section>
  );
}
