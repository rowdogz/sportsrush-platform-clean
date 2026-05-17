import type { FixtureStatus } from "../../features/types";

export type PredictionDisplayStatus = FixtureStatus | "locked";

const labels: Record<PredictionDisplayStatus, string> = {
  scheduled: "Scheduled",
  live: "Live",
  completed: "Completed",
  postponed: "Postponed",
  cancelled: "Cancelled",
  abandoned: "Abandoned",
  locked: "Locked",
};

export function PredictionStatusBadge({
  status,
}: {
  readonly status: PredictionDisplayStatus;
}) {
  return (
    <span className={`status status-${status} prediction-status-badge`}>
      {labels[status]}
    </span>
  );
}
