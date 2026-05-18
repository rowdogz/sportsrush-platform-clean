import { useCallback, useEffect, useState, type FormEvent } from "react";
import {
  archiveCompetition,
  createCompetition,
  listCompetitions,
  updateCompetition,
} from "../features/competitions/api";
import type {
  AdminCompetition,
  CompetitionWritePayload,
} from "../features/competitions/types";
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

type CompetitionsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly competitions: readonly AdminCompetition[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly sportId: string;
  readonly slug: string;
  readonly name: string;
  readonly shortName: string;
  readonly countryCode: string;
  readonly legacyId: string;
};

type FeedbackState = AdminFeedbackState;

const emptyFormValues: FormValues = {
  sportId: "",
  slug: "",
  name: "",
  shortName: "",
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

  return "Unable to load competitions.";
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

function toWritePayload(values: FormValues): CompetitionWritePayload {
  return {
    sportId: values.sportId.trim(),
    slug: values.slug.trim(),
    name: values.name.trim(),
    shortName: toOptionalValue(values.shortName),
    countryCode: toOptionalValue(values.countryCode),
    legacyId: toOptionalValue(values.legacyId),
  };
}

function toFormValues(competition: AdminCompetition): FormValues {
  return {
    sportId: competition.sportId,
    slug: competition.slug,
    name: competition.name,
    shortName: competition.shortName ?? "",
    countryCode: competition.countryCode ?? "",
    legacyId: competition.legacyId ?? "",
  };
}

export function CompetitionsPage() {
  const [state, setState] = useState<CompetitionsState>({
    status: "loading",
  });
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);
  const competitionMetrics =
    state.status === "success"
      ? [
          {
            label: "Competitions",
            value: state.competitions.length,
            description: "Visible competition records",
          },
          {
            label: "Active records",
            value: state.competitions.filter(
              (competition) => competition.isActive,
            ).length,
            description: "Currently available in SportsRush",
          },
          {
            label: "Inactive",
            value: state.competitions.filter(
              (competition) => !competition.isActive,
            ).length,
            description: "Archived or disabled records",
          },
          {
            label: "Legacy linked",
            value: state.competitions.filter(
              (competition) => competition.legacyId,
            ).length,
            description: "Mapped to legacy source IDs",
          },
        ]
      : [];

  const loadCompetitions = useCallback(
    async ({
      showLoading = false,
    }: { readonly showLoading?: boolean } = {}) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listCompetitions();
        setState({ status: "success", competitions: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadCompetitions({ showLoading: true });
  }, [loadCompetitions]);

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
      await createCompetition(toWritePayload(createValues));
      await loadCompetitions();
      setCreateValues(emptyFormValues);
      setFeedback(adminSuccessToast("Competition created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(competition: AdminCompetition) {
    setFeedback(null);
    setEditingId(competition.id);
    setEditValues(toFormValues(competition));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    competition: AdminCompetition,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${competition.id}`);
    try {
      await updateCompetition(competition.id, toWritePayload(editValues));
      await loadCompetitions();
      setEditingId(null);
      setFeedback(adminSuccessToast("Competition updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleArchive(competition: AdminCompetition) {
    setFeedback(null);
    if (!window.confirm(`Archive ${competition.name}?`)) {
      return;
    }

    setPendingAction(`archive:${competition.id}`);
    try {
      await archiveCompetition(competition.id);
      await loadCompetitions();
      setFeedback(adminSuccessToast("Competition archived."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="competitions-title">
      <div className="page-heading">
        <h2 id="competitions-title">Competitions</h2>
        <p>Manage competitions configured in SportsRush.</p>
      </div>

      {competitionMetrics.length > 0 ? (
        <div className="ops-summary-grid" aria-label="Competition overview">
          {competitionMetrics.map((metric) => (
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
          className="competition-form ops-section-card"
          onSubmit={handleCreateSubmit}
        >
          <div className="form-heading ops-section-card-header">
            <div>
              <h3>Create competition</h3>
              <p>
                Add a new competition record without leaving the operational
                list.
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
              {pendingAction === "create"
                ? "Creating..."
                : "Create competition"}
            </button>
          </div>
        </form>

        <aside
          className="ops-section-card ops-guidance-panel"
          aria-label="Competition workflow guidance"
        >
          <div className="ops-section-card-header">
            <div>
              <h3>Operational notes</h3>
              <p>
                Keep competition records consistent for downstream seasons,
                rounds, and fixtures.
              </p>
            </div>
          </div>
          <ul className="ops-guidance-list">
            <li>
              Use stable slugs because downstream references depend on them.
            </li>
            <li>Set short names for compact table and fixture displays.</li>
            <li>
              Link legacy IDs where migration or scraper mapping already exists.
            </li>
          </ul>
        </aside>
      </div>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading competitions…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load competitions"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.competitions.length === 0 ? (
        <AdminTableEmpty
          title="No competitions found"
          message="Competitions will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.competitions.length > 0 ? (
        <section
          className="ops-section-card"
          aria-labelledby="competitions-table-title"
        >
          <div className="ops-table-toolbar">
            <div>
              <h3 id="competitions-table-title">Competition records</h3>
              <p>
                Review status, make inline changes, and archive stale
                competitions.
              </p>
            </div>
          </div>
          <div className="competitions-table-wrapper">
            <table className="competitions-table">
              <thead>
                <tr>
                  <th scope="col">Name</th>
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
                {state.competitions.map((competition) => (
                  <tr key={competition.id}>
                    {editingId === competition.id ? (
                      <td colSpan={8}>
                        <form
                          className="inline-edit-form"
                          onSubmit={(event) =>
                            void handleEditSubmit(event, competition)
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
                              disabled={
                                pendingAction === `edit:${competition.id}`
                              }
                            >
                              {pendingAction === `edit:${competition.id}`
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
                        <td>{competition.name}</td>
                        <td>{competition.slug}</td>
                        <td>{competition.sportId}</td>
                        <td>{competition.shortName ?? "—"}</td>
                        <td>{competition.countryCode ?? "—"}</td>
                        <td>{competition.legacyId ?? "—"}</td>
                        <td>
                          <span
                            className={
                              competition.isActive
                                ? "status-pill status-pill-active"
                                : "status-pill status-pill-inactive"
                            }
                          >
                            {competition.isActive ? "Active" : "Inactive"}
                          </span>
                        </td>
                        <td>
                          <div className="row-actions">
                            <button
                              className="secondary-button compact-button"
                              type="button"
                              onClick={() => startEditing(competition)}
                            >
                              Edit
                            </button>
                            <button
                              className="secondary-button compact-button danger-button"
                              type="button"
                              disabled={
                                pendingAction === `archive:${competition.id}`
                              }
                              onClick={() => void handleArchive(competition)}
                            >
                              {pendingAction === `archive:${competition.id}`
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
