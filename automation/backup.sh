#!/usr/bin/env bash
# Nightly n8n backup: Postgres dump plus every workflow and (encrypted) credential as JSON.
set -euo pipefail
DIR=/opt/otonus-automation
STAMP=$(date +%F)
OUT="$DIR/backups/$STAMP"
mkdir -p "$OUT"
cd "$DIR"
docker compose exec -T postgres pg_dump -U n8n n8n | gzip > "$OUT/n8n-db.sql.gz"
docker compose exec -T n8n n8n export:workflow --all --separate --output=/tmp/wf >/dev/null
docker compose cp n8n:/tmp/wf "$OUT/workflows"
docker compose exec -T n8n n8n export:credentials --all --output=/tmp/creds.json >/dev/null
docker compose cp n8n:/tmp/creds.json "$OUT/credentials-encrypted.json"
find "$DIR/backups" -mindepth 1 -maxdepth 1 -type d -mtime +14 -exec rm -rf {} +
echo "$STAMP backup ok"
