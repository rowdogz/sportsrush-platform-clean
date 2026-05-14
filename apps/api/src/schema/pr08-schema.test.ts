import { describe, expect, it } from "vitest";
import initSqlJs from "sql.js";
import { readFileSync } from "node:fs";
import { resolve } from "node:path";

const migrationPath = resolve(
  __dirname,
  "../../migrations/0008_competitions_teams_fixtures_results.sql",
);

async function createMigratedDb() {
  const SQL = await initSqlJs();
  const db = new SQL.Database();
  db.run("PRAGMA foreign_keys = ON;");
  db.run(readFileSync(migrationPath, "utf8"));
  return db;
}

function seedFixtureDependencies(db: initSqlJs.Database) {
  const now = "2026-05-14T12:00:00.000Z";

  db.run(
    `INSERT INTO sports (id, slug, name, created_at, updated_at)
     VALUES ('sport-rugby-league', 'rugby-league', 'Rugby League', ?, ?)`,
    [now, now],
  );

  db.run(
    `INSERT INTO competitions
       (id, sport_id, slug, name, created_at, updated_at)
     VALUES
       ('comp-super-league', 'sport-rugby-league', 'super-league', 'Super League', ?, ?)`,
    [now, now],
  );

  db.run(
    `INSERT INTO seasons
       (id, competition_id, slug, name, created_at, updated_at)
     VALUES
       ('season-2026', 'comp-super-league', '2026', '2026 Season', ?, ?)`,
    [now, now],
  );

  db.run(
    `INSERT INTO teams
       (id, sport_id, slug, name, created_at, updated_at)
     VALUES
       ('team-wigan', 'sport-rugby-league', 'wigan', 'Wigan Warriors', ?, ?),
       ('team-saints', 'sport-rugby-league', 'saints', 'St Helens', ?, ?)`,
    [now, now, now, now],
  );

  db.run(
    `INSERT INTO rounds
       (id, season_id, round, round_name, display_order, created_at, updated_at)
     VALUES
       ('round-1', 'season-2026', '1', 'Round 1', 1, ?, ?)`,
    [now, now],
  );
}

describe("PR-08 competitions/fixtures schema migration", () => {
  it("creates all core tables", async () => {
    const db = await createMigratedDb();

    const tables = db
      .exec(
        `SELECT name
         FROM sqlite_master
         WHERE type = 'table'
         ORDER BY name`,
      )[0]
      .values.flat();

    expect(tables).toContain("sports");
    expect(tables).toContain("competitions");
    expect(tables).toContain("seasons");
    expect(tables).toContain("teams");
    expect(tables).toContain("team_aliases");
    expect(tables).toContain("competition_teams");
    expect(tables).toContain("rounds");
    expect(tables).toContain("fixtures");
    expect(tables).toContain("result_corrections");
  });

  it("prevents duplicate team aliases per sport", async () => {
    const db = await createMigratedDb();
    seedFixtureDependencies(db);

    const now = "2026-05-14T12:00:00.000Z";

    db.run(
      `INSERT INTO team_aliases
         (id, team_id, sport_id, alias, normalized_alias, created_at, updated_at)
       VALUES
         ('alias-1', 'team-wigan', 'sport-rugby-league', 'Wigan', 'wigan', ?, ?)`,
      [now, now],
    );

    expect(() => {
      db.run(
        `INSERT INTO team_aliases
           (id, team_id, sport_id, alias, normalized_alias, created_at, updated_at)
         VALUES
           ('alias-2', 'team-saints', 'sport-rugby-league', 'Wigan Warriors', 'wigan', ?, ?)`,
        [now, now],
      );
    }).toThrow();
  });

  it("prevents duplicate fixtures within the same season and kickoff", async () => {
    const db = await createMigratedDb();
    seedFixtureDependencies(db);

    const now = "2026-05-14T12:00:00.000Z";
    const kickoff = "2026-02-01T20:00:00.000Z";

    db.run(
      `INSERT INTO fixtures
       (
         id,
         sport_id,
         competition_id,
         season_id,
         round_id,
         round,
         round_name,
         round_order,
         home_team_id,
         away_team_id,
         scheduled_at,
         status,
         created_at,
         updated_at
       )
       VALUES
       (
         'fixture-1',
         'sport-rugby-league',
         'comp-super-league',
         'season-2026',
         'round-1',
         '1',
         'Round 1',
         1,
         'team-wigan',
         'team-saints',
         ?,
         'scheduled',
         ?,
         ?
       )`,
      [kickoff, now, now],
    );

    expect(() => {
      db.run(
        `INSERT INTO fixtures
         (
           id,
           sport_id,
           competition_id,
           season_id,
           round,
           round_name,
           home_team_id,
           away_team_id,
           scheduled_at,
           status,
           created_at,
           updated_at
         )
         VALUES
         (
           'fixture-2',
           'sport-rugby-league',
           'comp-super-league',
           'season-2026',
           '1',
           'Round 1',
           'team-wigan',
           'team-saints',
           ?,
           'scheduled',
           ?,
           ?
         )`,
        [kickoff, now, now],
      );
    }).toThrow();
  });

  it("requires completed fixtures to contain scores", async () => {
    const db = await createMigratedDb();
    seedFixtureDependencies(db);

    const now = "2026-05-14T12:00:00.000Z";

    expect(() => {
      db.run(
        `INSERT INTO fixtures
         (
           id,
           sport_id,
           competition_id,
           season_id,
           round,
           round_name,
           home_team_id,
           away_team_id,
           scheduled_at,
           status,
           created_at,
           updated_at
         )
         VALUES
         (
           'fixture-invalid',
           'sport-rugby-league',
           'comp-super-league',
           'season-2026',
           '1',
           'Round 1',
           'team-wigan',
           'team-saints',
           '2026-02-01T20:00:00.000Z',
           'completed',
           ?,
           ?
         )`,
        [now, now],
      );
    }).toThrow();
  });

  it("allows auditable result corrections", async () => {
    const db = await createMigratedDb();
    seedFixtureDependencies(db);

    const now = "2026-05-14T12:00:00.000Z";

    db.run(
      `INSERT INTO fixtures
       (
         id,
         sport_id,
         competition_id,
         season_id,
         round,
         round_name,
         home_team_id,
         away_team_id,
         scheduled_at,
         status,
         home_score,
         away_score,
         created_at,
         updated_at
       )
       VALUES
       (
         'fixture-completed',
         'sport-rugby-league',
         'comp-super-league',
         'season-2026',
         '1',
         'Round 1',
         'team-wigan',
         'team-saints',
         '2026-02-01T20:00:00.000Z',
         'completed',
         20,
         18,
         ?,
         ?
       )`,
      [now, now],
    );

    db.run(
      `INSERT INTO result_corrections
       (
         id,
         fixture_id,
         previous_status,
         previous_home_score,
         previous_away_score,
         corrected_status,
         corrected_home_score,
         corrected_away_score,
         reason,
         created_at
       )
       VALUES
       (
         'correction-1',
         'fixture-completed',
         'completed',
         20,
         18,
         'completed',
         22,
         18,
         'Video referee correction',
         ?
       )`,
      [now],
    );

    const rows = db.exec(
      `SELECT corrected_home_score
       FROM result_corrections
       WHERE id = 'correction-1'`,
    )[0].values;

    expect(rows[0]?.[0]).toBe(22);
  });
});
