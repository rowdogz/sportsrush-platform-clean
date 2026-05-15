import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  archiveTeam,
  createTeam,
  listTeams,
  updateTeam,
} from "../features/teams/api";
import type { AdminTeam, TeamWritePayload } from "../features/teams/types";
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

type FeedbackState =
  | { readonly type: "success"; readonly message: string }
  | { readonly type: "error"; readonly message: string };

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
      setFeedback({ type: "error", message: validationMessage });
      return;
    }

    setPendingAction("create");
    try {
      await createTeam(toWritePayload(createValues));
      await loadTeams();
      setCreateValues(emptyFormValues);
      setFeedback({ type: "success", message: "Team created." });
    } catch (error) {
      setFeedback({ type: "error", message: getErrorMessage(error) });
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
      setFeedback({ type: "error", message: validationMessage });
      return;
    }

    setPendingAction(`edit:${team.id}`);
    try {
      await updateTeam(team.id, toWritePayload(editValues));
      await loadTeams();
      setEditingId(null);
      setFeedback({ type: "success", message: "Team updated." });
    } catch (error) {
      setFeedback({ type: "error", message: getErrorMessage(error) });
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
      setFeedback({ type: "success", message: "Team archived." });
    } catch (error) {
      setFeedback({ type: "error", message: getErrorMessage(error) });
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

      <form className="admin-form" onSubmit={handleCreateSubmit}>
        <div className="form-heading">
          <h3>Create team</h3>
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
        <button
          className="primary-button"
          type="submit"
          disabled={pendingAction === "create"}
        >
          {pendingAction === "create" ? "Creating..." : "Create team"}
        </button>
      </form>

      {feedback ? (
        <div
          className={
            feedback.type === "success"
              ? "feedback-panel success-panel"
              : "feedback-panel error-panel"
          }
          role={feedback.type === "success" ? "status" : "alert"}
        >
          {feedback.message}
        </div>
      ) : null}

      {state.status === "loading" ? (
        <div className="state-panel" role="status">
          Loading teams…
        </div>
      ) : null}

      {state.status === "error" ? (
        <div className="state-panel error-panel" role="alert">
          <strong>Unable to load teams</strong>
          <span>{state.message}</span>
        </div>
      ) : null}

      {state.status === "success" && state.teams.length === 0 ? (
        <div className="state-panel">
          <strong>No teams found</strong>
          <span>Teams will appear here after they are added.</span>
        </div>
      ) : null}

      {state.status === "success" && state.teams.length > 0 ? (
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
                        onSubmit={(event) => void handleEditSubmit(event, team)}
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
      ) : null}
    </section>
  );
}
