import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  archiveTeam,
  createTeam,
  listTeams,
  updateTeam,
} from "../features/teams/api";
import type { AdminTeam, TeamWritePayload } from "../features/teams/types";
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
import { ApiError } from "../lib/apiClient";

type TeamsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly teams: readonly AdminTeam[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName: string;
  readonly displayName: string;
  readonly countryCode: string;
  readonly legacyId: string;
};

type FeedbackState = AdminFeedbackState;

const emptyFormValues: FormValues = {
  sportId: "",
  slug: "",
  name: "",
  shortName: "",
  displayName: "",
  countryCode: "",
  legacyId: "",
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load teams.";
}

function getRequiredValidationMessage(values: FormValues): string | null {
  if (!values.sportId.trim() || !values.slug.trim() || !values.name.trim()) {
    return "Sport ID, slug, and name are required.";
  }

  return null;
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function toWritePayload(values: FormValues): TeamWritePayload {
  return {
    sportId: values.sportId.trim(),
    slug: values.slug.trim(),
    name: values.name.trim(),
    shortName: toOptionalValue(values.shortName),
    displayName: toOptionalValue(values.displayName),
    countryCode: toOptionalValue(values.countryCode),
    legacyId: toOptionalValue(values.legacyId),
  };
}

function toFormValues(team: AdminTeam): FormValues {
  return {
    sportId: team.sportId,
    slug: team.slug,
    name: team.name,
    shortName: team.shortName ?? "",
    displayName: team.displayName ?? "",
    countryCode: team.countryCode ?? "",
    legacyId: team.legacyId ?? "",
  };
}

export function TeamsPage() {
  const [state, setState] = useState<TeamsState>({ status: "loading" });
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);
  const teamMetrics =
    state.status === "success"
      ? [
          {
            label: "Teams",
            value: state.teams.length,
            description: "Visible team records",
          },
          {
            label: "Active records",
            value: state.teams.filter((team) => team.isActive).length,
            description: "Currently available in SportsRush",
          },
          {
            label: "Display names",
            value: state.teams.filter((team) => team.displayName).length,
            description: "Configured for richer public presentation",
          },
          {
            label: "Legacy linked",
            value: state.teams.filter((team) => team.legacyId).length,
            description: "Mapped to imported or source data",
          },
        ]
      : [];

  const loadTeams = useCallback(
    async ({
      showLoading = false,
    }: { readonly showLoading?: boolean } = {}) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listTeams();
        setState({ status: "success", teams: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadTeams({ showLoading: true });
  }, [loadTeams]);

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
      await createTeam(toWritePayload(createValues));
      await loadTeams();
      setCreateValues(emptyFormValues);
      setFeedback(adminSuccessToast("Team created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(team: AdminTeam) {
    setFeedback(null);
    setEditingId(team.id);
    setEditValues(toFormValues(team));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    team: AdminTeam,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${team.id}`);
    try {
      await updateTeam(team.id, toWritePayload(editValues));
      await loadTeams();
      setEditingId(null);
      setFeedback(adminSuccessToast("Team updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleArchive(team: AdminTeam) {
    setFeedback(null);
    if (!window.confirm(`Archive ${team.name}?`)) {
      return;
    }

    setPendingAction(`archive:${team.id}`);
    try {
      await archiveTeam(team.id);
      await loadTeams();
      setFeedback(adminSuccessToast("Team archived."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="teams-title">
      <div className="page-heading">
        <h2 id="teams-title">Teams</h2>
        <p>Manage teams configured in SportsRush.</p>
      </div>

      {teamMetrics.length > 0 ? (
        <div className="ops-summary-grid" aria-label="Team overview">
          {teamMetrics.map((metric) => (
            <article className="ops-summary-card" key={metric.label}>
              <span>{metric.label}</span>
              <strong>{metric.value}</strong>
              <p>{metric.description}</p>
            </article>
          ))}
        </div>
      ) : null}

      <div className="ops-stage-grid">
        <form
          className="admin-form ops-section-card"
          onSubmit={handleCreateSubmit}
        >
          <div className="form-heading ops-section-card-header">
            <div>
              <h3>Create team</h3>
              <p>
                Set team naming, source mapping, and display data in one flow.
              </p>
            </div>
          </div>
          <div className="form-grid">
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
              Slug
              <input
                value={createValues.slug}
                onChange={(event) =>
                  setCreateValues({ ...createValues, slug: event.target.value })
                }
              />
            </label>
            <label>
              Name
              <input
                value={createValues.name}
                onChange={(event) =>
                  setCreateValues({ ...createValues, name: event.target.value })
                }
              />
            </label>
            <label>
              Short name
              <input
                value={createValues.shortName}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    shortName: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Display name
              <input
                value={createValues.displayName}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    displayName: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Country code
              <input
                value={createValues.countryCode}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    countryCode: event.target.value,
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
          </div>
          <div className="form-actions">
            <button
              className="primary-button"
              type="submit"
              disabled={pendingAction === "create"}
            >
              {pendingAction === "create" ? "Creating..." : "Create team"}
            </button>
          </div>
        </form>

        <aside
          className="ops-section-card ops-guidance-panel"
          aria-label="Team workflow guidance"
        >
          <div className="ops-section-card-header">
            <div>
              <h3>Operational notes</h3>
              <p>
                Keep team identity data stable for aliases, fixtures, and public
                presentation.
              </p>
            </div>
          </div>
          <ul className="ops-guidance-list">
            <li>
              Use display names when the public-facing presentation differs from
              the canonical team name.
            </li>
            <li>
              Keep short names compact for fixtures, rankings, and mobile
              layouts.
            </li>
            <li>
              Archive only when downstream aliases and fixtures no longer
              require the record.
            </li>
          </ul>
        </aside>
      </div>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading teams…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError title="Unable to load teams" message={state.message} />
      ) : null}

      {state.status === "success" && state.teams.length === 0 ? (
        <AdminTableEmpty
          title="No teams found"
          message="Teams will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.teams.length > 0 ? (
        <section
          className="ops-section-card"
          aria-labelledby="teams-table-title"
        >
          <div className="ops-table-toolbar">
            <div>
              <h3 id="teams-table-title">Team records</h3>
              <p>
                Maintain operational naming and archive inactive teams from the
                main table.
              </p>
            </div>
          </div>
          <div className="admin-table-wrapper">
            <table className="admin-table">
              <thead>
                <tr>
                  <th scope="col">Name</th>
                  <th scope="col">Display name</th>
                  <th scope="col">Slug</th>
                  <th scope="col">Sport ID</th>
                  <th scope="col">Short name</th>
                  <th scope="col">Country</th>
                  <th scope="col">Legacy ID</th>
                  <th scope="col">Status</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                {state.teams.map((team) => (
                  <tr key={team.id}>
                    {editingId === team.id ? (
                      <td colSpan={9}>
                        <form
                          className="inline-edit-form"
                          onSubmit={(event) =>
                            void handleEditSubmit(event, team)
                          }
                        >
                          <label>
                            Name
                            <input
                              aria-label="Edit name"
                              value={editValues.name}
                              onChange={(event) =>
                                setEditValues({
                                  ...editValues,
                                  name: event.target.value,
                                })
                              }
                            />
                          </label>
                          <label>
                            Display name
                            <input
                              aria-label="Edit display name"
                              value={editValues.displayName}
                              onChange={(event) =>
                                setEditValues({
                                  ...editValues,
                                  displayName: event.target.value,
                                })
                              }
                            />
                          </label>
                          <label>
                            Slug
                            <input
                              aria-label="Edit slug"
                              value={editValues.slug}
                              onChange={(event) =>
                                setEditValues({
                                  ...editValues,
                                  slug: event.target.value,
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
                            Short name
                            <input
                              aria-label="Edit short name"
                              value={editValues.shortName}
                              onChange={(event) =>
                                setEditValues({
                                  ...editValues,
                                  shortName: event.target.value,
                                })
                              }
                            />
                          </label>
                          <label>
                            Country code
                            <input
                              aria-label="Edit country code"
                              value={editValues.countryCode}
                              onChange={(event) =>
                                setEditValues({
                                  ...editValues,
                                  countryCode: event.target.value,
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
                          <div className="row-actions">
                            <button
                              className="primary-button compact-button"
                              type="submit"
                              disabled={pendingAction === `edit:${team.id}`}
                            >
                              {pendingAction === `edit:${team.id}`
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
                        <td>{team.name}</td>
                        <td>{team.displayName ?? "—"}</td>
                        <td>{team.slug}</td>
                        <td>{team.sportId}</td>
                        <td>{team.shortName ?? "—"}</td>
                        <td>{team.countryCode ?? "—"}</td>
                        <td>{team.legacyId ?? "—"}</td>
                        <td>
                          <span
                            className={
                              team.isActive
                                ? "status-pill status-pill-active"
                                : "status-pill status-pill-inactive"
                            }
                          >
                            {team.isActive ? "Active" : "Inactive"}
                          </span>
                        </td>
                        <td>
                          <div className="row-actions">
                            <button
                              className="secondary-button compact-button"
                              type="button"
                              onClick={() => startEditing(team)}
                            >
                              Edit
                            </button>
                            <button
                              className="secondary-button compact-button danger-button"
                              type="button"
                              disabled={pendingAction === `archive:${team.id}`}
                              onClick={() => void handleArchive(team)}
                            >
                              {pendingAction === `archive:${team.id}`
                                ? "Archiving..."
                                : "Archive"}
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
        </section>
      ) : null}
    </section>
  );
}
