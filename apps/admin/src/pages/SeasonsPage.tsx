import { useCallback, useEffect, useState, type FormEvent } from "react";
import { listCompetitions } from "../features/competitions/api";
import type { AdminCompetition } from "../features/competitions/types";
import {
  activateSeason,
  createSeason,
  listSeasons,
  updateSeason,
} from "../features/seasons/api";
import type {
  AdminSeason,
  SeasonListFilters,
  SeasonWritePayload,
} from "../features/seasons/types";
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
  mergeStoredObject,
  usePersistedAdminState,
} from "../hooks/usePersistedAdminState";
import { ApiError } from "../lib/apiClient";

type SeasonsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly seasons: readonly AdminSeason[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly competitionId: string;
  readonly slug: string;
  readonly name: string;
  readonly startsOn: string;
  readonly endsOn: string;
  readonly legacyId: string;
  readonly isActive: boolean;
};

type FilterValues = {
  readonly competitionId: string;
  readonly search: string;
};

type FeedbackState = AdminFeedbackState;

const emptyFormValues: FormValues = {
  competitionId: "",
  slug: "",
  name: "",
  startsOn: "",
  endsOn: "",
  legacyId: "",
  isActive: true,
};

const emptyFilterValues: FilterValues = {
  competitionId: "",
  search: "",
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load seasons.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function getRequiredValidationMessage(values: FormValues): string | null {
  if (
    !values.competitionId.trim() ||
    !values.slug.trim() ||
    !values.name.trim()
  ) {
    return "Competition, slug, and name are required.";
  }

  return null;
}

function toFilters(values: FilterValues): SeasonListFilters {
  return {
    competitionId: toOptionalValue(values.competitionId),
    search: toOptionalValue(values.search),
  };
}

function toWritePayload(values: FormValues): SeasonWritePayload {
  return {
    competitionId: values.competitionId.trim(),
    slug: values.slug.trim(),
    name: values.name.trim(),
    startsOn: toOptionalValue(values.startsOn),
    endsOn: toOptionalValue(values.endsOn),
    legacyId: toOptionalValue(values.legacyId),
    isActive: values.isActive,
  };
}

function toFormValues(season: AdminSeason): FormValues {
  return {
    competitionId: season.competitionId,
    slug: season.slug,
    name: season.name,
    startsOn: season.startsOn ?? "",
    endsOn: season.endsOn ?? "",
    legacyId: season.legacyId ?? "",
    isActive: season.isActive,
  };
}

function getCompetitionName(
  competitions: readonly AdminCompetition[],
  id: string,
): string {
  return competitions.find((competition) => competition.id === id)?.name ?? id;
}

export function SeasonsPage() {
  const [state, setState] = useState<SeasonsState>({ status: "loading" });
  const [competitions, setCompetitions] = useState<readonly AdminCompetition[]>(
    [],
  );
  const [filters, setFilters] = usePersistedAdminState(
    "sr-admin:seasons:filters",
    emptyFilterValues,
    mergeStoredObject(emptyFilterValues),
  );
  const [activeFilters, setActiveFilters] = usePersistedAdminState(
    "sr-admin:seasons:active-filters",
    emptyFilterValues,
    mergeStoredObject(emptyFilterValues),
  );
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);

  const loadSeasons = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listSeasons(toFilters(nextFilters));
        setState({ status: "success", seasons: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    async function loadInitialData() {
      const [competitionsResponse, seasonsResponse] = await Promise.all([
        listCompetitions(),
        listSeasons(toFilters(activeFilters)),
      ]);
      setCompetitions(competitionsResponse.data);
      setState({ status: "success", seasons: seasonsResponse.data });
    }

    setState({ status: "loading" });
    void loadInitialData().catch((error: unknown) => {
      setState({ status: "error", message: getErrorMessage(error) });
    });
  }, []);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);
    setActiveFilters(filters);
    await loadSeasons(filters, { showLoading: true });
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
      await createSeason(toWritePayload(createValues));
      await loadSeasons(activeFilters);
      setCreateValues({
        ...emptyFormValues,
        competitionId: createValues.competitionId,
      });
      setFeedback(adminSuccessToast("Season created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(season: AdminSeason) {
    setFeedback(null);
    setEditingId(season.id);
    setEditValues(toFormValues(season));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    season: AdminSeason,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${season.id}`);
    try {
      await updateSeason(season.id, toWritePayload(editValues));
      await loadSeasons(activeFilters);
      setEditingId(null);
      setFeedback(adminSuccessToast("Season updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleActivate(season: AdminSeason) {
    setFeedback(null);
    setPendingAction(`activate:${season.id}`);
    try {
      await activateSeason(season);
      await loadSeasons(activeFilters);
      setFeedback(adminSuccessToast("Season activated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="seasons-title">
      <div className="page-heading">
        <h2 id="seasons-title">Seasons</h2>
        <p>Manage competition seasons configured in SportsRush.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter seasons</h3>
        </div>
        <div className="form-grid">
          <label>
            Filter competition
            <select
              value={filters.competitionId}
              onChange={(event) =>
                setFilters({ ...filters, competitionId: event.target.value })
              }
            >
              <option value="">All competitions</option>
              {competitions.map((competition) => (
                <option key={competition.id} value={competition.id}>
                  {competition.name}
                </option>
              ))}
            </select>
          </label>
          <label>
            Search
            <input
              value={filters.search}
              onChange={(event) =>
                setFilters({ ...filters, search: event.target.value })
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
          <h3>Create season</h3>
        </div>
        <div className="form-grid">
          <label>
            Competition
            <select
              value={createValues.competitionId}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  competitionId: event.target.value,
                })
              }
            >
              <option value="">Select competition</option>
              {competitions.map((competition) => (
                <option key={competition.id} value={competition.id}>
                  {competition.name}
                </option>
              ))}
            </select>
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
            Starts on
            <input
              value={createValues.startsOn}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  startsOn: event.target.value,
                })
              }
            />
          </label>
          <label>
            Ends on
            <input
              value={createValues.endsOn}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  endsOn: event.target.value,
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
          <label className="checkbox-label">
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
            Active
          </label>
        </div>
        <button
          className="primary-button"
          type="submit"
          disabled={pendingAction === "create"}
        >
          {pendingAction === "create" ? "Creating..." : "Create season"}
        </button>
      </form>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading seasons…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load seasons"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.seasons.length === 0 ? (
        <AdminTableEmpty
          title="No seasons found"
          message="Seasons will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.seasons.length > 0 ? (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">Season</th>
                <th scope="col">Competition</th>
                <th scope="col">Slug</th>
                <th scope="col">Dates</th>
                <th scope="col">Status</th>
                <th scope="col">Legacy ID</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {state.seasons.map((season) => (
                <tr key={season.id}>
                  {editingId === season.id ? (
                    <td colSpan={7}>
                      <form
                        className="inline-edit-form"
                        onSubmit={(event) =>
                          void handleEditSubmit(event, season)
                        }
                      >
                        <label>
                          Competition
                          <select
                            aria-label="Edit competition"
                            value={editValues.competitionId}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                competitionId: event.target.value,
                              })
                            }
                          >
                            {competitions.map((competition) => (
                              <option
                                key={competition.id}
                                value={competition.id}
                              >
                                {competition.name}
                              </option>
                            ))}
                          </select>
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
                          Starts on
                          <input
                            aria-label="Edit starts on"
                            value={editValues.startsOn}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                startsOn: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Ends on
                          <input
                            aria-label="Edit ends on"
                            value={editValues.endsOn}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                endsOn: event.target.value,
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
                        <label className="checkbox-label">
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
                          Active
                        </label>
                        <div className="row-actions">
                          <button
                            className="primary-button compact-button"
                            type="submit"
                            disabled={pendingAction === `edit:${season.id}`}
                          >
                            {pendingAction === `edit:${season.id}`
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
                      <td>{season.name}</td>
                      <td>
                        {getCompetitionName(competitions, season.competitionId)}
                      </td>
                      <td>{season.slug}</td>
                      <td>
                        {season.startsOn ?? "—"} to {season.endsOn ?? "—"}
                      </td>
                      <td>
                        <span
                          className={
                            season.isActive
                              ? "status-pill status-pill-active"
                              : "status-pill status-pill-inactive"
                          }
                        >
                          {season.isActive ? "active" : "inactive"}
                        </span>
                      </td>
                      <td>{season.legacyId ?? "—"}</td>
                      <td>
                        <div className="row-actions">
                          <button
                            className="secondary-button compact-button"
                            type="button"
                            onClick={() => startEditing(season)}
                          >
                            Edit
                          </button>
                          <button
                            className="secondary-button compact-button"
                            type="button"
                            disabled={
                              season.isActive ||
                              pendingAction === `activate:${season.id}`
                            }
                            onClick={() => void handleActivate(season)}
                          >
                            {pendingAction === `activate:${season.id}`
                              ? "Activating..."
                              : "Activate"}
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
