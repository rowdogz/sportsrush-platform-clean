import { useEffect, useMemo, useState } from "react";
import type { Prediction, PublicFixture } from "../../features/types";
import {
  PredictionStatusBadge,
  type PredictionDisplayStatus,
} from "./PredictionStatusBadge";
import { PredictionInput } from "./PredictionInput";
import { SaveStateIndicator, type SaveState } from "./SaveStateIndicator";

function teamName(team: PublicFixture["homeTeam"]): string {
  return team.displayName ?? team.shortName ?? team.name;
}

function initials(team: PublicFixture["homeTeam"]): string {
  return (team.shortName ?? team.name).slice(0, 3).toUpperCase();
}

function formatKickoff(kickoffTime: string): string {
  return new Date(kickoffTime).toLocaleString(undefined, {
    weekday: "short",
    month: "short",
    day: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

function isEditableStatus(status: PredictionDisplayStatus): boolean {
  return status === "scheduled";
}

function formatPrediction(prediction: Prediction | undefined): string {
  if (!prediction) return "No saved prediction";
  return `${prediction.homeScore} - ${prediction.awayScore}`;
}

type PredictionFixtureCardProps = {
  readonly fixture: PublicFixture;
  readonly status: PredictionDisplayStatus;
  readonly prediction: Prediction | undefined;
  readonly isAuthenticated: boolean;
  readonly onLogin: () => void;
  readonly onSave: (
    fixture: PublicFixture,
    scores: { readonly homeScore: number; readonly awayScore: number },
    existing: Prediction | undefined,
  ) => Promise<void>;
  readonly saveState: SaveState;
};

export function PredictionFixtureCard({
  fixture,
  status,
  prediction,
  isAuthenticated,
  onLogin,
  onSave,
  saveState,
}: PredictionFixtureCardProps) {
  const [homeScore, setHomeScore] = useState(
    prediction?.homeScore.toString() ?? "",
  );
  const [awayScore, setAwayScore] = useState(
    prediction?.awayScore.toString() ?? "",
  );

  useEffect(() => {
    setHomeScore(prediction?.homeScore.toString() ?? "");
    setAwayScore(prediction?.awayScore.toString() ?? "");
  }, [fixture.id, prediction?.awayScore, prediction?.homeScore]);

  const editable = isAuthenticated && isEditableStatus(status);
  const parsedHome = Number(homeScore);
  const parsedAway = Number(awayScore);
  const isValid =
    Number.isInteger(parsedHome) &&
    Number.isInteger(parsedAway) &&
    parsedHome >= 0 &&
    parsedAway >= 0;
  const dirty =
    prediction?.homeScore?.toString() !== homeScore ||
    prediction?.awayScore?.toString() !== awayScore;
  const canSave =
    editable &&
    isValid &&
    dirty &&
    saveState.status !== "saving" &&
    homeScore !== "" &&
    awayScore !== "";

  const savedCopy = useMemo(() => formatPrediction(prediction), [prediction]);

  async function submit(): Promise<void> {
    if (!canSave) return;
    await onSave(
      fixture,
      { homeScore: parsedHome, awayScore: parsedAway },
      prediction,
    );
  }

  return (
    <article className={`prediction-card prediction-card-${status}`}>
      <div className="prediction-card-header">
        <div>
          <p className="prediction-kickoff">
            {formatKickoff(fixture.kickoffTime)}
          </p>
          <h3>{fixture.round.name}</h3>
          <p className="prediction-context">
            {fixture.competition.shortName ?? fixture.competition.name}
            {fixture.venue ? ` · ${fixture.venue}` : ""}
          </p>
        </div>
        <PredictionStatusBadge status={status} />
      </div>

      <div className="prediction-card-body">
        <div className="prediction-team">
          <span className="team-mark">{initials(fixture.homeTeam)}</span>
          <div>
            <strong>{teamName(fixture.homeTeam)}</strong>
            <span>Home</span>
          </div>
        </div>

        <div className="prediction-entry-panel">
          {editable ? (
            <>
              <div className="prediction-score-entry">
                <PredictionInput
                  label={`Home prediction for ${fixture.homeTeam.name}`}
                  value={homeScore}
                  onChange={setHomeScore}
                />
                <span className="prediction-separator">-</span>
                <PredictionInput
                  label={`Away prediction for ${fixture.awayTeam.name}`}
                  value={awayScore}
                  onChange={setAwayScore}
                />
              </div>
              <div className="prediction-card-actions">
                <button
                  className="button compact"
                  disabled={!canSave}
                  type="button"
                  onClick={() => void submit()}
                >
                  {prediction ? "Update prediction" : "Save prediction"}
                </button>
                <SaveStateIndicator state={saveState} />
              </div>
            </>
          ) : (
            <div className="prediction-readonly-panel">
              <div className="prediction-score-display">
                <span>
                  {fixture.homeScore !== null ? fixture.homeScore : "—"}
                </span>
                <span className="prediction-separator">-</span>
                <span>
                  {fixture.awayScore !== null ? fixture.awayScore : "—"}
                </span>
              </div>
              <p className="prediction-readonly-copy">
                {status === "locked"
                  ? `Locked prediction: ${savedCopy}`
                  : status === "live" || status === "completed"
                    ? `Your prediction: ${savedCopy}`
                    : !isAuthenticated
                      ? "Login to enter predictions before kickoff."
                      : "Predictions are unavailable for this fixture state."}
              </p>
              {!isAuthenticated && status === "scheduled" ? (
                <button
                  className="button secondary compact"
                  type="button"
                  onClick={onLogin}
                >
                  Login to predict
                </button>
              ) : null}
              <SaveStateIndicator state={saveState} />
            </div>
          )}
        </div>

        <div className="prediction-team prediction-team-away">
          <div>
            <strong>{teamName(fixture.awayTeam)}</strong>
            <span>Away</span>
          </div>
          <span className="team-mark">{initials(fixture.awayTeam)}</span>
        </div>
      </div>
    </article>
  );
}
