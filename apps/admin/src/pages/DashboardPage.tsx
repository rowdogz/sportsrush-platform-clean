import { useCallback, useEffect, useMemo, useState } from "react";
import { listCompetitions } from "../features/competitions/api";
import { listSeasons } from "../features/seasons/api";
import { listTeams } from "../features/teams/api";
import { listFixtures } from "../features/fixtures/api";
import { listUsers } from "../features/users/api";
import { listAuditEvents } from "../features/audit-events/api";
import type { AdminFixture } from "../features/fixtures/types";
import type { AdminAuditEvent } from "../features/audit-events/types";
import { useAuthSession } from "../contexts/AuthSessionProvider";
import { canViewAuditLog } from "../lib/adminPermissions";
import {
  AdminTableEmpty,
  AdminTableError,
  AdminTableLoading,
} from "../components/admin/AdminTableState";
import { ApiError } from "../lib/apiClient";

type SummaryCard = {
  readonly id: string;
  readonly label: string;
  readonly value: number;
  readonly description: string;
};

type DashboardState =
  | { readonly status: "loading" }
  | { readonly status: "error"; readonly message: string }
  | {
      readonly status: "success";
      readonly cards: readonly SummaryCard[];
      readonly recentActivity: readonly AdminAuditEvent[];
      readonly recentFixtureChanges: readonly AdminFixture[];
    };

function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) return error.message;
  if (error instanceof Error) return error.message;
  return "Unable to load dashboard.";
}

function formatDateTime(value: string | null): string {
  if (!value) return "—";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? value : date.toLocaleString();
}

function fixtureTimestamp(fixture: AdminFixture): string {
  return fixture.resultEnteredAt ?? fixture.scheduledAt;
}

function compareFixtureChanges(
  firstFixture: AdminFixture,
  secondFixture: AdminFixture,
): number {
  return fixtureTimestamp(secondFixture).localeCompare(
    fixtureTimestamp(firstFixture),
  );
}

function fixtureScore(fixture: AdminFixture): string {
  if (fixture.homeScore === null || fixture.awayScore === null) return "—";
  return `${fixture.homeScore}-${fixture.awayScore}`;
}

function fixtureLabel(fixture: AdminFixture): string {
  return `${fixture.homeTeamId} vs ${fixture.awayTeamId}`;
}

export function DashboardPage() {
  const { userRole } = useAuthSession();
  const canReadAuditEvents = canViewAuditLog(userRole);
  const [state, setState] = useState<DashboardState>({ status: "loading" });

  const loadDashboard = useCallback(async () => {
    setState({ status: "loading" });

    try {
      const [competitions, seasons, teams, fixtures, users, fixtureChanges] =
        await Promise.all([
          listCompetitions(),
          listSeasons(),
          listTeams(),
          listFixtures(),
          listUsers(),
          listFixtures({ status: "completed" }),
        ]);
      const auditEvents = canReadAuditEvents
        ? await listAuditEvents({}, { page: 1, limit: 5 })
        : null;

      const cards: SummaryCard[] = [
        {
          id: "competitions",
          label: "Competitions",
          value: competitions.meta.total,
          description: "Configured competitions",
        },
        {
          id: "seasons",
          label: "Seasons",
          value: seasons.meta.total,
          description: "Competition seasons",
        },
        {
          id: "teams",
          label: "Teams",
          value: teams.meta.total,
          description: "Managed teams",
        },
        {
          id: "fixtures",
          label: "Fixtures",
          value: fixtures.meta.total,
          description: "Known fixtures",
        },
        {
          id: "users",
          label: "Users",
          value: users.meta.total,
          description: "Registered users",
        },
      ];

      if (auditEvents) {
        cards.push({
          id: "audit-events",
          label: "Audit Events",
          value: auditEvents.meta.total,
          description: "Recorded admin actions",
        });
      }

      setState({
        status: "success",
        cards,
        recentActivity: auditEvents?.data ?? [],
        recentFixtureChanges: [...fixtureChanges.data]
          .sort(compareFixtureChanges)
          .slice(0, 5),
      });
    } catch (error) {
      setState({ status: "error", message: getErrorMessage(error) });
    }
  }, [canReadAuditEvents]);

  useEffect(() => {
    void loadDashboard();
  }, [loadDashboard]);

  const hasOperationalData = useMemo(() => {
    if (state.status !== "success") return false;
    return state.cards.some((card) => card.value > 0);
  }, [state]);

  return (
    <section aria-labelledby="dashboard-title">
      <div className="page-heading">
        <h2 id="dashboard-title">Dashboard</h2>
        <p>Operational overview for core SportsRush admin data.</p>
      </div>

      {state.status === "loading" ? (
        <AdminTableLoading message="Loading dashboard…" />
      ) : null}

      {state.status === "error" ? (
        <AdminTableError
          title="Unable to load dashboard"
          message={state.message}
        />
      ) : null}

      {state.status === "success" ? (
        <div className="dashboard-sections">
          <section aria-labelledby="dashboard-summary-title">
            <h3 id="dashboard-summary-title">Summary</h3>
            {hasOperationalData ? (
              <div className="dashboard-card-grid">
                {state.cards.map((card) => (
                  <article className="dashboard-card" key={card.id}>
                    <span>{card.label}</span>
                    <strong>{card.value}</strong>
                    <p>{card.description}</p>
                  </article>
                ))}
              </div>
            ) : (
              <AdminTableEmpty
                title="No admin data yet"
                message="Summary cards will populate after admin data is created."
              />
            )}
          </section>

          {canReadAuditEvents ? (
            <section aria-labelledby="recent-activity-title">
              <h3 id="recent-activity-title">Recent Activity</h3>
              {state.recentActivity.length > 0 ? (
                <div className="admin-table-wrapper">
                  <table className="admin-table">
                    <thead>
                      <tr>
                        <th scope="col">Occurred</th>
                        <th scope="col">Actor</th>
                        <th scope="col">Action</th>
                        <th scope="col">Entity</th>
                        <th scope="col">Summary</th>
                      </tr>
                    </thead>
                    <tbody>
                      {state.recentActivity.map((event) => (
                        <tr key={event.id}>
                          <td>{formatDateTime(event.createdAt)}</td>
                          <td>
                            {event.actorDisplayName ??
                              event.actorEmail ??
                              event.actorUserId ??
                              "—"}
                          </td>
                          <td>{event.action}</td>
                          <td>
                            {event.entityType}
                            {event.entityId ? ` ${event.entityId}` : ""}
                          </td>
                          <td>{event.summary}</td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <AdminTableEmpty
                  title="No recent activity"
                  message="Audit events will appear here after admin changes are recorded."
                />
              )}
            </section>
          ) : null}

          <section aria-labelledby="recent-fixtures-title">
            <h3 id="recent-fixtures-title">Recent Fixture Result Changes</h3>
            {state.recentFixtureChanges.length > 0 ? (
              <div className="admin-table-wrapper">
                <table className="admin-table">
                  <thead>
                    <tr>
                      <th scope="col">Fixture</th>
                      <th scope="col">Status</th>
                      <th scope="col">Score</th>
                      <th scope="col">Result Source</th>
                      <th scope="col">Updated</th>
                    </tr>
                  </thead>
                  <tbody>
                    {state.recentFixtureChanges.map((fixture) => (
                      <tr key={fixture.id}>
                        <td>
                          {fixtureLabel(fixture)}
                          <small>{fixture.roundName || fixture.round}</small>
                        </td>
                        <td>{fixture.status}</td>
                        <td>{fixtureScore(fixture)}</td>
                        <td>{fixture.resultSource ?? "—"}</td>
                        <td>{formatDateTime(fixtureTimestamp(fixture))}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            ) : (
              <AdminTableEmpty
                title="No recent fixture result changes"
                message="Completed fixture results will appear here when they are entered or corrected."
              />
            )}
          </section>
        </div>
      ) : null}
    </section>
  );
}
