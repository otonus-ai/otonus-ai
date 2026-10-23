# Otonus automation server

n8n + Postgres behind Caddy (automatic HTTPS) on one DigitalOcean droplet. Client workflows run here; the Otonus ops agent can share the same droplet and its fixed IP (the Namecheap API whitelist).

- **Droplet**: Toronto (TOR1), Ubuntu 24.04, Basic 2 GB / 1 vCPU (about $12 USD a month, $14.40 with weekly backups). `setup.sh` adds 2 GB swap.
- **Address**: `automate.otonus.ai`. Add an A record for `automate` pointing at the droplet IP in the otonus.ai DNS (cPanel > Zone Editor) before running setup, so Caddy can get the certificate.

## Install

In the droplet's web console, as root:

```bash
apt-get update && apt-get install -y git
git clone https://github.com/otonus-ai/otonus-ai.git /opt/otonus-src
bash /opt/otonus-src/automation/setup.sh automate.otonus.ai info@otonus.ai
```

It installs Docker, opens only ports 22, 80 and 443, writes `/opt/otonus-automation/.env` with fresh random secrets, starts the stack, and adds a nightly backup (03:15, 14 days kept in `/opt/otonus-automation/backups`). It prints `N8N_ENCRYPTION_KEY` at the end: save it in Bitwarden, because saved credentials can't be restored without it.

Then open https://automate.otonus.ai and create the owner account.

## Day to day

| Task | Command (in `/opt/otonus-automation`) |
|---|---|
| Status | `docker compose ps` |
| Logs | `docker compose logs -f n8n` |
| Upgrade n8n | change `N8N_VERSION` in `.env`, then `docker compose pull && docker compose up -d` |
| Backup now | `./backup.sh` |

Webhooks are public at `https://automate.otonus.ai/webhook/<path>`; everything else needs the n8n login.
