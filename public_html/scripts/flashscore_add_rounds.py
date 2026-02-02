#!/opt/alt/python311/bin/python3

import requests
from bs4 import BeautifulSoup
from datetime import datetime
import pytz
import mysql.connector
import logging

# --- CONFIG ---
ROUND_TO_UPDATE = "Round 7"
COMPETITION_NAME = "Super League 2024"
FLASH_URL = "https://www.flashscore.co.uk/rugby-league/england/super-league/fixtures/"
DB_CONFIG = {
    "host": "127.0.0.1",
    "user": "u108848352_Ewka1",
    "password": "WhuiMoFs0X",
    "database": "u108848352_KDqxs"
}

# --- LOGGING ---
logging.basicConfig(level=logging.INFO, format='%(asctime)s [%(levelname)s] %(message)s')

# --- Parse UK datetime to UTC ---
def parse_uk_datetime(date_str, time_str):
    naive = datetime.strptime(f"{date_str} {time_str}", "%d.%m. %H:%M")
    uk = pytz.timezone("Europe/London")
    local_dt = uk.localize(naive)
    return local_dt.astimezone(pytz.utc)

# --- Scrape Flashscore Fixtures ---
def get_fixtures_for_round(round_name):
    headers = {"User-Agent": "Mozilla/5.0"}
    response = requests.get(FLASH_URL, headers=headers)
    soup = BeautifulSoup(response.text, "html.parser")

    fixtures = []
    current_round = None

    elements = soup.find_all("div", class_=["event__round", "event__match"])

    for element in elements:
        classes = element.get("class", [])
        if "event__round" in classes:
            current_round = element.get_text(strip=True)
            logging.info(f"🌀 Found round: {current_round}")
        elif "event__match" in classes and current_round == round_name:
            try:
                home = element.find("div", class_="event__participant--home").text.strip()
                away = element.find("div", class_="event__participant--away").text.strip()
                time_str = element.find("div", class_="event__time").text.strip()
                date_part, time_part = time_str.split(" ")
                utc_datetime = parse_uk_datetime(date_part, time_part)

                fixtures.append({
                    "home_team": home,
                    "away_team": away,
                    "utc_datetime": utc_datetime.strftime("%Y-%m-%d %H:%M:%S")
                })
                logging.info(f"✅ Match: {home} vs {away} at {utc_datetime} (UTC)")
            except Exception as e:
                logging.warning(f"❌ Failed to parse fixture under {current_round}: {e}")

    return fixtures

# --- Update Matches in DB ---
def update_round_in_db(fixtures, round_label):
    db = mysql.connector.connect(**DB_CONFIG)
    cursor = db.cursor()

    for match in fixtures:
        logging.info(f"🔍 Looking for {match['home_team']} vs {match['away_team']} at {match['utc_datetime']}")

        cursor.execute("""
            SELECT m.id FROM pool_wpkl_matches m
            JOIN pool_wpkl_teams h ON m.home_team_id = h.id
            JOIN pool_wpkl_teams a ON m.away_team_id = a.id
            JOIN pool_wpkl_matchtypes t ON m.matchtype_id = t.id
            WHERE h.name = %s AND a.name = %s AND t.name = %s
            AND ABS(TIMESTAMPDIFF(MINUTE, m.play_date, %s)) <= 60
        """, (match["home_team"], match["away_team"], COMPETITION_NAME, match["utc_datetime"]))

        result = cursor.fetchone()

        if result:
            match_id = result[0]
            cursor.execute("UPDATE pool_wpkl_matches SET round = %s WHERE id = %s", (round_label, match_id))
            db.commit()
            logging.info(f"✅ Updated match ID {match_id} with round '{round_label}'")
        else:
            logging.warning(f"❌ No match found for {match['home_team']} vs {match['away_team']} at {match['utc_datetime']}")

    cursor.close()
    db.close()

# --- MAIN ---
if __name__ == "__main__":
    logging.info(f"📡 Scraping Flashscore for {ROUND_TO_UPDATE}")
    fixtures = get_fixtures_for_round(ROUND_TO_UPDATE)

    if fixtures:
        for f in fixtures:
            print(f"Scraped fixture: {f['home_team']} vs {f['away_team']} at {f['utc_datetime']}")
        update_round_in_db(fixtures, ROUND_TO_UPDATE)
    else:
        logging.warning("⚠️ No fixtures found for the specified round.")