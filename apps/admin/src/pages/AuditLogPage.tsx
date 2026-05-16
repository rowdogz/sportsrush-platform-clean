import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  exportAuditEvents,
  listAuditEvents,
} from "../features/audit-events/api";
import {
  AdminPagination,
  normalizeAdminPageSize,
} from "../components/admin/AdminPagination";
import {
  AdminTableEmpty,
  AdminTableError,
  AdminTableLoading,
} from "../components/admin/AdminTableState";
import {
  AdminTablePreferences,
  useAdminTablePreferences,
  type AdminTableColumn,
} from "../components/admin/AdminTablePreferences";
import { AuditDiff } from "../features/audit-events/AuditDiff";
import type {
  AdminAuditEvent,
  AuditEventListFilters,
  AuditEventListMeta,
} from "../features/audit-events/types";
import {
  appendNumberParam,
  appendStringParam,
  readDateParam,
  readPageSizeParam,
  readPositiveIntParam,
  readStringParam,
  useAdminSearchParams,
} from "../hooks/useAdminSearchParams";
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

const storageKeyPrefix = "sr-admin:audit-log";

type PaginationValues = {
  readonly page: number;
  readonly pageSize: number;
};

const emptyPaginationValues: PaginationValues = {
  page: 1,
  pageSize: 50,
};

const auditTableColumns: readonly AdminTableColumn[] = [
  { id: "created", label: "Created", optional: true },
  { id: "actor", label: "Actor" },
  { id: "action", label: "Action" },
  { id: "entity", label: "Entity", optional: true },
  { id: "summary", label: "Summary", optional: true },
  { id: "details", label: "Details" },
];

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

export function AuditLogPage() {
  const [state, setState] = useState<AuditLogState>({ status: "loading" });
  const [filters, setFilters] = useAdminSearchParams({
    storageKey: `${storageKeyPrefix}:filters`,
    defaults: emptyFilterValues,
    paramKeys: [
      "actor",
      "entityType",
      "entityId",
      "action",
      "dateFrom",
      "dateTo",
    ],
    parse: (params, fallback) => ({
      actorUserId: readStringParam(params, "actor", fallback.actorUserId),
      entityType: readStringParam(params, "entityType", fallback.entityType),
      entityId: readStringParam(params, "entityId", fallback.entityId),
      action: readStringParam(params, "action", fallback.action),
      dateFrom: readDateParam(params, "dateFrom", fallback.dateFrom),
      dateTo: readDateParam(params, "dateTo", fallback.dateTo),
    }),
    serialize: (value, defaults) => {
      const params = new URLSearchParams();
      appendStringParam(
        params,
        "actor",
        value.actorUserId,
        defaults.actorUserId,
      );
      appendStringParam(
        params,
        "entityType",
        value.entityType,
        defaults.entityType,
      );
      appendStringParam(params, "entityId", value.entityId, defaults.entityId);
      appendStringParam(params, "action", value.action, defaults.action);
      appendStringParam(params, "dateFrom", value.dateFrom, defaults.dateFrom);
      appendStringParam(params, "dateTo", value.dateTo, defaults.dateTo);
      return params;
    },
  });
  const [appliedFilters, setAppliedFilters] = useState<FilterValues>(filters);
  const [pagination, setPagination] = useAdminSearchParams({
    storageKey: `${storageKeyPrefix}:pagination`,
    defaults: emptyPaginationValues,
    paramKeys: ["page", "pageSize"],
    parse: (params, fallback) => ({
      page: readPositiveIntParam(params, "page", fallback.page),
      pageSize: normalizeAdminPageSize(
        readPageSizeParam(params, "pageSize", fallback.pageSize, [25, 50, 100]),
      ),
    }),
    serialize: (value, defaults) => {
      const params = new URLSearchParams();
      appendNumberParam(params, "page", value.page, defaults.page);
      appendNumberParam(params, "pageSize", value.pageSize, defaults.pageSize);
      return params;
    },
  });
  const [exportState, setExportState] = useState<
    | { readonly status: "idle" }
    | { readonly status: "exporting" }
    | { readonly status: "error"; readonly message: string }
  >({ status: "idle" });
  const tablePreferences = useAdminTablePreferences(
    "sr-admin:audit-log:table-preferences",
    auditTableColumns,
  );

  const loadAuditEvents = useCallback(
    async (
      nextFilters: FilterValues,
      {
        page = 1,
        limit = pagination.pageSize,
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
    [pagination.pageSize],
  );

  useEffect(() => {
    void loadAuditEvents(appliedFilters, {
      page: pagination.page,
      limit: pagination.pageSize,
      showLoading: true,
    });
  }, [loadAuditEvents]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setAppliedFilters(filters);
    setPagination({ ...pagination, page: 1 });
    await loadAuditEvents(filters, {
      page: 1,
      limit: pagination.pageSize,
      showLoading: true,
    });
  }

  async function handlePageChange(page: number) {
    setPagination({ ...pagination, page });
    await loadAuditEvents(appliedFilters, {
      page,
      limit: pagination.pageSize,
      showLoading: true,
    });
  }

  async function handlePageSizeChange(nextPageSize: number) {
    setPagination({ page: 1, pageSize: nextPageSize });
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
        <AdminTableLoading message="Loading audit events…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load audit events"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.events.length === 0 ? (
        <AdminTableEmpty
          title="No audit events found"
          message="Admin mutations will appear here after they are recorded."
        />
      ) : null}

      {state.status === "success" && state.events.length > 0 ? (
        <>
          <AdminPagination
            label="Audit event pagination"
            meta={state.meta}
            pageSize={pagination.pageSize}
            onPageChange={handlePageChange}
            onPageSizeChange={handlePageSizeChange}
          />
          <AdminTablePreferences
            columns={auditTableColumns}
            density={tablePreferences.density}
            hiddenColumns={tablePreferences.hiddenColumns}
            onColumnVisibleChange={tablePreferences.setColumnVisible}
            onDensityChange={tablePreferences.setDensity}
          />
          <div className="admin-table-wrapper">
            <table className={`${tablePreferences.tableClassName} audit-table`}>
              <thead>
                <tr>
                  {tablePreferences.isColumnVisible("created") ? (
                    <th scope="col">Created</th>
                  ) : null}
                  <th scope="col">Actor</th>
                  <th scope="col">Action</th>
                  {tablePreferences.isColumnVisible("entity") ? (
                    <th scope="col">Entity</th>
                  ) : null}
                  {tablePreferences.isColumnVisible("summary") ? (
                    <th scope="col">Summary</th>
                  ) : null}
                  <th scope="col">Details</th>
                </tr>
              </thead>
              <tbody>
                {state.events.map((event) => (
                  <AuditEventRow
                    key={event.id}
                    event={event}
                    isColumnVisible={tablePreferences.isColumnVisible}
                  />
                ))}
              </tbody>
            </table>
          </div>
          <AdminPagination
            label="Audit event pagination"
            meta={state.meta}
            pageSize={pagination.pageSize}
            onPageChange={handlePageChange}
            onPageSizeChange={handlePageSizeChange}
          />
        </>
      ) : null}
    </section>
  );
}

function AuditEventRow({
  event,
  isColumnVisible,
}: {
  readonly event: AdminAuditEvent;
  readonly isColumnVisible: (columnId: string) => boolean;
}) {
  return (
    <tr>
      {isColumnVisible("created") ? <td>{event.createdAt}</td> : null}
      <td>
        <span>{getActorLabel(event)}</span>
        {event.actorEmail ? <small>{event.actorEmail}</small> : null}
      </td>
      <td>{event.action}</td>
      {isColumnVisible("entity") ? (
        <td>
          <span>{event.entityType}</span>
          <small>{event.entityId ?? "—"}</small>
        </td>
      ) : null}
      {isColumnVisible("summary") ? <td>{event.summary}</td> : null}
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
            <AuditDiff
              before={event.beforeMetadata}
              after={event.afterMetadata}
            />
          </div>
        </details>
      </td>
    </tr>
  );
}
