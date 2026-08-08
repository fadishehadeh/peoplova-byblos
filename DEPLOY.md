# Deployment Guide — peoplova.com

## Server

| Item | Value |
|---|---|
| Host | `68.65.120.179` |
| SSH port | `21098` |
| SSH user | `clanumsr` |
| cPanel user | `clanumsr` |
| Server | Namecheap shared hosting / LiteSpeed |
| App path | `~/peoplova/` |
| Document root | `~/peoplova/public-hr/` |
| GitHub repo | `https://github.com/fadishehadeh/peoplova.git` |

## Database

| Item | Value |
|---|---|
| Database | `clanumsr_peoplova` |
| Username | `clanumsr_peoplovausr` |
| Password | `rX6)%WMrrD4Vx]5,` |
| Host | `localhost` |

## SSH Key Setup

The deploy key is an RSA 4096 key with **no passphrase**, authorized in cPanel as `deploy2`.

If the key file is missing (scratchpad is session-specific), generate a new one:
```bash
ssh-keygen -t rsa -b 4096 -N "" -f deploy_key
```
Then in cPanel → SSH Access → Import Key → paste the public key content → Authorize it.

## Deploy Workflow

Every code change follows this exact sequence:

### 1. Commit and push locally
```bash
git add <changed files>
git commit -m "describe the change"
git push origin main
```

### 2. Pull on the live server
```bash
KEY="path/to/deploy_key"
ssh -i "$KEY" -o StrictHostKeyChecking=no -o BatchMode=yes -p 21098 clanumsr@68.65.120.179 \
  "cd ~/peoplova && git pull origin main"
```

If the server has local uncommitted changes that block the pull:
```bash
ssh -i "$KEY" -o StrictHostKeyChecking=no -o BatchMode=yes -p 21098 clanumsr@68.65.120.179 \
  "cd ~/peoplova && git stash && git pull origin main"
```

### 3. Verify
```bash
curl -sk -L -H "Host: peoplova.com" https://68.65.120.179/ -o /dev/null -w "%{http_code}"
# Should return 200
```

> **Important:** Always test via `https://68.65.120.179` with a `Host` header, not via `--resolve` to `127.0.0.1`. LiteSpeed vhosts are bound to the public IP only.

## Local Development

Local URL: `http://localhost/peoplova/public-hr/`

The `settings` table's `app_url` row overrides `.env` at runtime. If routing breaks locally, fix it:
```sql
UPDATE settings SET setting_value = 'http://localhost/peoplova/public-hr' WHERE setting_key = 'app_url';
```

## Live .env (key values)

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://peoplova.com
ENCRYPTION_KEY=52b97e3f5fa300c92b574a321609ca07aaadcfc6801e27d07ddc2c9c777eed93
DB_DATABASE=clanumsr_peoplova
DB_USERNAME=clanumsr_peoplovausr
DB_PASSWORD=rX6)%WMrrD4Vx]5,
```

> `ENCRYPTION_KEY` must never change after data has been stored — it encrypts PII fields at rest.
