<?php
/**
 * BBC Rugby League score updater
 * - Pulls results from BBC scores/fixtures page for a given date
 * - Only processes competitions that are ACTIVE for that date in wpkl_pool_wpkl_scrape_competitions
 * - Resolves team aliases to your DB team names
 * - Updates pool_wpkl_matches scores (only if match is in the past and score is currently null)
 *
 * Usage:
 *   php scores.php 2026-01-24
 * If no date passed, defaults to yesterday.
 */

$log_file = '/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/scores.log';

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $formatted = "[$timestamp] $message\n";
    file_put_contents($log_file, $formatted, FILE_APPEND);
    echo $formatted;
}

// ------------------------------
// Config
// ------------------------------
$db_host = "localhost";
$db_user = "u108848352_Ewka1";
$db_pass = "WhuiMoFs0X";
$db_name = "u108848352_KDqxs";

$competitions_table = "wpkl_pool_wpkl_scrape_competitions";

// If you already have an aliases table, set the name here.
// If you DON'T have one, leave it as null, and the script will just use the hardcoded map below.
$team_aliases_table = null; // e.g. "pool_wpkl_team_aliases"

// Hardcoded alias map (extend as you find mismatches)
// Keys and values are compared case-insensitively after normalisation.
$hardcoded_team_aliases = [
    // Super League / UK common
    "hull k r" => "hull kingston rovers",
    "hull kr"  => "hull kingston rovers",
    "hull fc"  => "hull f.c.",
    "st helens" => "st helens saints",
    "leeds" => "leeds rhinos",
    "wigan" => "wigan warriors",
    "salford" => "salford red devils",
    "wakefield" => "wakefield trinity",
    "castleford" => "castleford tigers",

    // If BBC uses shortened / different punctuation:
    "leigh" => "leigh leopards",

    // Add more as needed…
];

// ------------------------------
// Helpers
// ------------------------------
function normalise_name($s) {
    $s = trim(mb_strtolower($s, 'UTF-8'));
    $s = preg_replace('/\s+/', ' ', $s);
    // Normalise apostrophes and dots a bit
    $s = str_replace(["’", "‘", "`"], "'", $s);
    return $s;
}

function http_get($url) {
    // Use cURL with a browser UA to avoid bot-lite HTML
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 25,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122 Safari/537.36",
        CURLOPT_HTTPHEADER => [
            "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
            "Accept-Language: en-GB,en;q=0.9",
        ],
    ]);
    $html = curl_exec($ch);
    $err  = curl_error($ch);
    $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($html === false || $code >= 400) {
        log_message("❌ HTTP fetch failed ($code): $err");
        return null;
    }
    return $html;
}

function load_active_competitions(mysqli $conn, string $table, string $scrape_date): array {
    // Returns map of normalised BBC title => db_name
    // Only active competitions within date window
    $sql = "
        SELECT bbc_title, db_name
        FROM {$table}
        WHERE active = 1
          AND (start_date IS NULL OR start_date <= ?)
          AND (end_date IS NULL OR end_date >= ?)
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        log_message("❌ Failed to prepare competitions query: " . $conn->error);
        return [];
    }
    $stmt->bind_param("ss", $scrape_date, $scrape_date);
    $stmt->execute();
    $res = $stmt->get_result();

    $map = [];
    while ($row = $res->fetch_assoc()) {
        $bbc = normalise_name($row['bbc_title']);
        $dbn = $row['db_name'];
        $map[$bbc] = $dbn;
    }
    $stmt->close();

    $names = array_values($map);
    log_message("📘 Active competitions loaded for {$scrape_date}: " . (empty($names) ? "NONE" : "['" . implode("', '", $names) . "']"));

    return $map;
}

function resolve_team_to_db_name(mysqli $conn, string $team_name, array $hardcoded_aliases, ?string $aliases_table): string {
    $raw = trim($team_name);
    $n = normalise_name($raw);

    // 1) Hardcoded alias map
    if (isset($hardcoded_aliases[$n])) {
        return $hardcoded_aliases[$n];
    }

    // 2) Optional DB alias table
    if ($aliases_table) {
        // Expected structure example:
        // alias_name (varchar), team_name (varchar) OR team_id (int)
        // Adjust this query if your table differs.
        $sql = "SELECT team_name FROM {$aliases_table} WHERE LOWER(alias_name) = ? LIMIT 1";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $n);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $stmt->close();
                return $row['team_name'];
            }
            $stmt->close();
        }
    }

    // 3) Return as-is (normalised later by DB lookup)
    return $raw;
}

function find_team_id(mysqli $conn, string $team_name): ?int {
    // Try exact match first, then a normalised match
    $sql = "SELECT id FROM pool_wpkl_teams WHERE name = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $team_name);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $stmt->close();
            return (int)$row['id'];
        }
        $stmt->close();
    }

    // Normalised fallback (lower + trim multiple spaces)
    $sql2 = "SELECT id FROM pool_wpkl_teams WHERE LOWER(TRIM(name)) = ? LIMIT 1";
    $stmt2 = $conn->prepare($sql2);
    if ($stmt2) {
        $norm = normalise_name($team_name);
        $stmt2->bind_param("s", $norm);
        $stmt2->execute();
        $res2 = $stmt2->get_result();
        if ($row2 = $res2->fetch_assoc()) {
            $stmt2->close();
            return (int)$row2['id'];
        }
        $stmt2->close();
    }

    return null;
}

function parse_bbc_scores(string $scrape_date, array $active_competitions_map, mysqli $conn, array $hardcoded_aliases, ?string $aliases_table): array {
    // Returns list of results:
    // [
    //   'competition_db_name' => 'Super League 2026',
    //   'home_team_id' => 1,
    //   'away_team_id' => 2,
    //   'home_score' => 12,
    //   'away_score' => 18,
    //   'home_team_raw' => 'Hull KR',
    //   'away_team_raw' => 'Leeds Rhinos',
    // ]
    $url = "https://www.bbc.co.uk/sport/rugby-league/scores-fixtures/{$scrape_date}";
    log_message("🔍 Scraping BBC: {$url}");

    $html = http_get($url);
    if (!$html) {
        log_message("❌ No HTML retrieved from BBC for {$scrape_date}");
        return [];
    }

    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($html);
    $xp = new DOMXPath($dom);

    // BBC pages change markup often. This tries a few patterns.
    // We want match blocks plus a nearby competition header.
    $matchNodes = $xp->query("//*[contains(@class,'ssrcss-1bjtunb-GridContainer') or contains(@class,'ssrcss-1lb7uaz-GridContainer')]");
    if (!$matchNodes || $matchNodes->length === 0) {
        // Fallback: any element with home/away team value classes
        $matchNodes = $xp->query("//*[.//*[contains(@class,'TeamHome')] and .//*[contains(@class,'TeamAway')]]");
    }

    log_message("🔎 Match containers found: " . ($matchNodes ? $matchNodes->length : 0));

    $results = [];

    foreach ($matchNodes as $node) {
        // Find league header by walking up and looking for an H2 group header
        $league = null;
        $p = $node;
        for ($i=0; $i<8 && $p; $i++) {
            $h2 = $xp->query(".//h2[contains(@class,'GroupHeader')]", $p);
            if ($h2 && $h2->length > 0) {
                $league = trim($h2->item(0)->textContent);
                break;
            }
            $p = $p->parentNode;
        }

        if (!$league) {
            // Sometimes header is just before the block, so look in previous siblings
            $sib = $node->previousSibling;
            for ($j=0; $j<10 && $sib; $j++) {
                if ($sib->nodeType === XML_ELEMENT_NODE) {
                    $h2b = $xp->query(".//h2[contains(@class,'GroupHeader')]", $sib);
                    if ($h2b && $h2b->length > 0) {
                        $league = trim($h2b->item(0)->textContent);
                        break;
                    }
                }
                $sib = $sib->previousSibling;
            }
        }

        if (!$league) {
            continue;
        }

        $leagueNorm = normalise_name($league);

        // Only process if league is in active competitions list
        if (!isset($active_competitions_map[$leagueNorm])) {
            continue;
        }
        $competition_db_name = $active_competitions_map[$leagueNorm];

        // Extract teams
        $home = null; $away = null;

        $homeNode = $xp->query(".//*[contains(@class,'TeamHome')]//*[contains(@class,'DesktopValue')]", $node);
        if ($homeNode && $homeNode->length > 0) $home = trim($homeNode->item(0)->textContent);

        $awayNode = $xp->query(".//*[contains(@class,'TeamAway')]//*[contains(@class,'DesktopValue')]", $node);
        if ($awayNode && $awayNode->length > 0) $away = trim($awayNode->item(0)->textContent);

        if (!$home || !$away) {
            continue;
        }

        // Extract scores
        $hs = null; $as = null;

        $hsNode = $xp->query(".//*[contains(@class,'HomeScore')]", $node);
        if ($hsNode && $hsNode->length > 0) $hs = trim($hsNode->item(0)->textContent);

        $asNode = $xp->query(".//*[contains(@class,'AwayScore')]", $node);
        if ($asNode && $asNode->length > 0) $as = trim($asNode->item(0)->textContent);

        // Only accept numeric scores
        if ($hs === null || $as === null) continue;
        if (!preg_match('/^\d+$/', $hs) || !preg_match('/^\d+$/', $as)) continue;

        $home_score = (int)$hs;
        $away_score = (int)$as;

        // Resolve aliases to DB names, then to IDs
        $homeResolved = resolve_team_to_db_name($conn, $home, $hardcoded_aliases, $aliases_table);
        $awayResolved = resolve_team_to_db_name($conn, $away, $hardcoded_aliases, $aliases_table);

        $home_id = find_team_id($conn, $homeResolved);
        $away_id = find_team_id($conn, $awayResolved);

        if (!$home_id || !$away_id) {
            log_message("⚠ Team not found in DB (league={$competition_db_name}): '{$home}' -> '{$homeResolved}' (id=" . ($home_id ?? 'null') . "), '{$away}' -> '{$awayResolved}' (id=" . ($away_id ?? 'null') . ")");
            continue;
        }

        $results[] = [
            'competition_db_name' => $competition_db_name,
            'home_team_id' => $home_id,
            'away_team_id' => $away_id,
            'home_score' => $home_score,
            'away_score' => $away_score,
            'home_team_raw' => $home,
            'away_team_raw' => $away,
        ];
    }

    // De-dupe (BBC sometimes repeats nodes)
    $dedup = [];
    $final = [];
    foreach ($results as $r) {
        $k = $r['competition_db_name'] . "|" . $r['home_team_id'] . "|" . $r['away_team_id'] . "|" . $r['home_score'] . "|" . $r['away_score'];
        if (!isset($dedup[$k])) {
            $dedup[$k] = true;
            $final[] = $r;
        }
    }

    log_message("✅ BBC results parsed for {$scrape_date}: " . count($final));
    return $final;
}

function update_scores_in_db(mysqli $conn, array $results, string $scrape_date): int {
    $updated = 0;

    // Find a match in DB by: date + competition name + home/away ids, and only if score null and match is in the past.
    $sql_find = "
        SELECT m.id
        FROM pool_wpkl_matches m
        JOIN pool_wpkl_matchtypes mt ON mt.id = m.matchtype_id
        WHERE DATE(m.play_date) = ?
          AND mt.name = ?
          AND m.home_team_id = ?
          AND m.away_team_id = ?
          AND m.play_date < NOW()
          AND (m.home_score IS NULL OR m.away_score IS NULL)
        LIMIT 1
    ";

    $sql_update = "UPDATE pool_wpkl_matches SET home_score = ?, away_score = ? WHERE id = ?";

    $stmt_find = $conn->prepare($sql_find);
    $stmt_upd  = $conn->prepare($sql_update);

    if (!$stmt_find || !$stmt_upd) {
        log_message("❌ Prepare failed: " . $conn->error);
        return 0;
    }

    foreach ($results as $r) {
        $comp = $r['competition_db_name'];
        $hid  = $r['home_team_id'];
        $aid  = $r['away_team_id'];
        $hs   = $r['home_score'];
        $as   = $r['away_score'];

        $stmt_find->bind_param("ssii", $scrape_date, $comp, $hid, $aid);
        $stmt_find->execute();
        $res = $stmt_find->get_result();
        $row = $res->fetch_assoc();

        if (!$row) {
            log_message("⚠ No DB fixture match for {$scrape_date} ({$comp}): {$r['home_team_raw']} vs {$r['away_team_raw']} ({$hs}-{$as})");
            continue;
        }

        $match_id = (int)$row['id'];

        $stmt_upd->bind_param("iii", $hs, $as, $match_id);
        if ($stmt_upd->execute()) {
            $updated++;
            log_message("✅ Updated: {$scrape_date} ({$comp}) {$r['home_team_raw']} vs {$r['away_team_raw']} => {$hs}-{$as} (match_id={$match_id})");
        } else {
            log_message("❌ Update failed for match_id={$match_id}: " . $conn->error);
        }
    }

    $stmt_find->close();
    $stmt_upd->close();

    return $updated;
}

// ------------------------------
// Main
// ------------------------------
$scrape_date = $argv[1] ?? date('Y-m-d', strtotime('-1 day'));
log_message("🏁 Starting BBC score update for date: {$scrape_date}");

// Connect
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    log_message("❌ Database connection failed: " . $conn->connect_error);
    exit(1);
}
$conn->set_charset("utf8mb4");
log_message("✅ Database connection established.");
log_message("📦 Using competitions table: {$competitions_table}");

$active_comps = load_active_competitions($conn, $competitions_table, $scrape_date);
if (empty($active_comps)) {
    log_message("⚠ No active competitions for {$scrape_date}. Exiting.");
    $conn->close();
    exit(0);
}

$results = parse_bbc_scores($scrape_date, $active_comps, $conn, $hardcoded_team_aliases, $team_aliases_table);

if (empty($results)) {
    log_message("⚠ No BBC results found/parsed for {$scrape_date} (either no matches or markup changed).");
    $conn->close();
    exit(0);
}

$updated = update_scores_in_db($conn, $results, $scrape_date);
log_message("🏁 Done. Total results parsed: " . count($results) . ". Total updated: {$updated}.");

$conn->close();