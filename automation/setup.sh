#!/usr/bin/env bash
# One-time setup of the Otonus automation server (Ubuntu 24.04, DigitalOcean Toronto).
# Run as root from the droplet's web console:
#   git clone https://github.com/otonus-ai/otonus-ai.git /opt/otonus-src
#   bash /opt/otonus-src/automation/setup.sh automate.otonus.ai info@otonus.ai
set -euo pipefail

DOMAIN="${1:?usage: setup.sh DOMAIN EMAIL}"
EMAIL="${2:?usage: setup.sh DOMAIN EMAIL}"
SRC="$(cd "$(dirname "$0")" && pwd)"
DIR=/opt/otonus-automation

apt-get update
apt-get install -y docker.io docker-compose-v2 ufw git openssl

# Only SSH and the web ports are open.
ufw allow OpenSSH
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable

# 2 GB swap so n8n, Postgres and the ops agent fit on a 2 GB droplet.
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile && mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

mkdir -p "$DIR/workflows" "$DIR/backups"
cp "$SRC/docker-compose.yml" "$SRC/Caddyfile" "$SRC/backup.sh" "$DIR/"

# Secrets are made here once and never leave the server. Keep a copy of
# N8N_ENCRYPTION_KEY in Bitwarden: without it saved credentials can't be restored.
if [ ! -f "$DIR/.env" ]; then
  cat > "$DIR/.env" <<ENV
N8N_DOMAIN=$DOMAIN
ACME_EMAIL=$EMAIL
N8N_VERSION=2.44.6
POSTGRES_PASSWORD=$(openssl rand -hex 24)
N8N_ENCRYPTION_KEY=$(openssl rand -hex 32)
ENV
  chmod 600 "$DIR/.env"
fi

cd "$DIR"
docker compose up -d

# Nightly backup at 03:15 Toronto time, 14 days kept.
echo "15 3 * * * root $DIR/backup.sh >> $DIR/backups/backup.log 2>&1" > /etc/cron.d/otonus-n8n-backup

echo
echo "Done. Open https://$DOMAIN in a minute to create the n8n owner account."
echo "Save this line in Bitwarden now:"
grep N8N_ENCRYPTION_KEY "$DIR/.env"
