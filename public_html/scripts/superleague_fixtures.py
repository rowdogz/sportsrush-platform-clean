#!/opt/alt/python311/bin/python3

import requests

API_KEY = "195653fc973a7a0263272c57093d01f1"
BASE_URL = "https://v1.rugby.api-sports.io"

headers = {
    "x-apisports-key": API_KEY
}

# Step 1: Confirm League ID for Super League
def get_super_league_id():
    url = f"{BASE_URL}/leagues"
    response = requests.get(url, headers=headers)
    if response.status_code != 200:
        print(f"❌ Error fetching leagues: {response.status_code} - {response.text}")
        return None

    for league in response.json().get("response", []):
        if "super league" in league["name"].lower() and league["country"]["name"].lower() == "england":
            print(f"✅ Found Super League: ID {league['id']}, Season {league['seasons'][-1]['year']}")
            return league["id"], league["seasons"][-1]["year"]

    print("❌ Super League not found in API.")
    return None, None

# Step 2: Get fixtures for Super League
def get_fixtures(league_id, season):
    url = f"{BASE_URL}/fixtures"
    params = {
        "league": league_id,
        "season": season
    }

    response = requests.get(url, headers=headers, params=params)
    if response.status_code != 200:
        print(f"❌ Error fetching fixtures: {response.status_code} - {response.text}")
        return

    fixtures = response.json().get("response", [])
    print(f"\n📋 Fixtures for Super League {season}\n")
    for f in fixtures:
        home = f["teams"]["home"]["name"]
        away = f["teams"]["away"]["name"]
        date = f["date"]
        round_info = f["league"].get("round", "N/A")
        print(f"📅 {round_info}: {home} vs {away} on {date}")

if __name__ == "__main__":
    league_id, season = get_super_league_id()
    if league_id:
        get_fixtures(league_id, season)