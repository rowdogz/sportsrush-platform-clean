import type { PublicCompetition } from "../../features/types";

export function LeaderboardFilters({
  competitions,
  selectedCompetitionId,
  search,
  onCompetitionChange,
  onSearchChange,
}: {
  readonly competitions: readonly PublicCompetition[];
  readonly selectedCompetitionId: string;
  readonly search: string;
  readonly onCompetitionChange: (value: string) => void;
  readonly onSearchChange: (value: string) => void;
}) {
  return (
    <div className="leaderboard-filters">
      <label>
        Competition
        <select
          aria-label="Leaderboard competition"
          value={selectedCompetitionId}
          onChange={(event) => onCompetitionChange(event.target.value)}
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
          aria-label="Leaderboard search"
          placeholder="Search by user"
          value={search}
          onChange={(event) => onSearchChange(event.target.value)}
        />
      </label>
    </div>
  );
}
