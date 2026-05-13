# SportsRush — Scoring and Rankings Engine

## Overview

SportsRush uses a **dual-layer scoring system**. The Football Pool core plugin provides the base infrastructure (history tables, calculation pipeline, admin triggers), but the SportsRush custom rankings plugin (`football-pool-custom-rankings`) has overridden the points formula and display with its own SQL-driven implementation. In many cases, the custom SQL query is the source of truth displayed to users rather than the history tables.

---

## Points Logic

The scoring formula is defined in the custom rankings plugin and applied inline via SQL. Points are calculated per match prediction against the actual result.

### Scoring Breakdown

| Scenario | Points |
|----------|--------|
| **Exact score match** (correct home AND away score) | **50 pts** |
| **Correct winner / draw (Toto)** — right outcome but wrong scores | **20 pts** |
| **Correct home score** (regardless of outcome) | **10 pts** |
| **Correct away score** (regardless of outcome) | **10 pts** |
| **Correct goal difference** — right outcome AND right margin | **20 pts** |
| **Wrong outcome** | 0 pts |

### Point Category Rules

These rules are applied as independent `CASE WHEN` blocks and **summed together** for each prediction:

```sql
-- 1. Exact score (50 pts, takes precedence)
CASE
  WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 50
  ELSE 0
END

-- 2. Toto (20 pts — correct outcome, not exact)
CASE
  WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0  -- already got 50
  WHEN (home wins both, away wins both, or both draw) THEN 20
  ELSE 0
END

-- 3. Correct home score (10 pts)
CASE WHEN m.home_score = p.home_score THEN 10 ELSE 0 END

-- 4. Correct away score (10 pts)
CASE WHEN m.away_score = p.away_score THEN 10 ELSE 0 END

-- 5. Correct goal difference (20 pts — only when outcome is also correct)
CASE
  WHEN m.home_score = p.home_score AND m.away_score = p.away_score THEN 0  -- already exact
  WHEN (correct outcome)
    AND (GREATEST(m.home_score, m.away_score) - LEAST(m.home_score, m.away_score))
      = (GREATEST(p.home_score, p.away_score) - LEAST(p.home_score, p.away_score))
  THEN 20
  ELSE 0
END
```

### Maximum Points Per Match (without joker)

An exact score prediction scores all applicable categories:
- Exact score: 50 pts
- Correct home score: 10 pts
- Correct away score: 10 pts
- (Toto and goal difference are 0 when exact — avoids double-counting)

**Maximum per match = 70 points** (50 + 10 + 10)

A correct outcome with correct goal difference but wrong scores:
- Toto: 20 pts
- Goal difference: 20 pts
- Plus either/both individual scores: up to 20 pts more

**Best non-exact outcome = up to 60 points**

### Joker Multiplier

Users can designate one (or more, depending on configuration) match per round as their **joker**. The joker is stored in `pool_wpkl_predictions.has_joker = 1`.

- When the Football Pool core plugin calculates scores, joker matches are multiplied by the configured factor (typically **2×**).
- The joker can only be set on a match that has not yet kicked off.
- The custom SQL rankings query in `football-pool-custom-rankings` **does not currently apply the joker multiplier** — it queries raw scores without reading `has_joker`. This is a known discrepancy between the two scoring paths.
- Joker logic is validated server-side: if jokers are disabled globally, or the match is not editable, the joker cannot be set.

---

## Rankings Logic

### Two Rankings Pathways

**Pathway A — Football Pool History Tables:**
The core Football Pool plugin maintains `pool_wpkl_scorehistory_s1_t1` and `pool_wpkl_scorehistory_s1_t2`. These are pre-computed snapshots updated whenever an admin runs `wp football-pool calc`. The two tables alternate (one is "active", one is being written) to ensure users never see partial results during calculation.

**Pathway B — Live SQL Query (Custom Rankings):**
The custom rankings shortcode bypasses the history tables and calculates scores live from `pool_wpkl_predictions` joined with `pool_wpkl_matches`. This is what users see on the main `/rankings/` page. It is the more accurate and up-to-date source.

### Calculation Process (Core Plugin — Pathway A)

The `Football_Pool_Admin_Score_Calculation::process()` function works in phases:
1. **Prepare** — empty the inactive history table, lock the calculation flag in `wpkl_options`.
2. **Per-user, per-match** — loop through every user × every scored match in the ranking's scope, calculate points, write to the history table.
3. **Per-user, per-bonus-question** — add bonus question points.
4. **Finalize** — swap the active and inactive table references, release the lock.

The process supports both **Full** (complete recalculation, full history) and **Simple** (faster, only updates deltas) calculation modes, controlled via a plugin option.

### Ranking Scope

Rankings are scoped by:
- **Season** (`pool_wpkl_seasons`)
- **Ranking definition** (`pool_wpkl_rankings`) — each ranking links to a specific set of matches via `pool_wpkl_rankings_matches` and a set of bonus questions via `pool_wpkl_rankings_bonusquestions`
- **Competition filter** — the custom rankings shortcode filters by `matchtype_id` (competition) using the `?competition=ID` URL parameter

---

## Tie-Breakers

Within the Football Pool core plugin, ties are broken in order:
1. Most **exact scores** (full points)
2. Most **toto scores** (correct outcome)
3. Most **correct bonus question answers**

Within the custom SQL rankings (Pathway B), the `DENSE_RANK()` window function is used:
```sql
DENSE_RANK() OVER (ORDER BY total_points DESC) AS user_rank
```
Players with equal `total_points` receive the same rank. There is **no secondary tie-breaker** in the custom SQL implementation. This means multiple users can share the same position number, and the next rank is not skipped (DENSE_RANK vs RANK).

---

## Competition Filtering

- The custom rankings shortcode reads `?competition=<matchtype_id>` from the URL.
- Only competitions where `pool_wpkl_matchtypes.visibility = 1` appear in the dropdown.
- The SQL query adds `WHERE m.matchtype_id = %d` to scope all calculations to the selected competition.
- A default competition is shown if no `?competition=` parameter is present (first visible competition in alphabetical order).

### Excluded Competitions for Monthly Winner

The custom rankings plugin hard-codes certain competition IDs to exclude from the Monthly Winner display:
```php
$excluded_monthly_winner_ids = [32, 34];
```
These appear to be bonus-round or cup competitions where monthly tracking doesn't make sense.

---

## Monthly Winners

The custom rankings shortcode calculates a separate **current month points** total alongside the overall total:

```sql
SUM(
  CASE
    WHEN MONTH(m.play_date) = MONTH(NOW()) AND YEAR(m.play_date) = YEAR(NOW())
    THEN [full scoring formula]
    ELSE 0
  END
) AS current_month_points
```

The user with the highest `current_month_points` is displayed as the "Monthly Winner" at the top of the rankings table (for non-excluded competitions). Last month's winner is also calculated and displayed separately.

**Important assumption:** Monthly winner is based on `m.play_date` (kick-off time), not the date predictions were made. This is correct for competition purposes but means a postponed match that gets replayed in a different month will count towards the new month's total.

---

## Edge Cases and Technical Concerns

### 1. Dual Scoring Paths Out of Sync
The custom SQL rankings (Pathway B) and the Football Pool history tables (Pathway A) can diverge because:
- Pathway B does not apply joker multipliers.
- Pathway B is live (always current); Pathway A requires a manual/scheduled recalculation trigger.
- Any discrepancy means the number shown on the rankings page may differ from what the Football Pool plugin's admin reports.

### 2. Goal Difference Bug in Monthly Calculation
In the monthly points SQL block, there is a subtle inconsistency:
```sql
-- Overall uses: LEAST(p.home_score, p.away_score)
-- Monthly uses: LEAST(p.away_score, p.home_score)  ← argument order swapped
```
This is equivalent for `LEAST()` (order doesn't matter) but suggests the two blocks were written separately and could diverge if logic changes.

### 3. Matches Without Scores
The custom SQL has `WHERE m.home_score IS NOT NULL AND m.away_score IS NOT NULL` — matches without scores are correctly excluded. However, there is no guard against scores of 0 being misinterpreted as NULL. In MariaDB, `0 IS NOT NULL` is true, so 0-0 results are handled correctly.

### 4. No Real-Time Score Updates
Rankings are not updated in real-time as matches progress. Score recalculation is a batch process triggered manually or by cron. Users will not see live points changes during a match.

### 5. Bonus Questions Not Reflected in Custom Rankings
The custom SQL rankings shortcode only considers `pool_wpkl_predictions` × `pool_wpkl_matches`. It does not include bonus question points. If bonus questions are actively used, the displayed total points will be understated compared to the Football Pool plugin's full calculation.

### 6. Calculation Lock Risk
The Football Pool score calculation sets a `calculation_in_progress = 1` flag in `wpkl_options`. If a calculation is interrupted (server crash, timeout), this flag may remain set, blocking future calculations. The admin can force-override via the dashboard, but this is a manual intervention.

### 7. DENSE_RANK Performance on Large User Sets
The custom SQL uses a window function subquery. On MariaDB 10.11, `DENSE_RANK() OVER (ORDER BY ...)` is supported but can be slow without proper indexes on `pool_wpkl_predictions(match_id)` and `pool_wpkl_matches(matchtype_id, home_score, away_score)`. With hundreds of users and thousands of matches, this query may become sluggish without query caching (LiteSpeed Cache may help at the page level).
