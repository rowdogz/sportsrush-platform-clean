# SportsRush Gamification Plugin

A comprehensive gamification layer for SportsRush/Football Pool WordPress sites. Adds rivals, position changes, daily picks, streaks, achievements, banter summaries, and smart notifications.

## Requirements

- WordPress 5.3+
- PHP 7.4+
- Football Pool plugin (v2.13.1 or compatible)

## Installation

1. Upload the `sportsrush-gamification` folder to `/wp-content/plugins/`
2. Activate the plugin through the WordPress admin
3. Navigate to **SR Gamification** (or **Football Pool → Gamification**) to configure

## Feature Flags

All features can be independently enabled/disabled via the admin panel:

| Flag | Description |
|------|-------------|
| `rivals_enabled` | Show rival users (above/below in rankings) |
| `position_changes_enabled` | Display position change arrows on leaderboards |
| `daily_pick_enabled` | Daily prediction game with bonus points |
| `streaks_enabled` | Login and prediction streak tracking |
| `mini_leaderboards_enabled` | Current round, last 5 fixtures, last 7 days views |
| `achievements_enabled` | Badges and achievement system |
| `banter_summaries_enabled` | Weekly league performance summaries |
| `smart_notifications_enabled` | In-app notification bell |

## Cron Schedules

| Job | Schedule | Description |
|-----|----------|-------------|
| Daily Pick Generator | Daily 09:00 UK | Creates the day's daily pick |
| Snapshot Builder | Daily 01:00 UK | Builds leaderboard snapshots for position tracking |
| Achievements Processor | Daily 02:00 UK | Awards achievements to qualifying users |
| Banter Generator | Weekly Monday 09:00 UK | Generates weekly league banter summaries |
| Daily Pick Settler | Hourly | Settles completed daily picks and awards points |
| Deadline Checker | Every 15 minutes | Sends deadline warning notifications |

### Running Cron Jobs Manually

1. Go to **SR Gamification → Cron Jobs**
2. Click **Run Now** next to any job

This is safe to run at any time and useful for testing or catching up after downtime.

## Recomputing Snapshots Safely

If you need to rebuild all leaderboard snapshots (e.g., after data import or correction):

1. Go to **SR Gamification → Cron Jobs**
2. Click **Run Now** next to "Snapshot Builder"

This will:
- Build global leaderboard snapshot
- Build per-competition snapshots
- Build per-league snapshots
- Refresh all user rivals

The process is additive and won't delete existing data. Old snapshots are preserved for historical comparison.

## Shortcodes

Use these shortcodes to display gamification widgets:

```
[sr_today_widget]           - Combined daily pick, streaks, and rivals
[sr_daily_pick]             - Daily pick widget only
[sr_streaks]                - User's streak information
[sr_achievements]           - User's achievements
[sr_rivals]                 - User's rivals widget
[sr_mini_leaderboards]      - Tabbed mini leaderboards
[sr_banter league_id="123"] - League banter summary
```

### Shortcode Parameters

**sr_today_widget / sr_streaks / sr_achievements / sr_rivals:**
- `user_id` - Display for specific user (default: current user)
- `context` - 'global' or 'league' (default: global)
- `context_id` - League ID if context is 'league'

**sr_mini_leaderboards:**
- `competition_id` - Filter by competition
- `league_id` - Filter by private league

**sr_banter:**
- `league_id` - Required. The private league ID

## Database Tables

The plugin creates these tables (prefixed with `sr_`):

| Table | Purpose |
|-------|---------|
| `sr_daily_picks` | Daily pick definitions |
| `sr_daily_pick_entries` | User entries for daily picks |
| `sr_user_streaks` | Login and prediction streak data |
| `sr_achievements` | Achievement definitions |
| `sr_user_achievements` | Awarded achievements |
| `sr_user_rivals` | Cached rival relationships |
| `sr_leaderboard_snapshots` | Historical ranking snapshots |
| `sr_notifications` | User notifications |
| `sr_banter_summaries` | Weekly league banter |

## Default Achievements

| Key | Name | Description |
|-----|------|-------------|
| `perfect_round` | Perfect Round | All predictions correct in a round |
| `underdog_hunter` | Underdog Hunter | Predicted 5 underdog wins correctly |
| `big_climber` | Big Climber | Gained 10+ positions in one round |
| `consistent` | Consistent | Top 25% for 5 rounds in a row |
| `daily_grinder` | Daily Grinder | 7-day login streak |
| `prediction_streak_5` | Hot Streak | 5 correct predictions in a row |
| `prediction_streak_10` | On Fire | 10 correct predictions in a row |
| `first_prediction` | First Steps | Made your first prediction |
| `daily_pick_winner` | Daily Pick Pro | Won 10 daily picks |
| `league_champion` | League Champion | Won a private league |
| `top_10_finish` | Top 10 | Finished in the top 10 |
| `joker_master` | Joker Master | Won 5 joker bets |

## Settings

Configure via **SR Gamification → Settings**:

**Daily Pick:**
- Points for correct pick (default: 3)
- Points for partial correct (default: 1)

**Streaks:**
- 7-day streak bonus (default: 5)
- 14-day streak bonus (default: 10)
- 30-day streak bonus (default: 25)

**Notifications:**
- Deadline warning minutes (default: 60)

## Hooks & Filters

### Actions

```php
// After daily pick is generated
do_action('sr_daily_pick_generated', $pick_id, $fixture_id);

// After achievement is awarded
do_action('sr_achievement_awarded', $user_id, $achievement_id);

// After snapshots are built
do_action('sr_snapshots_built');
```

### Filters

```php
// Modify position change indicator HTML
apply_filters('sr_position_indicator_html', $html, $change, $direction);

// Modify banter templates
apply_filters('sr_banter_templates', $templates);

// Modify achievement check logic
apply_filters('sr_should_award_achievement', $should_award, $user_id, $achievement_key);
```

## Troubleshooting

**Features not showing:**
- Check feature flags are enabled in admin
- Verify Football Pool plugin is active
- Clear any caching plugins

**Position changes not updating:**
- Run "Snapshot Builder" manually
- Check cron is running (use WP Crontrol plugin to verify)

**Notifications not appearing:**
- Ensure `smart_notifications_enabled` is on
- Check user is logged in
- Verify JavaScript is loading (check browser console)

## Support

For issues or feature requests, contact the SportsRush team.

## Changelog

### 1.0.0
- Initial release
- Feature flags system
- Position change indicators
- Rivals system
- Mini-leaderboards (current round, last 5, last 7 days)
- Daily pick game
- Login and prediction streaks with shields
- Achievements and badges
- Weekly banter summaries
- Smart notifications
