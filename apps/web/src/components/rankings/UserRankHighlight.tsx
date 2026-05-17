import type { LeaderboardEntry } from "../../features/types";

export function isCurrentUserEntry(
  entry: LeaderboardEntry,
  user:
    | {
        readonly id: string;
        readonly email: string;
      }
    | null
    | undefined,
): boolean {
  if (!user) return false;
  return entry.userId === user.id || entry.email === user.email;
}

export function UserRankHighlight({
  entry,
  active,
}: {
  readonly entry: LeaderboardEntry;
  readonly active: boolean;
}) {
  if (!active) return null;
  return (
    <div className="user-rank-highlight">
      <strong>Your standing</strong>
      <span>
        #{entry.rank} · {entry.totalPoints} pts ·{" "}
        {entry.displayName ?? entry.email ?? entry.userId}
      </span>
    </div>
  );
}
