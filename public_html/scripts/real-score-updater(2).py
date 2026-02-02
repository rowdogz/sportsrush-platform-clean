#!/opt/alt/python311/bin/python3

# coding: utf-8

import sys
import requests
import mysql.connector
from bs4 import BeautifulSoup
from datetime import datetime as mydatetime
import os
import pytz
import logging

from pytz.exceptions import AmbiguousTimeError, NonExistentTimeError

# Configure paths
SCRIPT_DIR = os.path.dirname(os.path.abspath(__file__))
LOG_FILE = os.path.join(SCRIPT_DIR, "fixture_scraper.log")

# Configure logging
log_handler_file = logging.FileHandler(LOG_FILE)
log_handler_console = logging.StreamHandler()

logging.basicConfig(
    level=logging.DEBUG,  # Logs all messages of level DEBUG and above
    format='%(asctime)s %(levelname)s %(message)s',
    handlers=[
        log_handler_file,
        log_handler_console
    ]
)

# Database connection details
DB_CONFIG = {
    "host": "127.0.0.1",  # Use IPv4 for compatibility
    "user": "u108848352_Ewka1",
    "password": "WhuiMoFs0X",
    "database": "u108848352_KDqxs"
}

# ---- DB helpers ----

def db_connect():
    try:
        mydb = mysql.connector.connect(**DB_CONFIG)
        logging.info("✅ Database connection established.")
        return mydb
    except mysql.connector.Error as err:
        logging.error(f"❌ Database connection error: {err}")
        return None

def fetch_id(cursor, table, name_column, id_column, name):
    query = f"SELECT {id_column} FROM {table} WHERE {name_column} = %s"
    cursor.execute(query, (name,))
    result = cursor.fetchone()
    if result:
        return result[0]
    else:
        logging.error(f"❌ No ID found in {table} for {name_column}: {name}")
        return None

def resolve_competitions_table(cursor):
    """
    Try to find the competitions config table.
    """
    candidates = [
        "wpkl_pool_wpkl_scrape_competitions",  # your confirmed table
        "pool_wpkl_scrape_competitions",
        "wp_pool_wpkl_scrape_competitions",
        "wp0_pool_wpkl_scrape_competitions",
        "wp1_pool_wpkl_scrape_competitions",
        "wordpress_pool_wpkl_scrape_competitions",
    ]
    for table in candidates:
        try:
            cursor.execute(f"SELECT 1 FROM `{table}` LIMIT 1")
            _ = cursor.fetchone()  # ✅ consume the single row to avoid unread results
            logging.info(f"📦 Using competitions table: {table}")
            return table
        except mysql.connector.Error:
            # Clear any partial/unread results defensively
            try:
                while cursor.fetchone():
                    pass
            except Exception:
                pass
            continue

    # Fallback: discover via INFORMATION_SCHEMA
    try:
        cursor.execute("""
            SELECT TABLE_NAME
            FROM INFORMATION_SCHEMA.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME LIKE %s
            LIMIT 1
        """, ("%pool_wpkl_scrape_competitions",))
        row = cursor.fetchone()  # ✅ consume
        if row and row[0]:
            logging.info(f"📦 Using competitions table (discovered): {row[0]}")
            return row[0]
    except mysql.connector.Error as err:
        logging.error(f"❌ Error probing INFORMATION_SCHEMA: {err}")

    logging.error("❌ Could not find competitions table created by the WP plugin.")
    return None

def load_valid_competitions_for_date(db, scrape_date):
    """
    Returns a dict mapping BBC page competition titles -> DB competition names
    Only competitions that are active and within optional date ranges for the given scrape_date.
    scrape_date: 'YYYY-MM-DD' string
    """
    cursor = db.cursor(buffered=True)  # ✅ buffered to prevent unread results
    table = resolve_competitions_table(cursor)
    if not table:
        cursor.close()
        return {}

    query = f"""
        SELECT bbc_title, db_name
        FROM `{table}`
        WHERE active = 1
          AND (start_date IS NULL OR %s >= start_date)
          AND (end_date   IS NULL OR %s <= end_date)
    """
    try:
        cursor.execute(query, (scrape_date, scrape_date))
        rows = cursor.fetchall()
        mapping = {bbc.strip(): dbname.strip() for (bbc, dbname) in rows if bbc and dbname}
        if mapping:
            logging.info(f"📘 Valid competitions loaded for {scrape_date}: {mapping}")
        else:
            logging.warning("⚠ No active competitions found for this date. Nothing will be scraped.")
        return mapping
    except mysql.connector.Error as err:
        logging.error(f"❌ Error loading competitions from {table}: {err}")
        return {}
    finally:
        cursor.close()

# ---- Logic helpers ----

def has_date_time_changed(existing_play_date, new_play_date):
    """
    Compare the existing play date (from DB) with the new one (scraped) and return True if they are different.
    """
    existing_play_date_dt = mydatetime.strptime(str(existing_play_date), "%Y-%m-%d %H:%M:%S")
    new_play_date_dt = mydatetime.strptime(str(new_play_date), "%Y-%m-%d %H:%M:%S")

    logging.debug("🕒 Checking match time change:")
    logging.debug(f"   - Existing DB time: {existing_play_date} (UTC)")
    logging.debug(f"   - New scraped time: {new_play_date} (UTC)")

    if existing_play_date_dt != new_play_date_dt:
        logging.info(f"🛑 Time difference detected! Updating from {existing_play_date} to {new_play_date}")
        return True
    else:
        logging.info("✅ No change detected.")
        return False

# ---- Scraper ----

def scrape_fixtures(scrape_date):
    url = f"https://www.bbc.co.uk/sport/rugby-league/scores-fixtures/{scrape_date}"
    logging.info(f"🔍 Scraping URL: {url}")
    print(f"Scraping URL: {url}")  # Real-time feedback for testing

    response = requests.get(url)
    if response.status_code != 200:
        logging.error(f"❌ Failed to retrieve URL: {url} with status code: {response.status_code}")
        return []

    html_content = response.text
    soup = BeautifulSoup(html_content, "html.parser")

    fixtures = []
    matches = soup.find_all('div', class_='ssrcss-1bjtunb-GridContainer e1efi6g55')
    logging.debug(f"🔍 Number of matches found: {len(matches)}")

    # Load competitions dynamically from WP-managed table
    db = db_connect()
    if not db:
        logging.error("❌ Cannot load valid competitions (DB down).")
        return []

    valid_competitions = load_valid_competitions_for_date(db, scrape_date)
    db.close()

    if not valid_competitions:
        logging.warning("⚠ No valid competitions configured for this date; skipping scrape.")
        return []

    for match in matches:
        try:
            h2 = match.find_previous('h2', class_='ssrcss-12l0oeb-GroupHeader ejnn8gi5')
            if not h2:
                logging.warning("⚠ Could not find competition title header; skipping block.")
                continue

            competition_title = h2.text.strip()
            logging.info(f"📢 Competition title found: {competition_title}")

            if competition_title not in valid_competitions:
                logging.info(f"⏭ Skipping competition (not active or out of date window): {competition_title}")
                continue

            competition_name = valid_competitions[competition_title]

            home_team_tag = match.find('div', class_='ssrcss-bon2fo-WithInlineFallback-TeamHome e1efi6g53')
            away_team_tag = match.find('div', class_='ssrcss-nvj22c-WithInlineFallback-TeamAway e1efi6g52')
            time_tag = match.find('time', class_='ssrcss-bkk8ek-StyledTime eli9aj90')

            if not home_team_tag or not away_team_tag or not time_tag:
                logging.warning("⚠ Skipping match due to missing team or time data.")
                continue

            home_team_span = home_team_tag.find('span', class_='ssrcss-1p14tic-DesktopValue emlpoi30')
            away_team_span = away_team_tag.find('span', class_='ssrcss-1p14tic-DesktopValue emlpoi30')

            if not home_team_span or not away_team_span:
                logging.warning("⚠ Skipping match due to missing team name spans.")
                continue

            home_team = home_team_span.text.strip()
            away_team = away_team_span.text.strip()
            match_time = time_tag.text.strip()

            logging.debug(f"⚽ Fixture: {home_team} vs {away_team} at {match_time}")

            fixtures.append({
                "competition": competition_name,
                "home_team": home_team,
                "away_team": away_team,
                "match_time": match_time
            })
        except AttributeError as e:
            logging.error(f"❌ Error parsing match data: {e}")
            continue

    logging.info(f"✅ Scraped fixtures: {fixtures}")
    return fixtures

# ---- Insert/Update ----

def insert_fixtures(fixtures, scrape_date):
    db = db_connect()
    if not db:
        return

    cursor = db.cursor()

    for fixture in fixtures:
        try:
            # Fetch IDs for teams
            home_team_id = fetch_id(cursor, "pool_wpkl_teams", "name", "id", fixture["home_team"])
            away_team_id = fetch_id(cursor, "pool_wpkl_teams", "name", "id", fixture["away_team"])

            # Fetch ID for competition
            matchtype_id = fetch_id(cursor, "pool_wpkl_matchtypes", "name", "id", fixture["competition"])

            if not home_team_id or not away_team_id or not matchtype_id:
                logging.error(f"❌ Missing IDs for fixture: {fixture}")
                continue

            # Convert play_date to UTC
            play_date = f"{scrape_date} {fixture['match_time']}"
            local = pytz.timezone("Europe/London")
            naive = mydatetime.strptime(play_date, "%Y-%m-%d %H:%M")

            try:
                is_bst = bool(local.dst(naive, is_dst=None))  # Determine if BST applies
                local_dt = local.localize(naive, is_dst=is_bst)
            except AmbiguousTimeError:
                local_dt = local.localize(naive, is_dst=False)  # Assume GMT if ambiguous
            except NonExistentTimeError:
                local_dt = local.localize(naive, is_dst=True)  # Assume BST if missing

            # Convert to UTC
            play_date_utc = local_dt.astimezone(pytz.utc).strftime("%Y-%m-%d %H:%M:%S")

            # Check if the fixture already exists within 3 days of the scraped date
            check_query = """
            SELECT id, play_date FROM pool_wpkl_matches 
            WHERE home_team_id = %s AND away_team_id = %s 
              AND matchtype_id = %s 
              AND ABS(DATEDIFF(play_date, %s)) < 3;
            """
            cursor.execute(check_query, (home_team_id, away_team_id, matchtype_id, play_date_utc))
            existing_fixture = cursor.fetchone()

            if existing_fixture:
                existing_match_id, existing_play_date = existing_fixture

                logging.debug(f"🔎 Existing fixture found: {existing_match_id} (DB Time: {existing_play_date}, New: {play_date_utc})")

                if has_date_time_changed(existing_play_date, play_date_utc):
                    # Update existing match time only if needed
                    update_query = """
                    UPDATE pool_wpkl_matches
                    SET play_date = %s
                    WHERE id = %s;
                    """
                    cursor.execute(update_query, (play_date_utc, existing_match_id))
                    db.commit()
                    logging.info(f"🔄 Updated play date for fixture ID {existing_match_id}: {fixture}")
                else:
                    logging.info(f"✅ No changes required for fixture ID {existing_match_id}.")
            else:
                # No existing fixture found within 3 days, insert new match
                default_stadium_id = 2
                insert_query = """
                INSERT INTO pool_wpkl_matches (home_team_id, away_team_id, play_date, matchtype_id, stadium_id)
                VALUES (%s, %s, %s, %s, %s);
                """
                cursor.execute(insert_query, (home_team_id, away_team_id, play_date_utc, matchtype_id, default_stadium_id))
                db.commit()
                logging.info(f"🆕 Inserted new fixture: {fixture}")

        except mysql.connector.Error as err:
            logging.error(f"❌ Database error when inserting/updating fixture: {err}")
            continue

    cursor.close()
    db.close()

# ---- Main ----

if __name__ == "__main__":
    scrape_date = sys.argv[1] if len(sys.argv) > 1 else input("Enter the date (YYYY-MM-DD): ")
    fixtures = scrape_fixtures(scrape_date)
    if fixtures:
        insert_fixtures(fixtures, scrape_date)
    else:
        logging.info("⚠ No fixtures found for the given date.")