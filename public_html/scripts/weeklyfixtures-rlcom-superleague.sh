#!/bin/bash
SCRIPT="/home/u108848352/domains/sportsrush.co.uk/public_html/scripts/real-score-updater-rlcom-superleague.py"

$SCRIPT $(date -I)
$SCRIPT $(date -I -d "+1 days")
$SCRIPT $(date -I -d "+2 days")
$SCRIPT $(date -I -d "+3 days")
$SCRIPT $(date -I -d "+4 days")
$SCRIPT $(date -I -d "+5 days")
$SCRIPT $(date -I -d "+6 days")
$SCRIPT $(date -I -d "+7 days")