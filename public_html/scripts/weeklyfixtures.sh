#!/bin/bash
set -euo pipefail

PYTHON="/opt/alt/python311/bin/python3"
SCRIPT="/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/real-score-updater-rlcom-superleague.py"

DAYS=7  # total days to run INCLUDING today (7 = today + next 6)

START_DATE="$(date +%Y-%m-%d)"

for ((i=0; i<$DAYS; i++)); do
  DATE=$(date -d "$START_DATE +$i day" +%Y-%m-%d)
  echo "▶ Running scraper for $DATE"
  "$PYTHON" "$SCRIPT" "$DATE"
done