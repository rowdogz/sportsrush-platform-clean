#!/opt/alt/python311/bin/python3
# coding: utf-8

import os
import re
import json
import logging
import requests
import mysql.connector
from bs4 import BeautifulSoup
from datetime import datetime

# ----------------------------
# Paths + Logging
# ----------------------------
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))

OUT_JSON = os.path.join(SCRIPT_DIR, "rlcom_alias_scan_out.json")
OUT_SKIPPED_JSON = os.path.join(SCRIPT_DIR, "rlcom_alias_scan_skipped.json")
LOG_FILE = os.path.join(SCRIPT_DIR, "rlcom_alias_scan.log")

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
    handlers=[logging.FileHandler(LOG_FILE), logging.StreamHandler()],
)

# ----------------------------
# DB Config (TEMP hard-coded)
# ----------------------------
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "u108848352_Ewka1",
    "password": "WhuiMoFs0X",
    "database": "u108848352_KDqxs",
}

# ----------------------------
# Competition URL mapping
# Key MUST match your scraper UI "BBC Title"
# ----------------------------
RLCOM_URLS = {
    "Betfred Super League": "https://www.rugby-league.com/competitions/pro-national/betfred-super-league/match-centre",

    # These are best guesses for standard rl.com structure; if any 404, just adjust.
    "Betfred Championship": "https://www.rugby-league.com/competitions/pro-national/betfred-championship",
    "Betfred League 1": "https://www.rugby-league.com/competitions/pro-national/betfred-league-one/match-centre",
    "Betfred Challenge Cup": "https://www.rugby-league.com/competitions/pro-national/betfred-challenge-cup/match-centre",
}

# ----------------------------
# Helpers
# ----------------------------
_TIME_RE = re.compile(r"\b([01]?\d|2[0-3]):([0-5]\d)\b")

def clean_text(s: str) -> str:
    s = (s or "").strip()
    s = re.sub(r"\s+", " ", s)
    return s

def db_connect():
    try:
        db = mysql.connector.connect(**DB_CONFIG)
        logging.info("✅ Database connection established.")
        return db
    except mysql.connector.Error as err:
        logging.error(f"❌ Database connection error: {err}")
        return None

def resolve_competitions_table(cursor):
    candidates = [
        "wpkl_pool_wpkl_scrape_competitions",
        "pool_wpkl_scrape_competitions",
        "wp_pool_wpkl_scrape_competitions",
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

def load_active_competitions(db, scrape_date: str):
    """
    Returns list of active 'bbc_title' values in date window.
    """
    cur = db.cursor(buffered=True)
    table = resolve_competitions_table(cur)
    if not table:
        cur.close()
        return []

    q = f"""
        SELECT bbc_title
        FROM `{table}`
        WHERE active = 1
          AND (start_date IS NULL OR %s >= start_date)
          AND (end_date   IS NULL OR %s <= end_date)
    """
    cur.execute(q, (scrape_date, scrape_date))
    rows = cur.fetchall()
    cur.close()
    titles = [r[0].strip() for r in rows if r and r[0]]
    logging.info(f"📘 Active competitions for {scrape_date}: {titles}")
    return titles

def find_fixture_blocks(soup: BeautifulSoup):
    """
    Anchor on venue-label spans and walk up until we find a container with team-name spans + time.
    """
    blocks = []
    seen = set()
    venue_spans = soup.select("span.venue-label")

    for vs in venue_spans:
        node = vs
        for _ in range(15):
            if not node or not getattr(node, "parent", None):
                break
            node = node.parent
            team_spans = node.select("span.team-name")
            text = node.get_text(" ", strip=True)
            if len(team_spans) >= 2 and _TIME_RE.search(text):
                key = id(node)
                if key not in seen:
                    seen.add(key)
                    blocks.append(node)
                break
    return blocks

def extract_teams_from_block(block):
    """
    Prefer desktop labels only (avoids Knights vs York RLFC style split).
    """
    desktop = block.select("span.team-name.d-none.d-lg-block")
    names = []
    for sp in desktop:
        txt = clean_text(sp.get_text(" ", strip=True))
        if txt and txt not in names:
            names.append(txt)

    if len(names) >= 2:
        return names[0], names[1]

    # fallback
    spans = block.select("span.team-name")
    alln = []
    for sp in spans:
        txt = clean_text(sp.get_text(" ", strip=True))
        if txt and txt not in alln:
            alln.append(txt)
    if len(alln) >= 2:
        return alln[0], alln[1]
    return None, None

def scrape_team_names(url: str):
    resp = requests.get(url, timeout=30)
    if resp.status_code != 200:
        logging.error(f"❌ Failed to fetch {url} ({resp.status_code})")
        return set(), resp.status_code

    soup = BeautifulSoup(resp.text, "html.parser")
    blocks = find_fixture_blocks(soup)
    logging.info(f"🔎 Fixture blocks found: {len(blocks)}")

    teams = set()
    for b in blocks:
        home, away = extract_teams_from_block(b)
        if home: teams.add(home)
        if away: teams.add(away)

    return teams, 200

def main():
    scrape_date = datetime.utcnow().strftime("%Y-%m-%d")

    db = db_connect()
    if not db:
        return

    active_titles = load_active_competitions(db, scrape_date)
    db.close()

    scanned = {}
    skipped = {}

    for title in active_titles:
        url = RLCOM_URLS.get(title)

        if not url:
            skipped[title] = {
                "reason": "No rugby-league.com match-centre URL configured in RLCOM_URLS",
            }
            logging.warning(f"⏭ Skipping '{title}' (no URL configured).")
            continue

        logging.info(f"🔍 Scanning: {title} -> {url}")
        teams, status = scrape_team_names(url)

        if status != 200:
            skipped[title] = {"reason": f"HTTP {status} for URL", "url": url}
            continue

        scanned[title] = sorted(list(teams))
        logging.info(f"✅ {title}: {len(teams)} team strings")

        if len(teams) == 0:
            logging.warning(f"⚠ {title}: 0 teams found. Page structure may differ or no fixtures loaded.")

    with open(OUT_JSON, "w", encoding="utf-8") as f:
        json.dump(scanned, f, ensure_ascii=False, indent=2)

    with open(OUT_SKIPPED_JSON, "w", encoding="utf-8") as f:
        json.dump(skipped, f, ensure_ascii=False, indent=2)

    print(OUT_JSON)
    print(OUT_SKIPPED_JSON)

if __name__ == "__main__":
    main()