#!/opt/alt/python311/bin/python3

import requests

API_KEY = "195653fc973a7a0263272c57093d01f1"
BASE_URL = "https://v1.rugby.api-sports.io"

headers = {
    "x-apisports-key": API_KEY
}

def list_all_leagues():
    url = f"{BASE_URL}/leagues"
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"❌ Error fetching leagues: {response.status_code} - {response.text}")
        return

    leagues = response.json().get("response", [])
    print("📋 All Available Rugby Leagues:\n")

    for league in leagues:
        league_name = league.get("name", "")
        country = league.get("country", {}).get("name", "")
        league_id = league.get("id", "")

        # Check seasons structure safely
        seasons = league.get("seasons", [])
        last_season = "N/A"
        if seasons:
            if isinstance(seasons[-1], dict):
                last_season = seasons[-1].get("start", "Unknown")

        print(f"🔹 ID: {league_id} | Name: {league_name} | Country: {country} | Latest Season Start: {last_season}")

if __name__ == "__main__":
    list_all_leagues()