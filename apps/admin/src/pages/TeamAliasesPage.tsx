import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  createTeamAlias,
  deleteTeamAlias,
  listTeamAliases,
  updateTeamAlias,
} from "../features/team-aliases/api";
import type {
  AdminTeamAlias,
  TeamAliasListFilters,
  TeamAliasWritePayload,
} from "../features/team-aliases/types";
import {
  AdminFeedback,
  adminErrorToast,
  adminSuccessToast,
  type AdminFeedbackState,
} from "../components/admin/AdminFeedback";
import {
  AdminTableEmpty,
  AdminTableError,
  AdminTableLoading,
} from "../components/admin/AdminTableState";
import {
  appendStringParam,
  readDateParam,
  readEnumParam,
  readStringParam,
  useAdminSearchParams,
} from "../hooks/useAdminSearchParams";
import { ApiError } from "../lib/apiClient";

type TeamAliasesState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly aliases: readonly AdminTeamAlias[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly teamId: string;
  readonly sportId: string;
  readonly alias: string;
  readonly normalizedAlias: string;
  readonly source: string;
  readonly priority: string;
  readonly isActive: boolean;
  readonly legacyId: string;
};

type FilterValues = {
  readonly sportId: string;
  readonly source: string;
  readonly alias: string;
};

type FeedbackState = AdminFeedbackState;

const defaultSportId = "sport-rugby-league";

const emptyFormValues: FormValues = {
  teamId: "",
  sportId: defaultSportId,
  alias: "",
  normalizedAlias: "",
  source: "manual",
  priority: "100",
  isActive: true,
  legacyId: "",
};

const emptyFilterValues: FilterValues = {
  sportId: defaultSportId,
  source: "",
  alias: "",
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load team aliases.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function getRequiredValidationMessage(values: FormValues): string | null {
  if (!values.teamId.trim() || !values.sportId.trim() || !values.alias.trim()) {
    return "Team ID, sport ID, and alias are required.";
  }

  return null;
}

function getFilterValidationMessage(values: FilterValues): string | null {
  if (!values.sportId.trim()) {
    return "Sport ID is required to load aliases.";
  }

  return null;
}

function toFilters(values: FilterValues): TeamAliasListFilters {
  return {
    sportId: values.sportId.trim(),
    source: toOptionalValue(values.source),
    alias: toOptionalValue(values.alias),
  };
}

function toWritePayload(values: FormValues): TeamAliasWritePayload {
  return {
    teamId: values.teamId.trim(),
    sportId: values.sportId.trim(),
    alias: values.alias.trim(),
    normalizedAlias: toOptionalValue(values.normalizedAlias),
    source: toOptionalValue(values.source),
    priority: values.priority.trim() ? Number(values.priority) : null,
    isActive: values.isActive,
    legacyId: toOptionalValue(values.legacyId),
  };
}

function toFormValues(alias: AdminTeamAlias): FormValues {
  return {
    teamId: alias.teamId,
    sportId: alias.sportId,
    alias: alias.alias,
    normalizedAlias: alias.normalizedAlias,
    source: alias.source,
    priority: String(alias.priority),
    isActive: alias.isActive,
    legacyId: alias.legacyId ?? "",
  };
}

export function TeamAliasesPage() {
  const [state, setState] = useState<TeamAliasesState>({
    status: "loading",
  });
  const [filters, setFilters] = useAdminSearchParams({
    storageKey: "sr-admin:team-aliases:filters",
    defaults: emptyFilterValues,
    paramKeys: ["sportId", "source", "search"],
    parse: (params, fallback) => ({
      sportId: readStringParam(params, "sportId", fallback.sportId),
      source: readStringParam(params, "source", fallback.source),
      alias: readStringParam(params, "search", fallback.alias),
    }),
    serialize: (value, defaults) => {
      const params = new URLSearchParams();
      appendStringParam(params, "sportId", value.sportId, defaults.sportId);
      appendStringParam(params, "source", value.source, defaults.source);
      appendStringParam(params, "search", value.alias, defaults.alias);
      return params;
    },
  });
  const [activeFilters, setActiveFilters] = useState<FilterValues>(filters);
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);

  const loadAliases = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listTeamAliases(toFilters(nextFilters));
        setState({ status: "success", aliases: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadAliases(activeFilters, { showLoading: true });
  }, [loadAliases]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getFilterValidationMessage(filters);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setActiveFilters(filters);
    await loadAliases(filters, { showLoading: true });
  }

  async function handleCreateSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(createValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction("create");
    try {
      await createTeamAlias(toWritePayload(createValues));
      await loadAliases(activeFilters);
      setCreateValues({ ...emptyFormValues, sportId: createValues.sportId });
      setFeedback(adminSuccessToast("Team alias created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(alias: AdminTeamAlias) {
    setFeedback(null);
    setEditingId(alias.id);
    setEditValues(toFormValues(alias));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    alias: AdminTeamAlias,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${alias.id}`);
    try {
      await updateTeamAlias(alias.id, toWritePayload(editValues));
      await loadAliases(activeFilters);
      setEditingId(null);
      setFeedback(adminSuccessToast("Team alias updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleDelete(alias: AdminTeamAlias) {
    setFeedback(null);
    if (!window.confirm(`Delete alias ${alias.alias}?`)) {
      return;
    }

    setPendingAction(`delete:${alias.id}`);
    try {
      await deleteTeamAlias(alias.id);
      await loadAliases(activeFilters);
      setFeedback(adminSuccessToast("Team alias deleted."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="team-aliases-title">
      <div className="page-heading">
        <h2 id="team-aliases-title">Team Aliases</h2>
        <p>Manage source-specific aliases linked to SportsRush teams.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter aliases</h3>
        </div>
        <div className="form-grid">
          <label>
            Filter sport ID
            <input
              value={filters.sportId}
              onChange={(event) =>
                setFilters({ ...filters, sportId: event.target.value })
              }
            />
          </label>
          <label>
            Filter source
            <input
              value={filters.source}
              onChange={(event) =>
                setFilters({ ...filters, source: event.target.value })
              }
            />
          </label>
          <label>
            Filter alias
            <input
              value={filters.alias}
              onChange={(event) =>
                setFilters({ ...filters, alias: event.target.value })
              }
            />
          </label>
        </div>
        <button className="secondary-button" type="submit">
          Apply filters
        </button>
      </form>

      <form className="admin-form" onSubmit={handleCreateSubmit}>
        <div className="form-heading">
          <h3>Create team alias</h3>
        </div>
        <div className="form-grid">
          <label>
            Team ID
            <input
              value={createValues.teamId}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  teamId: event.target.value,
                })
              }
            />
          </label>
          <label>
            Sport ID
            <input
              value={createValues.sportId}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  sportId: event.target.value,
                })
              }
            />
          </label>
          <label>
            Alias
            <input
              value={createValues.alias}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  alias: event.target.value,
                })
              }
            />
          </label>
          <label>
            Normalized alias
            <input
              value={createValues.normalizedAlias}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  normalizedAlias: event.target.value,
                })
              }
            />
          </label>
          <label>
            Source
            <input
              value={createValues.source}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  source: event.target.value,
                })
              }
            />
          </label>
          <label>
            Priority
            <input
              value={createValues.priority}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  priority: event.target.value,
                })
              }
            />
          </label>
          <label>
            Legacy ID
            <input
              value={createValues.legacyId}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  legacyId: event.target.value,
                })
              }
            />
          </label>
          <label>
            Active
            <input
              type="checkbox"
              checked={createValues.isActive}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  isActive: event.target.checked,
                })
              }
            />
          </label>
        </div>
        <button
          className="primary-button"
          type="submit"
          disabled={pendingAction === "create"}
        >
          {pendingAction === "create" ? "Creating..." : "Create team alias"}
        </button>
      </form>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading team aliases…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load team aliases"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.aliases.length === 0 ? (
        <AdminTableEmpty
          title="No team aliases found"
          message="Team aliases will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.aliases.length > 0 ? (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">Alias</th>
                <th scope="col">Normalized alias</th>
                <th scope="col">Source</th>
                <th scope="col">Team ID</th>
                <th scope="col">Sport ID</th>
                <th scope="col">Priority</th>
                <th scope="col">Legacy ID</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {state.aliases.map((alias) => (
                <tr key={alias.id}>
                  {editingId === alias.id ? (
                    <td colSpan={9}>
                      <form
                        className="inline-edit-form"
                        onSubmit={(event) =>
                          void handleEditSubmit(event, alias)
                        }
                      >
                        <label>
                          Alias
                          <input
                            aria-label="Edit alias"
                            value={editValues.alias}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                alias: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Normalized alias
                          <input
                            aria-label="Edit normalized alias"
                            value={editValues.normalizedAlias}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                normalizedAlias: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Source
                          <input
                            aria-label="Edit source"
                            value={editValues.source}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                source: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Team ID
                          <input
                            aria-label="Edit team ID"
                            value={editValues.teamId}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                teamId: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Sport ID
                          <input
                            aria-label="Edit sport ID"
                            value={editValues.sportId}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                sportId: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Priority
                          <input
                            aria-label="Edit priority"
                            value={editValues.priority}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                priority: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Legacy ID
                          <input
                            aria-label="Edit legacy ID"
                            value={editValues.legacyId}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                legacyId: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Active
                          <input
                            aria-label="Edit active"
                            type="checkbox"
                            checked={editValues.isActive}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                isActive: event.target.checked,
                              })
                            }
                          />
                        </label>
                        <div className="row-actions">
                          <button
                            className="primary-button compact-button"
                            type="submit"
                            disabled={pendingAction === `edit:${alias.id}`}
                          >
                            {pendingAction === `edit:${alias.id}`
                              ? "Saving..."
                              : "Save"}
                          </button>
                          <button
                            className="secondary-button compact-button"
                            type="button"
                            onClick={() => setEditingId(null)}
                          >
                            Cancel
                          </button>
                        </div>
                      </form>
                    </td>
                  ) : (
                    <>
                      <td>{alias.alias}</td>
                      <td>{alias.normalizedAlias || "—"}</td>
                      <td>{alias.source}</td>
                      <td>{alias.teamId}</td>
                      <td>{alias.sportId}</td>
                      <td>{alias.priority}</td>
                      <td>{alias.legacyId ?? "—"}</td>
                      <td>
                        <span
                          className={
                            alias.isActive
                              ? "status-pill status-pill-active"
                              : "status-pill status-pill-inactive"
                          }
                        >
                          {alias.isActive ? "Active" : "Inactive"}
                        </span>
                      </td>
                      <td>
                        <div className="row-actions">
                          <button
                            className="secondary-button compact-button"
                            type="button"
                            onClick={() => startEditing(alias)}
                          >
                            Edit
                          </button>
                          <button
                            className="secondary-button compact-button danger-button"
                            type="button"
                            disabled={pendingAction === `delete:${alias.id}`}
                            onClick={() => void handleDelete(alias)}
                          >
                            {pendingAction === `delete:${alias.id}`
                              ? "Deleting..."
                              : "Delete"}
                          </button>
                        </div>
                      </td>
                    </>
                  )}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      ) : null}
    </section>
  );
}
