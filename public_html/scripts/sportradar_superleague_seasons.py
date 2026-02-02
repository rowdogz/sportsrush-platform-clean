#!/opt/alt/python311/bin/python3

import requests
import logging

# Setup logging
logging.basicConfig(level=logging.INFO, format="%(message)s")

# Constants
API_KEY = "m7ukR5qJrPPf9P9ahy6bZ819tINRYAP1Ks8kGtHx"
SEASON_ID = "sr:season:125541"
URL = f"https://api.sportradar.com/rugby-league/trial/v3/en/seasons/{SEASON_ID}/schedule.json?api_key={API_KEY}"

logging.info(f"🔍 Requesting: {URL}")

try:
    response = requests.get(URL)
    response.raise_for_status()
    data = response.json()

    fixtures = data.get("sport_events", [])
    if not fixtures:
        logging.warning("⚠️ No fixtures found.")
    else:
        logging.info(f"📋 Fixtures for Super League 2025:")
        for match in fixtures:
            match_id = match.get("id")
            date = match.get("scheduled", "")[:10]
            home = away = "TBD"

            competitors = match.get("competitors", [])
            for team in competitors:
                if team["qualifier"] == "home":
                    home = team["name"]
                elif team["qualifier"] == "away":
                    away = team["name"]

            logging.info(f"🗓 {date} | {home} vs {away} | ID: {match_id}")

except requests.exceptions.HTTPError as err:
    logging.error(f"❌ HTTP error: {err} - {response.text}")
except Exception as e:
    logging.error(f"❌ Unexpected error: {e}")