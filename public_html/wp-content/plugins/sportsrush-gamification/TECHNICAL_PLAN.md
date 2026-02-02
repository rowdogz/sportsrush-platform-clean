# SportsRush Gamification Technical Plan

## Discovery Summary

### Existing Data Model
The site uses the Football Pool plugin with custom extensions. Key tables:

**Core Tables (prefix: pool_wpkl_)**
- `pool_wpkl_matches` - Fixtures with home/away teams, scores, play_date, matchtype_id (competition), round
- `pool_wpkl_predictions` - User predictions (user_id, match_id, home_score, away_score, has_joker)
- `pool_wpkl_matchtypes` - Competitions (id, name, visibility)
- `pool_wpkl_scorehistory_s1_t1/t2` - Score history with ranking, total_score per user per match
- `pool_wpkl_leagues` - Built-in leagues (not used for private leagues)
- `pool_wpkl_league_users` - League membership
- `pool_wpkl_teams` - Team data

**Custom Tables**
- `custom_competitions` - Private leagues (id, name, matchtype_id, is_private, is_paid, price_gbp, etc.)
- `custom_competition_users` - Private league membership (user_id, custom_competition_id)

**WordPress Tables (prefix: wpkl_)**
- `wpkl_users` - WordPress users
- `wpkl_usermeta` - User metadata
- `wpkl_options` - WordPress options (will store feature flags)

### Key Observations
1. Competitions are called "matchtypes" in the DB
2. Private leagues link to matchtypes via matchtype_id
3. Score history already tracks ranking per user per match
4. Round info stored in matches table (round, round_name, round_order)

## New Tables Design

All new tables will use prefix `sr_` (SportsRush) for clarity:

```sql
-- Feature flags stored in wpkl_options as 'sr_feature_flags' (JSON)

-- Daily Pick system
CREATE TABLE sr_daily_picks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pick_date DATE NOT NULL UNIQUE,
    competition_id INT UNSIGNED NULL,
    fixture_id INT UNSIGNED NULL,
    pick_type ENUM('winner', 'total_points_band', 'margin_band') NOT NULL DEFAULT 'winner',
    pick_payload JSON,
    lock_time DATETIME NOT NULL,
    settle_time DATETIME NULL,
    settled TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pick_date (pick_date)
);

CREATE TABLE sr_daily_pick_entries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    daily_pick_id INT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    entry_payload JSON NOT NULL,
    points_awarded INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_pick (daily_pick_id, user_id),
    INDEX idx_daily_pick (daily_pick_id)
);

-- Streaks
CREATE TABLE sr_user_streaks (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    login_streak_count INT UNSIGNED NOT NULL DEFAULT 0,
    login_last_date DATE NULL,
    prediction_streak_count INT UNSIGNED NOT NULL DEFAULT 0,
    prediction_streak_last_fixture_id INT UNSIGNED NULL,
    shields_available INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Achievements
CREATE TABLE sr_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    achievement_key VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon VARCHAR(100) DEFAULT 'trophy',
    points INT UNSIGNED DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sr_user_achievements (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    achievement_id INT UNSIGNED NOT NULL,
    awarded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_achievement (user_id, achievement_id),
    INDEX idx_user (user_id)
);

-- Rivals
CREATE TABLE sr_user_rivals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    context_type ENUM('global', 'competition', 'league') NOT NULL,
    context_id INT UNSIGNED NULL,
    rival_user_id BIGINT UNSIGNED NULL,
    chasing_user_id BIGINT UNSIGNED NULL,
    computed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_context (user_id, context_type, context_id),
    INDEX idx_user (user_id)
);

-- Leaderboard Snapshots
CREATE TABLE sr_leaderboard_snapshots (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    context_type ENUM('global', 'competition', 'league') NOT NULL,
    context_id INT UNSIGNED NULL,
    competition_id INT UNSIGNED NULL,
    round_number INT NULL,
    snapshot_payload JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_context (context_type, context_id, competition_id, round_number)
);

-- Notifications
CREATE TABLE sr_notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    notification_type ENUM('rival_overtook', 'deadline_soon', 'rank_change', 'banter_ready', 'daily_pick_ready', 'achievement_earned') NOT NULL,
    payload JSON,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_unread (user_id, is_read, created_at)
);

-- Banter Summaries
CREATE TABLE sr_banter_summaries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    league_id INT UNSIGNED NOT NULL,
    week_start DATE NOT NULL,
    summary_payload JSON NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_league_week (league_id, week_start)
);
```

## Feature Flags

Stored in `wpkl_options` as `sr_feature_flags`:
```json
{
    "rivals_enabled": true,
    "position_changes_enabled": true,
    "daily_pick_enabled": true,
    "streaks_enabled": true,
    "mini_leaderboards_enabled": true,
    "achievements_enabled": true,
    "banter_summaries_enabled": true,
    "smart_notifications_enabled": true
}
```

## Implementation Order

1. **Feature Flags + Admin UI** - Foundation for all features
2. **Database Migration** - Create all tables
3. **Snapshots + Position Deltas** - Core ranking change tracking
4. **Rivals System** - User above/below detection
5. **Mini-leaderboards** - Current round, last 5, last 7 days views
6. **Daily Pick** - Daily prediction game
7. **Streaks** - Login and prediction streaks with shields
8. **Achievements** - Badge system with auto-awarding
9. **Banter Summaries** - Weekly league summaries
10. **Notifications** - Bell icon with unread count

## Cron Schedule

- Daily Pick Generator: 09:00 UK time daily
- Snapshot Builder: 01:00 UK time nightly + after results
- Achievements Awarding: 02:00 UK time nightly
- Banter Summaries: 09:00 UK time Monday weekly

## File Structure

```
wp-content/plugins/sportsrush-gamification/
├── sportsrush-gamification.php          # Main plugin file
├── includes/
│   ├── class-sr-activator.php           # Activation/migration
│   ├── class-sr-feature-flags.php       # Feature flag management
│   ├── class-sr-snapshots.php           # Leaderboard snapshots
│   ├── class-sr-rivals.php              # Rival computation
│   ├── class-sr-mini-leaderboards.php   # Mini leaderboard views
│   ├── class-sr-daily-pick.php          # Daily pick logic
│   ├── class-sr-streaks.php             # Streak tracking
│   ├── class-sr-achievements.php        # Achievement system
│   ├── class-sr-banter.php              # Banter generation
│   ├── class-sr-notifications.php       # Notification system
│   └── class-sr-cron.php                # Cron job management
├── admin/
│   ├── class-sr-admin.php               # Admin menu/pages
│   └── views/
│       └── admin-gamification.php       # Admin settings page
├── public/
│   ├── class-sr-public.php              # Frontend hooks
│   ├── js/
│   │   └── sr-gamification.js           # Frontend JS
│   └── css/
│       └── sr-gamification.css          # Frontend styles
└── README.md
```
