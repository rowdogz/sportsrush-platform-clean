import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from "react";
import {
  correctFixtureResult,
  listFixtures,
  recalculateFixturePredictionScores,
} from "../features/fixtures/api";
import type { AdminFixture } from "../features/fixtures/types";
import { listTeams } from "../features/teams/api";
import type { AdminTeam } from "../features/teams/types";
import { listTeamAliases } from "../features/team-aliases/api";
import type { AdminTeamAlias } from "../features/team-aliases/types";
import { listAuditEvents } from "../features/audit-events/api";
import type { AdminAuditEvent } from "../features/audit-events/types";
import { useAuthSession } from "../contexts/AuthSessionProvider";
import { canViewAuditLog } from "../lib/adminPermissions";
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

type OperationsState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly scheduledFixtures: readonly AdminFixture[];
      readonly postponedFixtures: readonly AdminFixture[];
      readonly scoredFixtures: readonly AdminFixture[];
      readonly teams: readonly AdminTeam[];
      readonly scoringAudit: readonly AdminAuditEvent[];
    }
  | { readonly status: "error"; readonly message: string };

type AliasLookupState =
  | { readonly status: "idle"; readonly aliases: readonly AdminTeamAlias[] }
  | { readonly status: "loading"; readonly aliases: readonly AdminTeamAlias[] }
  | { readonly status: "success"; readonly aliases: readonly AdminTeamAlias[] }
  | {
      readonly status: "error";
      readonly aliases: readonly AdminTeamAlias[];
      readonly message: string;
    };

type AliasLookupValues = {
  readonly sportId: string;
  readonly source: string;
  readonly alias: string;
};

type CorrectionValues = {
  readonly homeScore: string;
  readonly awayScore: string;
  readonly reason: string;
};

type FeedbackState = AdminFeedbackState;

const defaultSportId = "sport-rugby-league";

const emptyAliasLookupValues: AliasLookupValues = {
  sportId: defaultSportId,
  source: "manual",
  alias: "",
};

const scoringAuditActions = new Set([
  "fixture.result.enter",
  "fixture.result.correct",
  "fixture.status.transition",
]);

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load operations data.";
}

function formatDateTime(value: string | null): string {
  if (!value) return "—";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}

function getTeamName(teams: readonly AdminTeam[], teamId: string): string {
  return teams.find((team) => team.id === teamId)?.name ?? teamId;
}

function fixtureLabel(
  fixture: AdminFixture,
  teams: readonly AdminTeam[],
): string {
  return `${getTeamName(teams, fixture.homeTeamId)} v ${getTeamName(
    teams,
    fixture.awayTeamId,
  )}`;
}

function fixtureScore(fixture: AdminFixture): string {
  if (fixture.homeScore === null || fixture.awayScore === null) return "—";
  return `${fixture.homeScore}-${fixture.awayScore}`;
}

function compareByScheduledAtDesc(
  firstFixture: AdminFixture,
  secondFixture: AdminFixture,
): number {
  return secondFixture.scheduledAt.localeCompare(firstFixture.scheduledAt);
}

function isStaleFixture(fixture: AdminFixture, now: number): boolean {
  if (fixture.status !== "scheduled" && fixture.status !== "postponed") {
    return false;
  }

  const scheduledAt = new Date(fixture.scheduledAt).getTime();
  return Number.isFinite(scheduledAt) && scheduledAt < now;
}

function makeCorrectionValues(fixture: AdminFixture): CorrectionValues {
  return {
    homeScore: fixture.homeScore === null ? "" : String(fixture.homeScore),
    awayScore: fixture.awayScore === null ? "" : String(fixture.awayScore),
    reason: "",
  };
}

function getScoreValidationMessage(
  homeScore: string,
  awayScore: string,
): string | null {
  if (!homeScore.trim() || !awayScore.trim()) {
    return "Home score and away score are required.";
  }

  if (
    !Number.isInteger(Number(homeScore)) ||
    !Number.isInteger(Number(awayScore))
  ) {
    return "Scores must be integers.";
  }

  return null;
}

export function OperationsPage() {
  const { userRole } = useAuthSession();
  const canReadAuditLog = canViewAuditLog(userRole);
  const [state, setState] = useState<OperationsState>({ status: "loading" });
  const [aliasLookupValues, setAliasLookupValues] = useState<AliasLookupValues>(
    emptyAliasLookupValues,
  );
  const [aliasLookupState, setAliasLookupState] = useState<AliasLookupState>({
    status: "idle",
    aliases: [],
  });
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);
  const [openCorrectionFixtureId, setOpenCorrectionFixtureId] = useState<
    string | null
  >(null);
  const [corrections, setCorrections] = useState<
    Readonly<Record<string, CorrectionValues>>
  >({});

  const loadOperations = useCallback(async () => {
    setState({ status: "loading" });

    try {
      const [
        scheduledFixtures,
        postponedFixtures,
        completedFixtures,
        abandonedFixtures,
        teams,
        auditResponse,
      ] = await Promise.all([
        listFixtures({ status: "scheduled" }),
        listFixtures({ status: "postponed" }),
        listFixtures({ status: "completed" }),
        listFixtures({ status: "abandoned" }),
        listTeams(),
        canReadAuditLog
          ? listAuditEvents({}, { page: 1, limit: 50 })
          : Promise.resolve(null),
      ]);

      setState({
        status: "success",
        scheduledFixtures: scheduledFixtures.data,
        postponedFixtures: postponedFixtures.data,
        scoredFixtures: [
          ...completedFixtures.data,
          ...abandonedFixtures.data,
        ].sort(compareByScheduledAtDesc),
        teams: teams.data,
        scoringAudit:
          auditResponse?.data.filter((event) =>
            scoringAuditActions.has(event.action),
          ) ?? [],
      });
    } catch (error) {
      setState({ status: "error", message: getErrorMessage(error) });
    }
  }, [canReadAuditLog]);

  useEffect(() => {
    void loadOperations();
  }, [loadOperations]);

  const staleFixtures = useMemo(() => {
    if (state.status !== "success") return [];
    const now = Date.now();
    return [...state.scheduledFixtures, ...state.postponedFixtures].filter(
      (fixture) => isStaleFixture(fixture, now),
    );
  }, [state]);

  const operationsMetrics =
    state.status === "success"
      ? [
          {
            label: "Scored fixtures",
            value: state.scoredFixtures.length,
            description:
              "Completed or abandoned fixtures ready for admin review",
          },
          {
            label: "Stale fixtures",
            value: staleFixtures.length,
            description: "Scheduled or postponed fixtures now past kickoff",
          },
          {
            label: "Postponed fixtures",
            value: state.postponedFixtures.length,
            description: "Need status review or rescheduling",
          },
          {
            label: "Scoring audit events",
            value: canReadAuditLog ? state.scoringAudit.length : 0,
            description: canReadAuditLog
              ? "Recent scoring-related admin actions"
              : "Requires superadmin audit access",
          },
        ]
      : [];

  async function handleAliasLookupSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);

    if (!aliasLookupValues.sportId.trim() || !aliasLookupValues.alias.trim()) {
      setFeedback(adminErrorToast("Sport ID and alias are required."));
      return;
    }

    setAliasLookupState((current) => ({
      status: "loading",
      aliases: current.aliases,
    }));

    try {
      const response = await listTeamAliases({
        sportId: aliasLookupValues.sportId.trim(),
        source: aliasLookupValues.source.trim() || null,
        alias: aliasLookupValues.alias.trim(),
      });
      setAliasLookupState({
        status: "success",
        aliases: response.data,
      });
    } catch (error) {
      setAliasLookupState({
        status: "error",
        aliases: [],
        message: getErrorMessage(error),
      });
    }
  }

  async function handleRecalculateScores(fixture: AdminFixture) {
    setFeedback(null);
    setPendingAction(`recalculate:${fixture.id}`);

    try {
      const result = await recalculateFixturePredictionScores(fixture.id);
      setFeedback(
        adminSuccessToast(
          `Recalculated ${result.scoredPredictions} prediction scores for ${fixtureLabel(
            fixture,
            state.status === "success" ? state.teams : [],
          )}.`,
        ),
      );
      await loadOperations();
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function updateCorrectionValues(
    fixtureId: string,
    values: Partial<CorrectionValues>,
    fixture: AdminFixture,
  ) {
    setCorrections((current) => ({
      ...current,
      [fixtureId]: {
        ...(current[fixtureId] ?? makeCorrectionValues(fixture)),
        ...values,
      },
    }));
  }

  async function handleCorrectionSubmit(fixture: AdminFixture) {
    setFeedback(null);
    const correctionValues =
      corrections[fixture.id] ?? makeCorrectionValues(fixture);

    const validationMessage = getScoreValidationMessage(
      correctionValues.homeScore,
      correctionValues.awayScore,
    );
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    if (!correctionValues.reason.trim()) {
      setFeedback(adminErrorToast("Correction reason is required."));
      return;
    }

    setPendingAction(`correct:${fixture.id}`);

    try {
      await correctFixtureResult(fixture.id, {
        homeScore: Number(correctionValues.homeScore),
        awayScore: Number(correctionValues.awayScore),
        reason: correctionValues.reason.trim(),
      });
      setOpenCorrectionFixtureId(null);
      setFeedback(adminSuccessToast("Fixture result corrected."));
      await loadOperations();
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  return (
    <section aria-labelledby="operations-title">
      <div className="page-heading">
        <h2 id="operations-title">Operations</h2>
        <p>Scrape review, automation health, and scoring administration.</p>
      </div>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading operations…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load operations"
          message={state.message}
        />
      ) : null}

      {state.status === "success" ? (
        <>
          <div className="ops-summary-grid" aria-label="Operations overview">
            {operationsMetrics.map((metric) => (
              <article className="ops-summary-card" key={metric.label}>
                <span>{metric.label}</span>
                <strong>{metric.value}</strong>
                <p>{metric.description}</p>
              </article>
            ))}
          </div>

          <div className="ops-stage-grid ops-stage-grid-wide">
            <section className="ops-section-card operations-panel">
              <div className="ops-section-card-header">
                <div>
                  <h3>Automation health</h3>
                  <p>
                    Derived operational indicators based on live admin data.
                  </p>
                </div>
              </div>
              <div className="operations-health-grid">
                <article className="operations-health-item">
                  <strong>Stale kickoff backlog</strong>
                  <span>{staleFixtures.length} fixtures</span>
                  <p>
                    Scheduled or postponed fixtures past kickoff that may need
                    score entry, status review, or scraper follow-up.
                  </p>
                </article>
                <article className="operations-health-item">
                  <strong>Scoring recalculation queue</strong>
                  <span>{state.scoredFixtures.length} fixtures</span>
                  <p>
                    Completed or abandoned fixtures that can be manually
                    recalculated after corrections or audits.
                  </p>
                </article>
                <article className="operations-health-item">
                  <strong>Recent scoring changes</strong>
                  <span>
                    {canReadAuditLog ? state.scoringAudit.length : "Restricted"}
                  </span>
                  <p>
                    Uses existing audit events to highlight recent result entry,
                    correction, and fixture status changes.
                  </p>
                </article>
                <article className="operations-health-item operations-health-item-muted">
                  <strong>Scraper run telemetry</strong>
                  <span>Unavailable</span>
                  <p>
                    The backend does not currently persist scraper run logs or
                    job-health records for direct admin inspection.
                  </p>
                </article>
              </div>
            </section>

            <section className="ops-section-card operations-panel">
              <div className="ops-section-card-header">
                <div>
                  <h3>Scrape & alias review</h3>
                  <p>
                    Manually check alias mappings while unresolved scraper
                    events remain unpersisted.
                  </p>
                </div>
              </div>
              <form
                className="admin-form operations-inline-form"
                onSubmit={handleAliasLookupSubmit}
              >
                <div className="form-grid">
                  <label>
                    Sport ID
                    <input
                      value={aliasLookupValues.sportId}
                      onChange={(event) =>
                        setAliasLookupValues({
                          ...aliasLookupValues,
                          sportId: event.target.value,
                        })
                      }
                    />
                  </label>
                  <label>
                    Source
                    <input
                      value={aliasLookupValues.source}
                      onChange={(event) =>
                        setAliasLookupValues({
                          ...aliasLookupValues,
                          source: event.target.value,
                        })
                      }
                    />
                  </label>
                  <label>
                    Alias
                    <input
                      value={aliasLookupValues.alias}
                      onChange={(event) =>
                        setAliasLookupValues({
                          ...aliasLookupValues,
                          alias: event.target.value,
                        })
                      }
                    />
                  </label>
                </div>
                <div className="form-actions">
                  <button
                    className="secondary-button"
                    type="submit"
                    disabled={aliasLookupState.status === "loading"}
                  >
                    {aliasLookupState.status === "loading"
                      ? "Looking up..."
                      : "Lookup alias"}
                  </button>
                </div>
              </form>

              {aliasLookupState.status === "error" ? (
                <AdminTableError
                  title="Unable to load alias matches"
                  message={aliasLookupState.message}
                />
              ) : null}

              {aliasLookupState.status !== "idle" &&
              aliasLookupState.status !== "loading" &&
              aliasLookupState.aliases.length === 0 ? (
                <AdminTableEmpty
                  title="No alias mapping found"
                  message="No existing mapping matched the requested source and alias. Use the Aliases page to add one if needed."
                />
              ) : null}

              {aliasLookupState.aliases.length > 0 ? (
                <div className="admin-table-wrapper">
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th scope="col">Alias</th>
                        <th scope="col">Source</th>
                        <th scope="col">Team ID</th>
                        <th scope="col">Priority</th>
                        <th scope="col">Status</th>
                      </tr>
                    </thead>
                    <tbody>
                      {aliasLookupState.aliases.map((alias) => (
                        <tr key={alias.id}>
                          <td>{alias.alias}</td>
                          <td>{alias.source}</td>
                          <td>{alias.teamId}</td>
                          <td>{alias.priority}</td>
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
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : null}
            </section>
          </div>

          <section
            className="ops-section-card operations-panel"
            aria-labelledby="scoring-admin-title"
          >
            <div className="ops-table-toolbar">
              <div>
                <h3 id="scoring-admin-title">Scoring administration</h3>
                <p>
                  Recalculate prediction scores and correct completed results
                  without leaving the operations screen.
                </p>
              </div>
            </div>

            {state.scoredFixtures.length === 0 ? (
              <AdminTableEmpty
                title="No scored fixtures available"
                message="Completed or scored abandoned fixtures will appear here for recalculation and correction."
              />
            ) : (
              <div className="admin-table-wrapper">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th scope="col">Fixture</th>
                      <th scope="col">Status</th>
                      <th scope="col">Score</th>
                      <th scope="col">Scheduled</th>
                      <th scope="col">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    {state.scoredFixtures.map((fixture) => {
                      const correctionValues =
                        corrections[fixture.id] ??
                        makeCorrectionValues(fixture);

                      return (
                        <tr key={fixture.id}>
                          <td>
                            {fixtureLabel(fixture, state.teams)}
                            <small>{fixture.roundName || fixture.round}</small>
                          </td>
                          <td>{fixture.status}</td>
                          <td>{fixtureScore(fixture)}</td>
                          <td>{formatDateTime(fixture.scheduledAt)}</td>
                          <td>
                            <div className="row-actions">
                              <button
                                className="secondary-button compact-button"
                                type="button"
                                disabled={
                                  pendingAction === `recalculate:${fixture.id}`
                                }
                                onClick={() =>
                                  void handleRecalculateScores(fixture)
                                }
                              >
                                {pendingAction === `recalculate:${fixture.id}`
                                  ? "Recalculating..."
                                  : "Recalculate scores"}
                              </button>
                              <button
                                className="secondary-button compact-button"
                                type="button"
                                onClick={() =>
                                  setOpenCorrectionFixtureId((current) =>
                                    current === fixture.id ? null : fixture.id,
                                  )
                                }
                              >
                                {openCorrectionFixtureId === fixture.id
                                  ? "Hide correction"
                                  : "Correct result"}
                              </button>
                            </div>
                            {openCorrectionFixtureId === fixture.id ? (
                              <div className="fixture-actions operations-correction-panel">
                                <label>
                                  Corrected home score
                                  <input
                                    aria-label={`Corrected home score for ${fixture.id}`}
                                    value={correctionValues.homeScore}
                                    onChange={(event) =>
                                      updateCorrectionValues(
                                        fixture.id,
                                        { homeScore: event.target.value },
                                        fixture,
                                      )
                                    }
                                  />
                                </label>
                                <label>
                                  Corrected away score
                                  <input
                                    aria-label={`Corrected away score for ${fixture.id}`}
                                    value={correctionValues.awayScore}
                                    onChange={(event) =>
                                      updateCorrectionValues(
                                        fixture.id,
                                        { awayScore: event.target.value },
                                        fixture,
                                      )
                                    }
                                  />
                                </label>
                                <label>
                                  Correction reason
                                  <input
                                    aria-label={`Correction reason for ${fixture.id}`}
                                    value={correctionValues.reason}
                                    onChange={(event) =>
                                      updateCorrectionValues(
                                        fixture.id,
                                        { reason: event.target.value },
                                        fixture,
                                      )
                                    }
                                  />
                                </label>
                                <button
                                  className="primary-button compact-button"
                                  type="button"
                                  disabled={
                                    pendingAction === `correct:${fixture.id}`
                                  }
                                  onClick={() =>
                                    void handleCorrectionSubmit(fixture)
                                  }
                                >
                                  {pendingAction === `correct:${fixture.id}`
                                    ? "Saving..."
                                    : "Save correction"}
                                </button>
                              </div>
                            ) : null}
                          </td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
            )}
          </section>

          <section
            className="ops-section-card operations-panel"
            aria-labelledby="scoring-audit-title"
          >
            <div className="ops-table-toolbar">
              <div>
                <h3 id="scoring-audit-title">Result audit history</h3>
                <p>
                  Recent scoring-related audit events from the existing admin
                  audit log.
                </p>
              </div>
            </div>

            {!canReadAuditLog ? (
              <AdminTableEmpty
                title="Audit access restricted"
                message="Recent scoring audit history requires superadmin audit-log access."
              />
            ) : state.scoringAudit.length === 0 ? (
              <AdminTableEmpty
                title="No recent scoring audit events"
                message="Result entry, corrections, and status transitions will appear here once recorded."
              />
            ) : (
              <div
                className="operations-timeline"
                aria-label="Scoring audit timeline"
              >
                {state.scoringAudit.slice(0, 10).map((event) => (
                  <article className="operations-timeline-item" key={event.id}>
                    <header>
                      <strong>{event.summary}</strong>
                      <span>{formatDateTime(event.createdAt)}</span>
                    </header>
                    <p>
                      {event.action} ·{" "}
                      {event.actorDisplayName ??
                        event.actorEmail ??
                        event.actorUserId ??
                        "Unknown actor"}
                    </p>
                    <small>
                      {event.entityType}
                      {event.entityId ? ` ${event.entityId}` : ""}
                    </small>
                  </article>
                ))}
              </div>
            )}
          </section>
        </>
      ) : null}
    </section>
  );
}
