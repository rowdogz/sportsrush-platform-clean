import {
  useCallback,
  useEffect,
  useMemo,
  useState,
  type FormEvent,
} from "react";
import {
  createFixture,
  correctFixtureResult,
  enterFixtureResult,
  listFixtures,
  toFixtureWritePayload,
  transitionFixture,
  updateFixture,
} from "../features/fixtures/api";
import type {
  AdminFixture,
  FixtureListFilters,
  FixtureStatus,
  FixtureWritePayload,
} from "../features/fixtures/types";
import { fixtureStatuses } from "../features/fixtures/types";
import { listCompetitions } from "../features/competitions/api";
import type { AdminCompetition } from "../features/competitions/types";
import { listRounds } from "../features/rounds/api";
import type { AdminRound } from "../features/rounds/types";
import { listTeams } from "../features/teams/api";
import type { AdminTeam } from "../features/teams/types";
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
  AdminTablePreferences,
  useAdminTablePreferences,
  type AdminTableColumn,
} from "../components/admin/AdminTablePreferences";
import {
  appendStringParam,
  readDateParam,
  readEnumParam,
  readStringParam,
  useAdminSearchParams,
} from "../hooks/useAdminSearchParams";
import { ApiError } from "../lib/apiClient";

type FixturesState =
  | { readonly status: "loading" }
  | {
      readonly status: "success";
      readonly fixtures: readonly AdminFixture[];
    }
  | { readonly status: "error"; readonly message: string };

type ReferenceState = {
  readonly competitions: readonly AdminCompetition[];
  readonly teams: readonly AdminTeam[];
  readonly rounds: readonly AdminRound[];
};

type FormValues = {
  readonly sportId: string;
  readonly competitionId: string;
  readonly seasonId: string;
  readonly roundId: string;
  readonly round: string;
  readonly roundName: string;
  readonly roundOrder: string;
  readonly homeTeamId: string;
  readonly awayTeamId: string;
  readonly scheduledAt: string;
  readonly originalScheduledAt: string;
  readonly venueName: string;
  readonly status: FixtureStatus;
  readonly homeScore: string;
  readonly awayScore: string;
  readonly legacyMatchId: string;
  readonly legacyFixtureId: string;
  readonly externalSource: string;
  readonly externalId: string;
};

type FilterValues = {
  readonly competitionId: string;
  readonly seasonId: string;
  readonly round: string;
  readonly status: string;
  readonly dateFrom: string;
  readonly dateTo: string;
};

type RowActionValues = {
  readonly nextStatus: FixtureStatus;
  readonly homeScore: string;
  readonly awayScore: string;
  readonly resultSource: string;
  readonly correctedHomeScore: string;
  readonly correctedAwayScore: string;
  readonly correctionReason: string;
};

type BulkActionValues = {
  readonly status: Exclude<FixtureStatus, "completed">;
  readonly roundId: string;
  readonly round: string;
  readonly roundName: string;
};

type FeedbackState = AdminFeedbackState;

const defaultSportId = "sport-rugby-league";

const emptyFormValues: FormValues = {
  sportId: defaultSportId,
  competitionId: "",
  seasonId: "",
  roundId: "",
  round: "",
  roundName: "",
  roundOrder: "",
  homeTeamId: "",
  awayTeamId: "",
  scheduledAt: "",
  originalScheduledAt: "",
  venueName: "",
  status: "scheduled",
  homeScore: "",
  awayScore: "",
  legacyMatchId: "",
  legacyFixtureId: "",
  externalSource: "",
  externalId: "",
};

const emptyFilterValues: FilterValues = {
  competitionId: "",
  seasonId: "",
  round: "",
  status: "",
  dateFrom: "",
  dateTo: "",
};

const emptyBulkActionValues: BulkActionValues = {
  status: "postponed",
  roundId: "",
  round: "",
  roundName: "",
};

const bulkTransitionStatuses = fixtureStatuses.filter(
  (status): status is BulkActionValues["status"] => status !== "completed",
);

const fixtureTableColumns: readonly AdminTableColumn[] = [
  { id: "fixture", label: "Fixture" },
  { id: "competition", label: "Competition", optional: true },
  { id: "season", label: "Season", optional: true },
  { id: "round", label: "Round", optional: true },
  { id: "scheduled", label: "Scheduled" },
  { id: "venue", label: "Venue", optional: true },
  { id: "status", label: "Status" },
  { id: "score", label: "Score" },
  { id: "actions", label: "Actions" },
];

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    return error.message;
  }

  if (error instanceof Error) {
    return error.message;
  }

  return "Unable to load fixtures.";
}

function toOptionalValue(value: string): string | null {
  const trimmedValue = value.trim();
  return trimmedValue ? trimmedValue : null;
}

function toOptionalNumber(value: string): number | null {
  const trimmedValue = value.trim();
  return trimmedValue ? Number(trimmedValue) : null;
}

function isIntegerValue(value: string): boolean {
  return !value.trim() || Number.isInteger(Number(value));
}

function getRequiredValidationMessage(values: FormValues): string | null {
  if (
    !values.sportId.trim() ||
    !values.competitionId.trim() ||
    !values.seasonId.trim() ||
    !values.round.trim() ||
    !values.roundName.trim() ||
    !values.homeTeamId.trim() ||
    !values.awayTeamId.trim() ||
    !values.scheduledAt.trim()
  ) {
    return "Sport, competition, season, round, teams, and scheduled time are required.";
  }

  if (!isIntegerValue(values.roundOrder)) {
    return "Round order must be an integer.";
  }

  if (!isIntegerValue(values.homeScore) || !isIntegerValue(values.awayScore)) {
    return "Scores must be integers.";
  }

  if (!isIntegerValue(values.legacyMatchId)) {
    return "Legacy match ID must be an integer.";
  }

  return null;
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

function toFilters(values: FilterValues): FixtureListFilters {
  return {
    competitionId: toOptionalValue(values.competitionId),
    seasonId: toOptionalValue(values.seasonId),
    round: toOptionalValue(values.round),
    status: toOptionalValue(values.status) as FixtureStatus | null,
    dateFrom: toOptionalValue(values.dateFrom),
    dateTo: toOptionalValue(values.dateTo),
  };
}

function toWritePayload(values: FormValues): FixtureWritePayload {
  return toFixtureWritePayload({
    sportId: values.sportId.trim(),
    competitionId: values.competitionId.trim(),
    seasonId: values.seasonId.trim(),
    roundId: toOptionalValue(values.roundId),
    round: values.round.trim(),
    roundName: values.roundName.trim(),
    roundOrder: toOptionalNumber(values.roundOrder),
    homeTeamId: values.homeTeamId.trim(),
    awayTeamId: values.awayTeamId.trim(),
    scheduledAt: values.scheduledAt.trim(),
    originalScheduledAt: toOptionalValue(values.originalScheduledAt),
    venueName: toOptionalValue(values.venueName),
    status: values.status,
    homeScore: toOptionalNumber(values.homeScore),
    awayScore: toOptionalNumber(values.awayScore),
    legacyMatchId: toOptionalNumber(values.legacyMatchId),
    legacyFixtureId: toOptionalValue(values.legacyFixtureId),
    externalSource: toOptionalValue(values.externalSource),
    externalId: toOptionalValue(values.externalId),
  });
}

function toEditPayload(
  fixture: AdminFixture,
  values: FormValues,
): FixtureWritePayload {
  const payload = { ...toWritePayload(values) };

  if (fixture.status !== "completed") {
    return payload;
  }

  delete payload.homeScore;
  delete payload.awayScore;

  return payload;
}

function toFormValues(fixture: AdminFixture): FormValues {
  return {
    sportId: fixture.sportId,
    competitionId: fixture.competitionId,
    seasonId: fixture.seasonId,
    roundId: fixture.roundId ?? "",
    round: fixture.round,
    roundName: fixture.roundName,
    roundOrder: fixture.roundOrder === null ? "" : String(fixture.roundOrder),
    homeTeamId: fixture.homeTeamId,
    awayTeamId: fixture.awayTeamId,
    scheduledAt: fixture.scheduledAt,
    originalScheduledAt: fixture.originalScheduledAt ?? "",
    venueName: fixture.venueName ?? "",
    status: fixture.status,
    homeScore: fixture.homeScore === null ? "" : String(fixture.homeScore),
    awayScore: fixture.awayScore === null ? "" : String(fixture.awayScore),
    legacyMatchId:
      fixture.legacyMatchId === null ? "" : String(fixture.legacyMatchId),
    legacyFixtureId: fixture.legacyFixtureId ?? "",
    externalSource: fixture.externalSource ?? "",
    externalId: fixture.externalId ?? "",
  };
}

function makeRowActionValues(fixture: AdminFixture): RowActionValues {
  return {
    nextStatus: fixture.status === "scheduled" ? "postponed" : "scheduled",
    homeScore: fixture.homeScore === null ? "" : String(fixture.homeScore),
    awayScore: fixture.awayScore === null ? "" : String(fixture.awayScore),
    resultSource: fixture.resultSource ?? "manual",
    correctedHomeScore:
      fixture.homeScore === null ? "" : String(fixture.homeScore),
    correctedAwayScore:
      fixture.awayScore === null ? "" : String(fixture.awayScore),
    correctionReason: "",
  };
}

function getTeamName(teams: readonly AdminTeam[], id: string): string {
  return teams.find((team) => team.id === id)?.name ?? id;
}

function canEnterResult(fixture: AdminFixture): boolean {
  return fixture.status === "scheduled" || fixture.status === "postponed";
}

function canCorrectResult(fixture: AdminFixture): boolean {
  return fixture.status === "completed";
}

function pluralizeFixture(count: number): string {
  return count === 1 ? "fixture" : "fixtures";
}

export function FixturesPage() {
  const [state, setState] = useState<FixturesState>({ status: "loading" });
  const [references, setReferences] = useState<ReferenceState>({
    competitions: [],
    teams: [],
    rounds: [],
  });
  const [filters, setFilters] = useAdminSearchParams({
    storageKey: "sr-admin:fixtures:filters",
    defaults: emptyFilterValues,
    paramKeys: [
      "competitionId",
      "seasonId",
      "roundId",
      "status",
      "dateFrom",
      "dateTo",
    ],
    parse: (params, fallback) => ({
      competitionId: readStringParam(
        params,
        "competitionId",
        fallback.competitionId,
      ),
      seasonId: readStringParam(params, "seasonId", fallback.seasonId),
      round: readStringParam(params, "roundId", fallback.round),
      status: readEnumParam(params, "status", fallback.status, [
        "",
        ...fixtureStatuses,
      ]),
      dateFrom: readDateParam(params, "dateFrom", fallback.dateFrom),
      dateTo: readDateParam(params, "dateTo", fallback.dateTo),
    }),
    serialize: (value, defaults) => {
      const params = new URLSearchParams();
      appendStringParam(
        params,
        "competitionId",
        value.competitionId,
        defaults.competitionId,
      );
      appendStringParam(params, "seasonId", value.seasonId, defaults.seasonId);
      appendStringParam(params, "roundId", value.round, defaults.round);
      appendStringParam(params, "status", value.status, defaults.status);
      appendStringParam(params, "dateFrom", value.dateFrom, defaults.dateFrom);
      appendStringParam(params, "dateTo", value.dateTo, defaults.dateTo);
      return params;
    },
  });
  const [activeFilters, setActiveFilters] = useState<FilterValues>(filters);
  const [createValues, setCreateValues] = useState<FormValues>(emptyFormValues);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [correctionFixtureId, setCorrectionFixtureId] = useState<string | null>(
    null,
  );
  const [editValues, setEditValues] = useState<FormValues>(emptyFormValues);
  const [rowActions, setRowActions] = useState<
    Readonly<Record<string, RowActionValues>>
  >({});
  const [selectedFixtureIds, setSelectedFixtureIds] = useState<
    readonly string[]
  >([]);
  const [bulkValues, setBulkValues] = useState<BulkActionValues>(
    emptyBulkActionValues,
  );
  const [feedback, setFeedback] = useState<FeedbackState | null>(null);
  const [pendingAction, setPendingAction] = useState<string | null>(null);
  const tablePreferences = useAdminTablePreferences(
    "sr-admin:fixtures:table-preferences",
    fixtureTableColumns,
  );
  const visibleFixtures = useMemo(
    () => (state.status === "success" ? state.fixtures : []),
    [state],
  );
  const selectedFixtures = useMemo(
    () =>
      visibleFixtures.filter((fixture) =>
        selectedFixtureIds.includes(fixture.id),
      ),
    [selectedFixtureIds, visibleFixtures],
  );
  const allVisibleSelected =
    visibleFixtures.length > 0 &&
    visibleFixtures.every((fixture) => selectedFixtureIds.includes(fixture.id));
  const fixtureMetrics =
    state.status === "success"
      ? [
          {
            label: "Visible fixtures",
            value: state.fixtures.length,
            description: "Current table result set",
          },
          {
            label: "Selected",
            value: selectedFixtures.length,
            description: "Ready for safe bulk actions",
          },
          {
            label: "Scheduled",
            value: state.fixtures.filter(
              (fixture) => fixture.status === "scheduled",
            ).length,
            description: "Pending kickoff or result entry",
          },
          {
            label: "Completed",
            value: state.fixtures.filter(
              (fixture) => fixture.status === "completed",
            ).length,
            description: "Available for corrections only",
          },
        ]
      : [];

  const loadFixtures = useCallback(
    async (
      nextFilters: FilterValues,
      { showLoading = false }: { readonly showLoading?: boolean } = {},
    ) => {
      if (showLoading) {
        setState({ status: "loading" });
      }
      try {
        const response = await listFixtures(toFilters(nextFilters));
        setState({ status: "success", fixtures: response.data });
        setSelectedFixtureIds((current) => {
          const visibleIds = new Set(
            response.data.map((fixture) => fixture.id),
          );
          return current.filter((id) => visibleIds.has(id));
        });
        setRowActions(
          Object.fromEntries(
            response.data.map((fixture) => [
              fixture.id,
              makeRowActionValues(fixture),
            ]),
          ),
        );
      } catch (error) {
        setState({ status: "error", message: getErrorMessage(error) });
      }
    },
    [],
  );

  useEffect(() => {
    async function loadInitialData() {
      const [competitionsResponse, teamsResponse, fixturesResponse] =
        await Promise.all([
          listCompetitions(),
          listTeams(),
          listFixtures(toFilters(activeFilters)),
        ]);
      setReferences({
        competitions: competitionsResponse.data,
        teams: teamsResponse.data,
        rounds: [],
      });
      setState({ status: "success", fixtures: fixturesResponse.data });
      setSelectedFixtureIds((current) => {
        const visibleIds = new Set(
          fixturesResponse.data.map((fixture) => fixture.id),
        );
        return current.filter((id) => visibleIds.has(id));
      });
      setRowActions(
        Object.fromEntries(
          fixturesResponse.data.map((fixture) => [
            fixture.id,
            makeRowActionValues(fixture),
          ]),
        ),
      );
    }

    setState({ status: "loading" });
    void loadInitialData().catch((error: unknown) => {
      setState({ status: "error", message: getErrorMessage(error) });
    });
  }, []);

  async function loadRoundsForSeason(seasonId: string) {
    const trimmedSeasonId = seasonId.trim();
    if (!trimmedSeasonId) {
      setReferences((current) => ({ ...current, rounds: [] }));
      return;
    }

    try {
      const response = await listRounds({ seasonId: trimmedSeasonId });
      setReferences((current) => ({ ...current, rounds: response.data }));
    } catch {
      setReferences((current) => ({ ...current, rounds: [] }));
    }
  }

  async function handleFilterSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault();
    setFeedback(null);
    setActiveFilters(filters);
    await loadFixtures(filters, { showLoading: true });
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
      await createFixture(toWritePayload(createValues));
      await loadFixtures(activeFilters);
      setCreateValues({
        ...emptyFormValues,
        sportId: createValues.sportId,
        competitionId: createValues.competitionId,
        seasonId: createValues.seasonId,
      });
      setFeedback(adminSuccessToast("Fixture created."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function startEditing(fixture: AdminFixture) {
    setFeedback(null);
    setEditingId(fixture.id);
    setEditValues(toFormValues(fixture));
  }

  async function handleEditSubmit(
    event: FormEvent<HTMLFormElement>,
    fixture: AdminFixture,
  ) {
    event.preventDefault();
    setFeedback(null);

    const validationMessage = getRequiredValidationMessage(editValues);
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`edit:${fixture.id}`);
    try {
      await updateFixture(fixture.id, toEditPayload(fixture, editValues));
      await loadFixtures(activeFilters);
      setEditingId(null);
      setFeedback(adminSuccessToast("Fixture updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleTransition(fixture: AdminFixture) {
    setFeedback(null);
    const actionValues = rowActions[fixture.id] ?? makeRowActionValues(fixture);
    setPendingAction(`transition:${fixture.id}`);
    try {
      await transitionFixture(fixture.id, { status: actionValues.nextStatus });
      await loadFixtures(activeFilters);
      setFeedback(adminSuccessToast("Fixture status updated."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleEnterResult(fixture: AdminFixture) {
    setFeedback(null);
    if (!canEnterResult(fixture)) {
      setFeedback({
        type: "error",
        message:
          "Results can only be entered for scheduled or postponed fixtures.",
      });
      return;
    }

    const actionValues = rowActions[fixture.id] ?? makeRowActionValues(fixture);
    const validationMessage = getScoreValidationMessage(
      actionValues.homeScore,
      actionValues.awayScore,
    );
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    setPendingAction(`result:${fixture.id}`);
    try {
      await enterFixtureResult(fixture.id, {
        homeScore: Number(actionValues.homeScore),
        awayScore: Number(actionValues.awayScore),
        resultSource: toOptionalValue(actionValues.resultSource),
      });
      await loadFixtures(activeFilters);
      setFeedback(adminSuccessToast("Fixture result entered."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  async function handleCorrectResult(fixture: AdminFixture) {
    setFeedback(null);
    if (!canCorrectResult(fixture)) {
      setFeedback({
        type: "error",
        message: "Only completed fixture results can be corrected.",
      });
      return;
    }

    const actionValues = rowActions[fixture.id] ?? makeRowActionValues(fixture);
    const validationMessage = getScoreValidationMessage(
      actionValues.correctedHomeScore,
      actionValues.correctedAwayScore,
    );
    if (validationMessage) {
      setFeedback(adminErrorToast(validationMessage));
      return;
    }

    if (!actionValues.correctionReason.trim()) {
      setFeedback(adminErrorToast("Correction reason is required."));
      return;
    }

    setPendingAction(`correct:${fixture.id}`);
    try {
      await correctFixtureResult(fixture.id, {
        homeScore: Number(actionValues.correctedHomeScore),
        awayScore: Number(actionValues.correctedAwayScore),
        reason: actionValues.correctionReason.trim(),
      });
      await loadFixtures(activeFilters);
      setCorrectionFixtureId(null);
      setFeedback(adminSuccessToast("Fixture result corrected."));
    } catch (error) {
      setFeedback(adminErrorToast(getErrorMessage(error)));
    } finally {
      setPendingAction(null);
    }
  }

  function updateRowAction(
    fixture: AdminFixture,
    values: Partial<RowActionValues>,
  ) {
    setRowActions((current) => ({
      ...current,
      [fixture.id]: {
        ...(current[fixture.id] ?? makeRowActionValues(fixture)),
        ...values,
      },
    }));
  }

  function toggleFixtureSelection(fixtureId: string) {
    setSelectedFixtureIds((current) =>
      current.includes(fixtureId)
        ? current.filter((id) => id !== fixtureId)
        : [...current, fixtureId],
    );
  }

  function toggleAllVisibleFixtures() {
    if (allVisibleSelected) {
      setSelectedFixtureIds((current) =>
        current.filter(
          (id) => !visibleFixtures.some((fixture) => fixture.id === id),
        ),
      );
      return;
    }

    setSelectedFixtureIds((current) =>
      Array.from(
        new Set([...current, ...visibleFixtures.map((fixture) => fixture.id)]),
      ),
    );
  }

  function clearSelection() {
    setSelectedFixtureIds([]);
  }

  async function applyBulkStatus() {
    setFeedback(null);
    if (selectedFixtures.length === 0) {
      setFeedback(
        adminErrorToast("Select fixtures before applying a bulk action."),
      );
      return;
    }

    if (
      !window.confirm(
        `Update status for ${selectedFixtures.length} selected ${pluralizeFixture(
          selectedFixtures.length,
        )}?`,
      )
    ) {
      return;
    }

    setPendingAction("bulk-status");
    const results = await Promise.all(
      selectedFixtures.map(async (fixture) => {
        try {
          await transitionFixture(fixture.id, { status: bulkValues.status });
          return { ok: true };
        } catch (error) {
          return { ok: false, message: getErrorMessage(error) };
        }
      }),
    );

    await loadFixtures(activeFilters);
    const failedCount = results.filter((result) => !result.ok).length;
    const succeededCount = results.length - failedCount;
    if (failedCount === 0) {
      clearSelection();
      setFeedback(
        adminSuccessToast(
          `Updated status for ${succeededCount} ${pluralizeFixture(
            succeededCount,
          )}.`,
        ),
      );
    } else {
      setFeedback(
        adminErrorToast(
          `Updated status for ${succeededCount} ${pluralizeFixture(
            succeededCount,
          )}; ${failedCount} failed.`,
        ),
      );
    }
    setPendingAction(null);
  }

  async function applyBulkRound() {
    setFeedback(null);
    if (selectedFixtures.length === 0) {
      setFeedback(
        adminErrorToast("Select fixtures before applying a bulk action."),
      );
      return;
    }
    if (!bulkValues.round.trim() || !bulkValues.roundName.trim()) {
      setFeedback(adminErrorToast("Round and round name are required."));
      return;
    }

    if (
      !window.confirm(
        `Change round for ${selectedFixtures.length} selected ${pluralizeFixture(
          selectedFixtures.length,
        )}?`,
      )
    ) {
      return;
    }

    setPendingAction("bulk-round");
    const results = await Promise.all(
      selectedFixtures.map(async (fixture) => {
        try {
          await updateFixture(
            fixture.id,
            toEditPayload(fixture, {
              ...toFormValues(fixture),
              roundId: bulkValues.roundId,
              round: bulkValues.round,
              roundName: bulkValues.roundName,
            }),
          );
          return { ok: true };
        } catch (error) {
          return { ok: false, message: getErrorMessage(error) };
        }
      }),
    );

    await loadFixtures(activeFilters);
    const failedCount = results.filter((result) => !result.ok).length;
    const succeededCount = results.length - failedCount;
    if (failedCount === 0) {
      clearSelection();
      setFeedback(
        adminSuccessToast(
          `Changed round for ${succeededCount} ${pluralizeFixture(
            succeededCount,
          )}.`,
        ),
      );
      setBulkValues(emptyBulkActionValues);
    } else {
      setFeedback(
        adminErrorToast(
          `Changed round for ${succeededCount} ${pluralizeFixture(
            succeededCount,
          )}; ${failedCount} failed.`,
        ),
      );
    }
    setPendingAction(null);
  }

  function updateCreateSeasonId(seasonId: string) {
    setCreateValues({ ...createValues, seasonId });
    void loadRoundsForSeason(seasonId);
  }

  function updateEditSeasonId(seasonId: string) {
    setEditValues({ ...editValues, seasonId });
    void loadRoundsForSeason(seasonId);
  }

  return (
    <section aria-labelledby="fixtures-title">
      <div className="page-heading">
        <h2 id="fixtures-title">Fixtures</h2>
        <p>Manage fixture schedules, statuses, and results.</p>
      </div>

      {fixtureMetrics.length > 0 ? (
        <div className="ops-summary-grid" aria-label="Fixture overview">
          {fixtureMetrics.map((metric) => (
            <article className="ops-summary-card" key={metric.label}>
              <span>{metric.label}</span>
              <strong>{metric.value}</strong>
              <p>{metric.description}</p>
            </article>
          ))}
        </div>
      ) : null}

      <div className="ops-stage-grid ops-stage-grid-wide">
        <form
          className="admin-form ops-section-card"
          onSubmit={handleFilterSubmit}
        >
          <div className="form-heading ops-section-card-header">
            <div>
              <h3>Filter fixtures</h3>
              <p>
                Refine the operational queue before editing, scoring, or bulk
                updates.
              </p>
            </div>
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
                {references.competitions.map((competition) => (
                  <option key={competition.id} value={competition.id}>
                    {competition.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Filter season ID
              <input
                value={filters.seasonId}
                onChange={(event) =>
                  setFilters({ ...filters, seasonId: event.target.value })
                }
              />
            </label>
            <label>
              Filter round
              <input
                value={filters.round}
                onChange={(event) =>
                  setFilters({ ...filters, round: event.target.value })
                }
              />
            </label>
            <label>
              Filter status
              <select
                value={filters.status}
                onChange={(event) =>
                  setFilters({ ...filters, status: event.target.value })
                }
              >
                <option value="">All statuses</option>
                {fixtureStatuses.map((status) => (
                  <option key={status} value={status}>
                    {status}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Date from
              <input
                value={filters.dateFrom}
                onChange={(event) =>
                  setFilters({ ...filters, dateFrom: event.target.value })
                }
              />
            </label>
            <label>
              Date to
              <input
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
          </div>
        </form>

        <form
          className="admin-form ops-section-card"
          onSubmit={handleCreateSubmit}
        >
          <div className="form-heading ops-section-card-header">
            <div>
              <h3>Create fixture</h3>
              <p>
                Create and stage new fixtures without leaving the main
                operational surface.
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
                {references.competitions.map((competition) => (
                  <option key={competition.id} value={competition.id}>
                    {competition.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Season ID
              <input
                value={createValues.seasonId}
                onChange={(event) => updateCreateSeasonId(event.target.value)}
              />
            </label>
            <label>
              Round ID
              <select
                value={createValues.roundId}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    roundId: event.target.value,
                  })
                }
              >
                <option value="">No linked round</option>
                {references.rounds.map((round) => (
                  <option key={round.id} value={round.id}>
                    {round.roundName}
                  </option>
                ))}
              </select>
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
              Round order
              <input
                value={createValues.roundOrder}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    roundOrder: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Home team
              <select
                value={createValues.homeTeamId}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    homeTeamId: event.target.value,
                  })
                }
              >
                <option value="">Select home team</option>
                {references.teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Away team
              <select
                value={createValues.awayTeamId}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    awayTeamId: event.target.value,
                  })
                }
              >
                <option value="">Select away team</option>
                {references.teams.map((team) => (
                  <option key={team.id} value={team.id}>
                    {team.name}
                  </option>
                ))}
              </select>
            </label>
            <label>
              Scheduled at
              <input
                value={createValues.scheduledAt}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    scheduledAt: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Venue
              <input
                value={createValues.venueName}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    venueName: event.target.value,
                  })
                }
              />
            </label>
            <label>
              Status
              <select
                value={createValues.status}
                onChange={(event) =>
                  setCreateValues({
                    ...createValues,
                    status: event.target.value as FixtureStatus,
                  })
                }
              >
                {fixtureStatuses.map((status) => (
                  <option key={status} value={status}>
                    {status}
                  </option>
                ))}
              </select>
            </label>
          </div>
          <div className="form-actions">
            <button
              className="primary-button"
              type="submit"
              disabled={pendingAction === "create"}
            >
              {pendingAction === "create" ? "Creating..." : "Create fixture"}
            </button>
          </div>
        </form>
      </div>

      <AdminFeedback feedback={feedback} />

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading fixtures…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load fixtures"
          message={state.message}
        />
      ) : null}

      {state.status === "success" && state.fixtures.length === 0 ? (
        <AdminTableEmpty
          title="No fixtures found"
          message="Fixtures will appear here after they are added."
        />
      ) : null}

      {state.status === "success" && state.fixtures.length > 0 ? (
        <section
          className="ops-section-card"
          aria-labelledby="fixtures-table-title"
        >
          <div className="ops-table-toolbar">
            <div>
              <h3 id="fixtures-table-title">Fixture operations</h3>
              <p>
                Use filters, inline editing, result entry, and bulk actions from
                the same workspace.
              </p>
            </div>
          </div>
          <AdminTablePreferences
            columns={fixtureTableColumns}
            density={tablePreferences.density}
            hiddenColumns={tablePreferences.hiddenColumns}
            onColumnVisibleChange={tablePreferences.setColumnVisible}
            onDensityChange={tablePreferences.setDensity}
          />
          <div className="bulk-actions-panel" aria-label="Fixture bulk actions">
            <div>
              <strong>
                {selectedFixtures.length} selected{" "}
                {pluralizeFixture(selectedFixtures.length)}
              </strong>
              <span>
                Select visible fixtures, then apply a safe bulk update.
              </span>
            </div>
            <div className="bulk-actions-grid">
              <label>
                Bulk status
                <select
                  value={bulkValues.status}
                  onChange={(event) =>
                    setBulkValues({
                      ...bulkValues,
                      status: event.target.value as BulkActionValues["status"],
                    })
                  }
                  disabled={selectedFixtures.length === 0}
                >
                  {bulkTransitionStatuses.map((status) => (
                    <option key={status} value={status}>
                      {status}
                    </option>
                  ))}
                </select>
              </label>
              <button
                className="secondary-button compact-button"
                type="button"
                disabled={
                  selectedFixtures.length === 0 ||
                  pendingAction === "bulk-status"
                }
                onClick={() => void applyBulkStatus()}
              >
                {pendingAction === "bulk-status"
                  ? "Updating..."
                  : "Apply status"}
              </button>
              <label>
                Bulk round ID
                <input
                  value={bulkValues.roundId}
                  onChange={(event) =>
                    setBulkValues({
                      ...bulkValues,
                      roundId: event.target.value,
                    })
                  }
                  disabled={selectedFixtures.length === 0}
                />
              </label>
              <label>
                Bulk round
                <input
                  value={bulkValues.round}
                  onChange={(event) =>
                    setBulkValues({
                      ...bulkValues,
                      round: event.target.value,
                    })
                  }
                  disabled={selectedFixtures.length === 0}
                />
              </label>
              <label>
                Bulk round name
                <input
                  value={bulkValues.roundName}
                  onChange={(event) =>
                    setBulkValues({
                      ...bulkValues,
                      roundName: event.target.value,
                    })
                  }
                  disabled={selectedFixtures.length === 0}
                />
              </label>
              <button
                className="secondary-button compact-button"
                type="button"
                disabled={
                  selectedFixtures.length === 0 ||
                  pendingAction === "bulk-round"
                }
                onClick={() => void applyBulkRound()}
              >
                {pendingAction === "bulk-round" ? "Changing..." : "Apply round"}
              </button>
              <button
                className="secondary-button compact-button"
                type="button"
                disabled={selectedFixtures.length === 0}
                onClick={clearSelection}
              >
                Clear selection
              </button>
            </div>
          </div>
          <div className="admin-table-wrapper">
            <table className={tablePreferences.tableClassName}>
              <thead>
                <tr>
                  <th scope="col">
                    <input
                      aria-label="Select all visible fixtures"
                      type="checkbox"
                      checked={allVisibleSelected}
                      onChange={toggleAllVisibleFixtures}
                    />
                  </th>
                  <th scope="col">Fixture</th>
                  {tablePreferences.isColumnVisible("competition") ? (
                    <th scope="col">Competition</th>
                  ) : null}
                  {tablePreferences.isColumnVisible("season") ? (
                    <th scope="col">Season</th>
                  ) : null}
                  {tablePreferences.isColumnVisible("round") ? (
                    <th scope="col">Round</th>
                  ) : null}
                  <th scope="col">Scheduled</th>
                  {tablePreferences.isColumnVisible("venue") ? (
                    <th scope="col">Venue</th>
                  ) : null}
                  <th scope="col">Status</th>
                  <th scope="col">Score</th>
                  <th scope="col">Actions</th>
                </tr>
              </thead>
              <tbody>
                {state.fixtures.map((fixture) => {
                  const actionValues =
                    rowActions[fixture.id] ?? makeRowActionValues(fixture);
                  return (
                    <tr key={fixture.id}>
                      {editingId === fixture.id ? (
                        <td colSpan={99}>
                          <form
                            className="inline-edit-form"
                            onSubmit={(event) =>
                              void handleEditSubmit(event, fixture)
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
                                  updateEditSeasonId(event.target.value)
                                }
                              />
                            </label>
                            <label>
                              Home team ID
                              <input
                                aria-label="Edit home team ID"
                                value={editValues.homeTeamId}
                                onChange={(event) =>
                                  setEditValues({
                                    ...editValues,
                                    homeTeamId: event.target.value,
                                  })
                                }
                              />
                            </label>
                            <label>
                              Away team ID
                              <input
                                aria-label="Edit away team ID"
                                value={editValues.awayTeamId}
                                onChange={(event) =>
                                  setEditValues({
                                    ...editValues,
                                    awayTeamId: event.target.value,
                                  })
                                }
                              />
                            </label>
                            <label>
                              Scheduled at
                              <input
                                aria-label="Edit scheduled at"
                                value={editValues.scheduledAt}
                                onChange={(event) =>
                                  setEditValues({
                                    ...editValues,
                                    scheduledAt: event.target.value,
                                  })
                                }
                              />
                            </label>
                            <label>
                              Venue
                              <input
                                aria-label="Edit venue"
                                value={editValues.venueName}
                                onChange={(event) =>
                                  setEditValues({
                                    ...editValues,
                                    venueName: event.target.value,
                                  })
                                }
                              />
                            </label>
                            <div className="row-actions">
                              {fixture.status === "completed" ? (
                                <span className="form-help">
                                  Completed fixture scores can only be changed
                                  with a result correction.
                                </span>
                              ) : null}
                              <button
                                className="primary-button compact-button"
                                type="submit"
                                disabled={
                                  pendingAction === `edit:${fixture.id}`
                                }
                              >
                                {pendingAction === `edit:${fixture.id}`
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
                          <td>
                            <input
                              aria-label={`Select fixture ${fixture.id}`}
                              type="checkbox"
                              checked={selectedFixtureIds.includes(fixture.id)}
                              onChange={() =>
                                toggleFixtureSelection(fixture.id)
                              }
                            />
                          </td>
                          <td>
                            {getTeamName(references.teams, fixture.homeTeamId)}{" "}
                            v{" "}
                            {getTeamName(references.teams, fixture.awayTeamId)}
                          </td>
                          {tablePreferences.isColumnVisible("competition") ? (
                            <td>{fixture.competitionId}</td>
                          ) : null}
                          {tablePreferences.isColumnVisible("season") ? (
                            <td>{fixture.seasonId}</td>
                          ) : null}
                          {tablePreferences.isColumnVisible("round") ? (
                            <td>
                              {fixture.roundName} ({fixture.round})
                            </td>
                          ) : null}
                          <td>{fixture.scheduledAt}</td>
                          {tablePreferences.isColumnVisible("venue") ? (
                            <td>{fixture.venueName ?? "—"}</td>
                          ) : null}
                          <td>
                            <span className="status-pill status-pill-active">
                              {fixture.status}
                            </span>
                          </td>
                          <td>
                            <div className="score-summary">
                              <strong>
                                {fixture.homeScore === null ||
                                fixture.awayScore === null
                                  ? "—"
                                  : `${fixture.homeScore}-${fixture.awayScore}`}
                              </strong>
                              {fixture.resultSource ? (
                                <span>Source: {fixture.resultSource}</span>
                              ) : null}
                              {fixture.resultEnteredAt ? (
                                <span>Entered: {fixture.resultEnteredAt}</span>
                              ) : null}
                            </div>
                          </td>
                          <td>
                            <div className="fixture-actions">
                              <button
                                className="secondary-button compact-button"
                                type="button"
                                onClick={() => startEditing(fixture)}
                              >
                                Edit
                              </button>
                              <label>
                                Status
                                <select
                                  aria-label={`Transition status for ${fixture.id}`}
                                  value={actionValues.nextStatus}
                                  onChange={(event) =>
                                    updateRowAction(fixture, {
                                      nextStatus: event.target
                                        .value as FixtureStatus,
                                    })
                                  }
                                >
                                  {fixtureStatuses.map((status) => (
                                    <option key={status} value={status}>
                                      {status}
                                    </option>
                                  ))}
                                </select>
                              </label>
                              <button
                                className="secondary-button compact-button"
                                type="button"
                                disabled={
                                  pendingAction === `transition:${fixture.id}`
                                }
                                onClick={() => void handleTransition(fixture)}
                              >
                                Update status
                              </button>
                              {canEnterResult(fixture) ? (
                                <div className="result-entry-panel">
                                  <label>
                                    Home score
                                    <input
                                      aria-label={`Home score for ${fixture.id}`}
                                      value={actionValues.homeScore}
                                      onChange={(event) =>
                                        updateRowAction(fixture, {
                                          homeScore: event.target.value,
                                        })
                                      }
                                    />
                                  </label>
                                  <label>
                                    Away score
                                    <input
                                      aria-label={`Away score for ${fixture.id}`}
                                      value={actionValues.awayScore}
                                      onChange={(event) =>
                                        updateRowAction(fixture, {
                                          awayScore: event.target.value,
                                        })
                                      }
                                    />
                                  </label>
                                  <label>
                                    Result source
                                    <input
                                      aria-label={`Result source for ${fixture.id}`}
                                      value={actionValues.resultSource}
                                      onChange={(event) =>
                                        updateRowAction(fixture, {
                                          resultSource: event.target.value,
                                        })
                                      }
                                    />
                                  </label>
                                  <button
                                    className="secondary-button compact-button"
                                    type="button"
                                    disabled={
                                      pendingAction === `result:${fixture.id}`
                                    }
                                    onClick={() =>
                                      void handleEnterResult(fixture)
                                    }
                                  >
                                    {pendingAction === `result:${fixture.id}`
                                      ? "Entering..."
                                      : "Enter result"}
                                  </button>
                                </div>
                              ) : null}
                              {canCorrectResult(fixture) ? (
                                <div className="result-entry-panel">
                                  {correctionFixtureId === fixture.id ? (
                                    <>
                                      <label>
                                        Corrected home
                                        <input
                                          aria-label={`Corrected home score for ${fixture.id}`}
                                          value={
                                            actionValues.correctedHomeScore
                                          }
                                          onChange={(event) =>
                                            updateRowAction(fixture, {
                                              correctedHomeScore:
                                                event.target.value,
                                            })
                                          }
                                        />
                                      </label>
                                      <label>
                                        Corrected away
                                        <input
                                          aria-label={`Corrected away score for ${fixture.id}`}
                                          value={
                                            actionValues.correctedAwayScore
                                          }
                                          onChange={(event) =>
                                            updateRowAction(fixture, {
                                              correctedAwayScore:
                                                event.target.value,
                                            })
                                          }
                                        />
                                      </label>
                                      <label>
                                        Reason
                                        <input
                                          aria-label={`Correction reason for ${fixture.id}`}
                                          value={actionValues.correctionReason}
                                          onChange={(event) =>
                                            updateRowAction(fixture, {
                                              correctionReason:
                                                event.target.value,
                                            })
                                          }
                                        />
                                      </label>
                                      <button
                                        className="secondary-button compact-button"
                                        type="button"
                                        disabled={
                                          pendingAction ===
                                          `correct:${fixture.id}`
                                        }
                                        onClick={() =>
                                          void handleCorrectResult(fixture)
                                        }
                                      >
                                        {pendingAction ===
                                        `correct:${fixture.id}`
                                          ? "Correcting..."
                                          : "Save correction"}
                                      </button>
                                      <button
                                        className="secondary-button compact-button"
                                        type="button"
                                        onClick={() =>
                                          setCorrectionFixtureId(null)
                                        }
                                      >
                                        Cancel correction
                                      </button>
                                    </>
                                  ) : (
                                    <button
                                      className="secondary-button compact-button"
                                      type="button"
                                      onClick={() => {
                                        setFeedback(null);
                                        setCorrectionFixtureId(fixture.id);
                                      }}
                                    >
                                      Correct result
                                    </button>
                                  )}
                                </div>
                              ) : null}
                            </div>
                          </td>
                        </>
                      )}
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </section>
      ) : null}
    </section>
  );
}
