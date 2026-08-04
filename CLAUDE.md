# CLAUDE.md - Byblos HR (Byblos Printing SAL)

This is a client-specific fork of the Peoplova HR platform, customized for Byblos Printing SAL.

## Upstream

Forked from: https://github.com/fadishehadeh/peoplova

To pull core bug fixes from the original:
```bash
git remote add upstream https://github.com/fadishehadeh/peoplova.git
git fetch upstream
git merge upstream/main
```
Never push to upstream. Only pull from it.

## What This Is

A custom PHP MVC HR management system. Two front-ends from the same codebase:
- HR Portal (public-hr/ document root) - internal staff portal
- Careers Portal (public-careers/ document root) - public job board

## Client Info

- Client: Byblos Printing SAL
- Industry: Printing / Publishing
- Location: Lebanon
- Primary color: #5B8DB8 (steel blue)
- Dark accent: #4A7BA6
- Soft tint: #E8F0F7
- Charcoal text: #333333

## Brand Assets

Logo files live in public-hr/assets/images/:
- byblos-logo.png - main color logo (used in sidebar and login)
- byblos-logo-white.png - white version (used on dark backgrounds)

To update the logo, replace these files. The sidebar references them via the settings table
logo_url in the DB, or directly in app/Views/partials/sidebar.php.

## Running Locally

Requires XAMPP (Apache + MySQL). No build step.
Start Apache + MySQL, navigate to http://localhost/byblos-hr/public-hr/

Update DB app_url if routes return 404:
```sql
UPDATE settings SET setting_value = 'http://localhost/byblos-hr/public-hr' WHERE setting_key = 'app_url';
```

## Hosting (TBD)

- Domain: TBD
- Host: TBD (Namecheap shared hosting recommended)
- Server IP: TBD
- SSH: TBD
- DB name: TBD
- DB user: TBD

Update this file when hosting is provisioned.

## Deployment

Same git push -> git pull workflow as peoplova.com.

```bash
git push origin main
ssh -i ~/.ssh/byblos_deploy -p 21098 <user>@<server-ip> "cd /home/<user>/byblos-hr && git pull origin main"
```

## Environment (.env)

Copy .env.example to .env. Key values:
- DB_DATABASE - Byblos DB name
- DB_USERNAME / DB_PASSWORD - Byblos DB credentials
- ENCRYPTION_KEY - generate fresh 64-char hex (NEVER copy from peoplova)
- APP_URL - the live domain once provisioned
- MAIL_FROM_NAME - "Byblos HR"
- MAIL_FROM_ADDRESS - noreply@<client-domain>

CRITICAL: Generate a NEW ENCRYPTION_KEY for this client. Never reuse peoplova's key:
```bash
php -r "echo bin2hex(random_bytes(32));"
```

## Architecture

Identical to upstream Peoplova. See upstream repo CLAUDE.md for full architecture notes.

Key differences from upstream:
- Brand colors: blue (#5B8DB8) instead of red
- App name: "Byblos HR" instead of "Peoplova HR"
- Logo: Byblos Printing SAL logo

## Tests

```bash
composer test
```

## Cron Jobs

```bash
php scripts/process-email-queue.php    # Every minute
php scripts/process-escalations.php   # Periodic
```
