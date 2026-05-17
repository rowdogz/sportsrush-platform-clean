export function RankMovementBadge({
  movement,
}: {
  readonly movement: number | null;
}) {
  if (movement === null || movement === 0) {
    return (
      <span className="rank-movement-badge rank-movement-neutral">
        No change
      </span>
    );
  }
  if (movement > 0) {
    return (
      <span className="rank-movement-badge rank-movement-up">↑ {movement}</span>
    );
  }
  return (
    <span className="rank-movement-badge rank-movement-down">
      ↓ {Math.abs(movement)}
    </span>
  );
}
