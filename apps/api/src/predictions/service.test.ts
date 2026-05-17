import { describe, expect, it } from "vitest";
import { scorePrediction } from "./service";

describe("prediction scoring", () => {
  it("awards exact score, result, team score, and goal-difference points", () => {
    expect(
      scorePrediction(
        { home_score: 24, away_score: 18 },
        { home_score: 24, away_score: 18, status: "completed" },
      ),
    ).toEqual({
      exactScore: 5,
      correctResult: 3,
      homeScore: 1,
      awayScore: 1,
      goalDifference: 1,
      total: 11,
    });
  });

  it("awards partial bonuses for correct result and goal difference", () => {
    expect(
      scorePrediction(
        { home_score: 20, away_score: 14 },
        { home_score: 24, away_score: 18, status: "completed" },
      ),
    ).toEqual({
      exactScore: 0,
      correctResult: 3,
      homeScore: 0,
      awayScore: 0,
      goalDifference: 1,
      total: 4,
    });
  });

  it("supports abandoned fixtures with partial scores", () => {
    expect(
      scorePrediction(
        { home_score: 10, away_score: 8 },
        { home_score: 10, away_score: 8, status: "abandoned" },
      ).total,
    ).toBe(11);
  });

  it("returns zero until a fixture has scores", () => {
    expect(
      scorePrediction(
        { home_score: 10, away_score: 8 },
        { home_score: null, away_score: null, status: "scheduled" },
      ).total,
    ).toBe(0);
  });
});
