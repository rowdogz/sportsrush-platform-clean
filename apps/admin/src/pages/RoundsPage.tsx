import { useCallback, useEffect, useState, type FormEvent } from "react";
import { createRound, listRounds, updateRound } from "../features/rounds/api";
import type {
  AdminRound,
  RoundListFilters,
  RoundWritePayload,
} from "../features/rounds/types";
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

type RoundsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly rounds: readonly AdminRound[];
    }
  | { readonly status: "error"; readonly message: string };

type FormValues = {
  readonly seasonId: string;
  readonly round: string;
  readonly roundName: string;
  readonly displayOrder: string;
  readonly startsAt: string;
  readonly endsAt: string;
  readonly legacyId: string;
};

type FilterValues = {
  readonly seasonId: string;
};

type FeedbackState = AdminFeedbackState;

const defaultSeasonId = "season-current";

const emptyFormValues: FormValues = {
  seasonId: defaultSeasonId,
  round: "",
  roundName: "",
  displayOrder: "0",
  startsAt: "",
  endsAt: "",
  legacyId: "",
};

const emptyFilterValues: FilterValues = {
  seasonId: defaultSeasonId,
};

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load rounds.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function getRequiredValidationMessage(values: FormValues): string | null {
  if (
    !values.seasonId.trim() ||
    !values.round.trim() ||
    !values.roundName.trim() ||
    !values.displayOrder.trim()
  ) {
    return "Season ID, round, round name, and display order are required.";
  }

  if (!Number.isInteger(Number(values.displayOrder))) {
    return "Display order must be an integer.";
  }

  return null;
}

function getFilterValidationMessage(values: FilterValues): string | null {
  if (!values.seasonId.trim()) {
    return "Season ID is required to load rounds.";
  }

  return null;
}

function toFilters(values: FilterValues): RoundListFilters {
  return {
    seasonId: values.seasonId.trim(),
  };
}

function toWritePayload(values: FormValues): RoundWritePayload {
  return {
    seasonId: values.seasonId.trim(),
    round: values.round.trim(),
    roundName: values.roundName.trim(),
    displayOrder: Number(values.displayOrder),
    startsAt: toOptionalValue(values.startsAt),
    endsAt: toOptionalValue(values.endsAt),
    legacyId: toOptionalValue(values.legacyId),
  };
}

function toFormValues(round: AdminRound): FormValues {
  return {
    seasonId: round.seasonId,
    round: round.round,
    roundName: round.roundName,
    displayOrder: String(round.displayOrder),
    startsAt: round.startsAt ?? "",
    endsAt: round.endsAt ?? "",
    legacyId: round.legacyId ?? "",
  };
}

export function RoundsPage() {
  const [state, setState] = useState<RoundsState>({ status: "loading" });
  const [filters, setFilters] = useAdminSearchParams({
    storageKey: "sr-admin:rounds:filters",
    defaults: emptyFilterValues,
    paramKeys: ["seasonId"],
    parse: (params, fallback) => ({
      seasonId: readStringParam(params, "seasonId", fallback.seasonId),
    }),
    serialize: (value, defaults) => {
      const params = new URLSearchParams();
      appendStringParam(params, "seasonId", value.seasonId, defaults.seasonId);
      return params;
    },
  });
  const [activeFilters, setActiveFilters] = useState<FilterValues>(filters);
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);

  const loadRounds = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listRounds(toFilters(nextFilters));
        setState({ status: "success", rounds: response.data });
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    void loadRounds(activeFilters, { showLoading: true });
  }, [loadRounds]);

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getFilterValidationMessage(filters);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setActiveFilters(filters);
    await loadRounds(filters, { showLoading: true });
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
      await createRound(toWritePayload(createValues));
      await loadRounds(activeFilters);
      setCreateValues({ ...emptyFormValues, seasonId: createValues.seasonId });
      setFeedback(adminSuccessToast("Round created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(round: AdminRound) {
    setFeedback(null);
    setEditingId(round.id);
    setEditValues(toFormValues(round));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    round: AdminRound,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${round.id}`);
    try {
      await updateRound(round.id, toWritePayload(editValues));
      await loadRounds(activeFilters);
      setEditingId(null);
      setFeedback(adminSuccessToast("Round updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="rounds-title">
      <div className="page-heading">
        <h2 id="rounds-title">Rounds</h2>
        <p>Manage competition season rounds configured in SportsRush.</p>
      </div>

      <form className="admin-form" onSubmit={handleFilterSubmit}>
        <div className="form-heading">
          <h3>Filter rounds</h3>
        </div>
        <div className="form-grid">
          <label>
            Filter season ID
            <input
              value={filters.seasonId}
              onChange={(event) =>
                setFilters({ ...filters, seasonId: event.target.value })
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
          <h3>Create round</h3>
        </div>
        <div className="form-grid">
          <label>
            Season ID
            <input
              value={createValues.seasonId}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  seasonId: event.target.value,
                })
              }
            />
          </label>
          <label>
            Round
            <input
              value={createValues.round}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  round: event.target.value,
                })
              }
            />
          </label>
          <label>
            Round name
            <input
              value={createValues.roundName}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  roundName: event.target.value,
                })
              }
            />
          </label>
          <label>
            Display order
            <input
              value={createValues.displayOrder}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  displayOrder: event.target.value,
                })
              }
            />
          </label>
          <label>
            Starts at
            <input
              value={createValues.startsAt}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  startsAt: event.target.value,
                })
              }
            />
          </label>
          <label>
            Ends at
            <input
              value={createValues.endsAt}
              onChange={(event) =>
                setCreateValues({
                  ...createValues,
                  endsAt: event.target.value,
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
          {pendingAction === "create" ? "Creating..." : "Create round"}
        </button>
      </form>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading rounds…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load rounds"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.rounds.length === 0 ? (
        <AdminTableEmpty
          title="No rounds found"
          message="Rounds will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.rounds.length > 0 ? (
        <div className="admin-table-wrapper">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">Round</th>
                <th scope="col">Round name</th>
                <th scope="col">Season ID</th>
                <th scope="col">Display order</th>
                <th scope="col">Starts at</th>
                <th scope="col">Ends at</th>
                <th scope="col">Legacy ID</th>
                <th scope="col">Actions</th>
              </tr>
            </thead>
            <tbody>
              {state.rounds.map((round) => (
                <tr key={round.id}>
                  {editingId === round.id ? (
                    <td colSpan={8}>
                      <form
                        className="inline-edit-form"
                        onSubmit={(event) =>
                          void handleEditSubmit(event, round)
                        }
                      >
                        <label>
                          Round
                          <input
                            aria-label="Edit round"
                            value={editValues.round}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                round: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Round name
                          <input
                            aria-label="Edit round name"
                            value={editValues.roundName}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                roundName: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Season ID
                          <input
                            aria-label="Edit season ID"
                            value={editValues.seasonId}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                seasonId: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Display order
                          <input
                            aria-label="Edit display order"
                            value={editValues.displayOrder}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                displayOrder: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Starts at
                          <input
                            aria-label="Edit starts at"
                            value={editValues.startsAt}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                startsAt: event.target.value,
                              })
                            }
                          />
                        </label>
                        <label>
                          Ends at
                          <input
                            aria-label="Edit ends at"
                            value={editValues.endsAt}
                            onChange={(event) =>
                              setEditValues({
                                ...editValues,
                                endsAt: event.target.value,
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
                            disabled={pendingAction === `edit:${round.id}`}
                          >
                            {pendingAction === `edit:${round.id}`
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
                      <td>{round.round}</td>
                      <td>{round.roundName}</td>
                      <td>{round.seasonId}</td>
                      <td>{round.displayOrder}</td>
                      <td>{round.startsAt ?? "—"}</td>
                      <td>{round.endsAt ?? "—"}</td>
                      <td>{round.legacyId ?? "—"}</td>
                      <td>
                        <div className="row-actions">
                          <button
                            className="secondary-button compact-button"
                            type="button"
                            onClick={() => startEditing(round)}
                          >
                            Edit
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
