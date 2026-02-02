#!/opt/alt/python311/bin/python3
# coding: utf-8

import os
import re
import sys
import logging
import requests
import mysql.connector
import pytz
from bs4 import BeautifulSoup
from datetime import datetime as mydatetime
from pytz.exceptions import AmbiguousTimeError, NonExistentTimeError

# ----------------------------
# Paths + Logging
# ----------------------------
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(SCRIPT_DIR, "fixture_scraper_rlcom_all.log")

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    handlers=[logging.FileHandler(LOG_FILE), logging.StreamHandler()],
)

# ----------------------------
# Database config (TEMP - hardcoded)
# ----------------------------
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "u108848352_Ewka1",
    "password": "WhuiMoFs0X",
    "database": "u108848352_KDqxs",
}

# ----------------------------
# Regex helpers
# ----------------------------
_TIME_RE = re.compile(r"\b([01]?\d|2[0-3]):([0-5]\d)\b")  # 20:00
_ORDINAL_RE = re.compile(r"(\d+)(st|nd|rd|th)", re.IGNORECASE)
_DATE_HEADING_RE = re.compile(
    r"\b(?:Mon|Tue|Wed|Thu|Fri|Sat|Sun)\s+\d{1,2}(?:st|nd|rd|th)\s+[A-Za-z]+\s+\d{4}\b"
)
_ROUND_RE = re.compile(r"Round:\s*([0-9]+)\b", re.IGNORECASE)


def clean_text(s: str) -> str:
    s = (s or "").strip()
    s = re.sub(r"\s+", " ", s)
    return s


def parse_heading_date_to_ymd(heading_text: str):
    if not heading_text:
        return None
    s = heading_text.strip()
    s = _ORDINAL_RE.sub(r"\1", s)  # 12th -> 12
    s = re.sub(r"\s+", " ", s)

    for fmt in ("%a %d %B %Y", "%A %d %B %Y"):
        try:
            dt = mydatetime.strptime(s, fmt)
            return dt.strftime("%Y-%m-%d")
        except ValueError:
            continue
    return None


def extract_time(text: str):
    if not text:
        return None
    m = _TIME_RE.search(text)
    return m.group(0) if m else None


def extract_round_int(text: str):
    if not text:
        return None
    m = _ROUND_RE.search(text)
    if not m:
        return None
    try:
        return int(m.group(1))
    except Exception:
        return None


# ----------------------------
# DB helpers
# ----------------------------
def db_connect():
    try:
        db = mysql.connector.connect(**DB_CONFIG)
        logging.info("✅ Database connection established.")
        return db
    except mysql.connector.Error as err:
        logging.error(f"❌ Database connection error: {err}")
        return None


def fetch_id(cursor, table, name_column, id_column, name):
    cursor.execute(f"SELECT {id_column} FROM {table} WHERE {name_column} = %s", (name,))
    row = cursor.fetchone()
    return row[0] if row else None


# ----------------------------
# Competition mapping (reuse your WP scraper competitions UI table)
# ----------------------------
def resolve_competitions_table(cursor):
    candidates = [
        "wpkl_pool_wpkl_scrape_competitions",
        "pool_wpkl_scrape_competitions",
        "wp_pool_wpkl_scrape_competitions",
        "wp0_pool_wpkl_scrape_competitions",
        "wp1_pool_wpkl_scrape_competitions",
    ]
    for table in candidates:
        try:
            cursor.execute(f"SELECT 1 FROM `{table}` LIMIT 1")
            cursor.fetchone()
            logging.info(f"📦 Using competitions table: {table}")
            return table
        except mysql.connector.Error:
            continue
    logging.error("❌ Could not find competitions table created by the WP plugin.")
    return None


def load_valid_competitions_for_date(db, scrape_date: str):
    """
    Returns dict mapping site competition titles -> config dict:
      {
        "db_name": "...",      # pool_wpkl_matchtypes.name to use
        "rlcom_url": "..."     # match centre URL to scrape
      }

    Only competitions that are active and within optional date ranges for scrape_date.
    """
    cursor = db.cursor(buffered=True)
    table = resolve_competitions_table(cursor)
    if not table:
        cursor.close()
        return {}

    query = f"""
        SELECT bbc_title, db_name, rlcom_url
        FROM `{table}`
        WHERE active = 1
          AND (start_date IS NULL OR %s >= start_date)
          AND (end_date   IS NULL OR %s <= end_date)
    """
    try:
        cursor.execute(query, (scrape_date, scrape_date))
        rows = cursor.fetchall()

        mapping = {}
        for bbc_title, db_name, rlcom_url in rows:
            if not bbc_title or not db_name:
                continue
            mapping[bbc_title.strip()] = {
                "db_name": db_name.strip(),
                "rlcom_url": (rlcom_url or "").strip(),
            }

        if mapping:
            logging.info(f"📘 Active competitions loaded for {scrape_date}: {list(mapping.keys())}")
        else:
            logging.warning("⚠ No active competitions found for this date. Nothing will be scraped.")

        return mapping

    except mysql.connector.Error as err:
        logging.error(f"❌ Error loading competitions from {table}: {err}")
        return {}
    finally:
        cursor.close()


# ----------------------------
# Team alias helpers
# ----------------------------
def resolve_team_id_via_alias(cursor, alias_name: str):
    cursor.execute(
        "SELECT team_id FROM pool_wpkl_team_aliases WHERE alias_name = %s LIMIT 1",
        (alias_name,),
    )
    row = cursor.fetchone()
    if row:
        logging.info(f"🔁 Alias match: '{alias_name}' -> team_id {row[0]}")
        return row[0]
    return None


def ensure_alias(cursor, alias_name: str, team_id: int):
    cursor.execute(
        """
        INSERT INTO pool_wpkl_team_aliases (alias_name, team_id)
        VALUES (%s, %s)
        ON DUPLICATE KEY UPDATE team_id = VALUES(team_id)
        """,
        (alias_name, team_id),
    )


def get_or_create_team_id(db, cursor, team_name: str):
    team_name = clean_text(team_name)
    if not team_name:
        logging.error("❌ Empty team name; cannot fetch/create team.")
        return None

    # 1) Alias lookup
    alias_id = resolve_team_id_via_alias(cursor, team_name)
    if alias_id:
        return alias_id

    # 2) Exact match lookup
    team_id = fetch_id(cursor, "pool_wpkl_teams", "name", "id", team_name)
    if team_id:
        try:
            ensure_alias(cursor, team_name, team_id)
            db.commit()
        except Exception:
            pass
        return team_id

    # 3) Create team (should be rare once aliases are maintained)
    try:
        logging.warning(f"🆕 Team not found in DB. Creating team: {team_name}")
        cursor.execute("INSERT IGNORE INTO pool_wpkl_teams (name) VALUES (%s)", (team_name,))
        db.commit()

        team_id = fetch_id(cursor, "pool_wpkl_teams", "name", "id", team_name)
        if team_id:
            ensure_alias(cursor, team_name, team_id)
            db.commit()
            logging.info(f"✅ Created team '{team_name}' with ID {team_id}")
            return team_id

        logging.error(f"❌ Failed to create team '{team_name}' (no ID after insert).")
        return None
    except mysql.connector.Error as err:
        logging.error(f"❌ Error creating team '{team_name}': {err}")
        return None


def has_date_time_changed(existing_play_date, new_play_date):
    existing_dt = mydatetime.strptime(str(existing_play_date), "%Y-%m-%d %H:%M:%S")
    new_dt = mydatetime.strptime(str(new_play_date), "%Y-%m-%d %H:%M:%S")
    return existing_dt != new_dt


# ----------------------------
# HTTP fetch helper (tries “fixtures tab” variants)
# ----------------------------
def fetch_match_centre_html(url: str, timeout: int = 30):
    headers = {
        "User-Agent": "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36",
        "Accept-Language": "en-GB,en;q=0.9",
    }

    joiner = "&" if "?" in url else "?"
    candidates = [
        url,
        f"{url}{joiner}clear=&fixtures=",
        f"{url}{joiner}fixtures=",
        f"{url}{joiner}tab=fixtures",
    ]

    last_url = url
    last_status = None
    last_html = ""

    for u in candidates:
        try:
            resp = requests.get(u, headers=headers, timeout=timeout)
            last_url = u
            last_status = resp.status_code
            last_html = resp.text or ""

            if resp.status_code != 200:
                logging.warning(f"⚠ GET {u} -> {resp.status_code}")
                continue

            html = last_html
            # quick “fixture-ish” markers
            if ("team-name" in html) or ("venue-label" in html) or ("Round:" in html) or _TIME_RE.search(html):
                return u, html

            logging.info(f"ℹ No fixture markers in HTML for: {u} (len={len(html)})")

        except Exception as e:
            logging.warning(f"⚠ Error fetching {u}: {e}")

    logging.warning(f"⚠ Falling back to last response for {url} (status={last_status}, len={len(last_html)})")
    return last_url, last_html


# ----------------------------
# Scrape helpers (rugby-league.com)
# ----------------------------
def _is_reasonable_fixture_container(node) -> bool:
    """
    Avoid selecting massive wrapper nodes. We want "card-ish" containers.
    """
    try:
        text = node.get_text(" ", strip=True)
    except Exception:
        return False

    if not text:
        return False

    if not _TIME_RE.search(text):
        return False
    if len(node.select("span.team-name")) < 2:
        return False

    # Cap to avoid whole-page containers
    if len(text) > 800:
        return False

    return True


def find_fixture_blocks(soup: BeautifulSoup):
    """
    Primary heuristic: climb parents from span.venue-label.
    Fallback heuristic: scan for smaller containers containing 2 team-name spans + HH:MM time.
    """
    blocks = []
    seen = set()

    # ---- Primary: venue-label anchor ----
    venue_spans = soup.select("span.venue-label")
    logging.info(f"🔎 venue-label spans found: {len(venue_spans)}")

    for vs in venue_spans:
        node = vs
        for _ in range(18):
            if not node or not getattr(node, "parent", None):
                break
            node = node.parent

            if not hasattr(node, "select"):
                continue

            if _is_reasonable_fixture_container(node):
                key = id(node)
                if key not in seen:
                    seen.add(key)
                    blocks.append(node)
                break

    if blocks:
        logging.info(f"🔎 Fixture blocks found (venue-label heuristic): {len(blocks)}")
        return blocks

    # ---- Fallback: no venue-label present ----
    fallback = []
    candidates = soup.select("span.team-name")
    logging.info(f"🔎 team-name spans found: {len(candidates)}")

    for sp in candidates:
        node = sp
        for _ in range(12):
            if not node or not getattr(node, "parent", None):
                break
            node = node.parent
            if not hasattr(node, "select"):
                continue

            if _is_reasonable_fixture_container(node):
                key = id(node)
                if key not in seen:
                    seen.add(key)
                    fallback.append(node)
                break

    # Keep smallest containers and remove nested duplicates
    fallback_sorted = sorted(fallback, key=lambda n: len(n.get_text(" ", strip=True)))
    final = []
    used = set()

    for n in fallback_sorted:
        # Skip if n is within something we already kept
        skip = False
        for kept in final:
            try:
                if kept in n.parents:
                    skip = True
                    break
            except Exception:
                pass
        if skip:
            continue

        k = id(n)
        if k in used:
            continue
        used.add(k)
        final.append(n)

    logging.info(f"🔎 Fixture blocks found (fallback heuristic): {len(final)}")
    return final


def extract_teams_from_block(block):
    """
    Prefer desktop labels to avoid mobile legacy names.
    """
    desktop_spans = block.select("span.team-name.d-none.d-lg-block")
    desktop_names = []
    for sp in desktop_spans:
        txt = clean_text(sp.get_text(" ", strip=True))
        if txt and txt not in desktop_names:
            desktop_names.append(txt)

    if len(desktop_names) >= 2:
        return desktop_names[0], desktop_names[1]

    spans = block.select("span.team-name")
    names = []
    for sp in spans:
        txt = clean_text(sp.get_text(" ", strip=True))
        if txt and txt not in names:
            names.append(txt)

    if len(names) < 2:
        return None, None
    return names[0], names[1]


def find_date_for_block(block):
    prev = block
    for _ in range(600):
        prev = prev.find_previous()
        if not prev:
            break
        if not hasattr(prev, "get_text"):
            continue
        text = prev.get_text(" ", strip=True)
        if not text:
            continue
        m = _DATE_HEADING_RE.search(text)
        if m:
            ymd = parse_heading_date_to_ymd(m.group(0))
            if ymd:
                return ymd
    return None


def extract_venue_from_block(block):
    v = block.select_one("span.venue-label")
    if v:
        txt = clean_text(v.get_text(" ", strip=True))
        txt = txt.replace("Venue:", "").strip()
        return txt or None

    text = clean_text(block.get_text(" ", strip=True))
    m = re.search(r"Venue:\s*([^|]+?)(?:Round:|$)", text, flags=re.IGNORECASE)
    if m:
        return clean_text(m.group(1)).strip() or None

    return None


# ----------------------------
# Core scrape (per competition URL)
# ----------------------------
def scrape_fixtures_for_competition(scrape_date: str, bbc_title: str, url: str):
    logging.info(f"🔍 Scraping RL.com: {bbc_title} -> {url}")

    final_url, html = fetch_match_centre_html(url, timeout=30)

    safe_name = re.sub(r"[^a-zA-Z0-9_-]+", "_", bbc_title).strip("_").lower()

    # ----------------------------
    # Optional HTML dump (store under scripts/rlcom_html/)
    # Enable by running with: RLCOM_SAVE_HTML=1
    # ----------------------------
    SAVE_HTML = os.getenv("RLCOM_SAVE_HTML", "0") == "1"
    if SAVE_HTML:
        html_dir = os.path.join(SCRIPT_DIR, "rlcom_html")
        try:
            os.makedirs(html_dir, exist_ok=True)
            html_path = os.path.join(html_dir, f"rlcom_{safe_name}_{scrape_date}.html")
            with open(html_path, "w", encoding="utf-8") as f:
                f.write(html or "")
            logging.info(
                f"🧾 Saved HTML to: {html_path} (len={len(html or '')}) [from {final_url}]"
            )
        except Exception as e:
            logging.warning(f"⚠ Could not write HTML dump file: {e}")

    if not html:
        logging.error(f"❌ Empty HTML retrieved for {bbc_title} ({url})")
        return []

    soup = BeautifulSoup(html, "html.parser")
    blocks = find_fixture_blocks(soup)
    logging.info(f"🔎 Fixture blocks found: {len(blocks)}")

    fixtures = []

    for b in blocks:
        block_text = b.get_text(" ", strip=True)

        block_date = find_date_for_block(b)
        if not block_date:
            continue
        if block_date != scrape_date:
            continue

        match_time = extract_time(block_text)
        round_int = extract_round_int(block_text)
        home, away = extract_teams_from_block(b)
        venue = extract_venue_from_block(b)

        if not home or not away or not match_time:
            logging.warning("⚠ Skipping block (missing home/away/time).")
            continue

        fixtures.append(
            {
                "competition_bbc_title": bbc_title,
                "home_team": home,
                "away_team": away,
                "match_time": match_time,
                "round": round_int,
                "venue": venue,
            }
        )

    logging.info(f"✅ {bbc_title} fixtures for {scrape_date}: {len(fixtures)}")
    return fixtures


# ----------------------------
# Insert/Update
# ----------------------------
def upsert_fixtures_for_competition(db, fixtures, scrape_date: str, db_competition_name: str):
    cursor = db.cursor()

    matchtype_id = fetch_id(cursor, "pool_wpkl_matchtypes", "name", "id", db_competition_name)
    if not matchtype_id:
        logging.error(f"❌ matchtype_id not found for '{db_competition_name}' in pool_wpkl_matchtypes.")
        cursor.close()
        return

    local_tz = pytz.timezone("Europe/London")

    for fx in fixtures:
        try:
            home_team_id = get_or_create_team_id(db, cursor, fx["home_team"])
            away_team_id = get_or_create_team_id(db, cursor, fx["away_team"])
            if not home_team_id or not away_team_id:
                logging.error(f"❌ Missing team IDs for fixture: {fx}")
                continue

            play_date_local_str = f"{scrape_date} {fx['match_time']}"
            naive = mydatetime.strptime(play_date_local_str, "%Y-%m-%d %H:%M")

            # Handle DST edge cases safely
            try:
                local_dt = local_tz.localize(naive, is_dst=None)
            except AmbiguousTimeError:
                local_dt = local_tz.localize(naive, is_dst=False)
            except NonExistentTimeError:
                local_dt = local_tz.localize(naive, is_dst=True)

            play_date_utc = local_dt.astimezone(pytz.utc).strftime("%Y-%m-%d %H:%M:%S")

            # Find existing match within ~14 days by same teams+matchtype
            check_query = """
                SELECT id, play_date, round
                FROM pool_wpkl_matches
                WHERE home_team_id = %s
                  AND away_team_id = %s
                  AND matchtype_id = %s
                  AND ABS(TIMESTAMPDIFF(HOUR, play_date, %s)) < 336
                LIMIT 1;
            """
            cursor.execute(check_query, (home_team_id, away_team_id, matchtype_id, play_date_utc))
            existing = cursor.fetchone()

            round_val = fx.get("round")

            if existing:
                match_id, existing_play_date, existing_round = existing

                updates = []
                params = []

                if has_date_time_changed(existing_play_date, play_date_utc):
                    updates.append("play_date = %s")
                    params.append(play_date_utc)

                if round_val is not None and (existing_round is None or int(existing_round) != int(round_val)):
                    updates.append("round = %s")
                    params.append(int(round_val))

                if updates:
                    params.append(match_id)
                    cursor.execute(
                        f"UPDATE pool_wpkl_matches SET {', '.join(updates)} WHERE id = %s",
                        tuple(params),
                    )
                    db.commit()
                    logging.info(f"🔄 Updated fixture {match_id}: {fx}")
                else:
                    logging.info(f"✅ No changes required for fixture {match_id}.")
            else:
                default_stadium_id = 2
                insert_query = """
                    INSERT INTO pool_wpkl_matches
                        (home_team_id, away_team_id, play_date, matchtype_id, stadium_id, round)
                    VALUES
                        (%s, %s, %s, %s, %s, %s);
                """
                cursor.execute(
                    insert_query,
                    (
                        home_team_id,
                        away_team_id,
                        play_date_utc,
                        matchtype_id,
                        default_stadium_id,
                        int(round_val) if round_val is not None else None,
                    ),
                )
                db.commit()
                logging.info(f"🆕 Inserted fixture: {fx}")

        except mysql.connector.Error as err:
            logging.error(f"❌ Database error when inserting/updating fixture: {err}")
        except Exception as err:
            logging.error(f"❌ Unexpected error processing fixture {fx}: {err}")

    cursor.close()


# ----------------------------
# Main
# ----------------------------
def main(scrape_date: str):
    db = db_connect()
    if not db:
        return

    # bbc_title -> {db_name, rlcom_url}
    comp_map = load_valid_competitions_for_date(db, scrape_date)
    if not comp_map:
        logging.warning("⚠ No active competitions configured for this date. Nothing to do.")
        db.close()
        return

    total_fixtures = 0

    for bbc_title, cfg in comp_map.items():
        db_name = cfg.get("db_name")
        url = cfg.get("rlcom_url")

        if not url:
            logging.info(f"⏭ No RL.com URL configured for '{bbc_title}' yet; skipping.")
            continue

        fixtures = scrape_fixtures_for_competition(scrape_date, bbc_title, url)
        if not fixtures:
            continue

        upsert_fixtures_for_competition(db, fixtures, scrape_date, db_name)
        total_fixtures += len(fixtures)

    db.close()
    logging.info(f"🏁 Done. Total fixtures processed for {scrape_date}: {total_fixtures}")


if __name__ == "__main__":
    scrape_date = sys.argv[1] if len(sys.argv) > 1 else input("Enter the date (YYYY-MM-DD): ").strip()
    main(scrape_date)