import type { FixtureStatus } from "../../features/types";

const labels: Record<FixtureStatus, string> = {
  scheduled: "Scheduled",
  live: "Live",
  completed: "Completed",
  postponed: "Postponed",
  cancelled: "Cancelled",
  abandoned: "Abandoned",
};

export function ResultStatusBadge({
  status,
}: {
  readonly status: FixtureStatus;
}) {
  return (
    <span className={`status status-${status} result-status-badge`}>
      {labels[status]}
    </span>
  );
}
