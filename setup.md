# VakilNeeti: Deploying to Hostinger with Git

This guide deploys the VakilNeeti Laravel app to Hostinger shared or cloud hosting (hPanel) and pulls updates from GitHub with Git.

> **Only `public/` may be web-visible.** The repo root is the Laravel app. The web server must serve the repo's **`public/`** folder and nothing else, otherwise `.env`, `storage/` and `vendor/` can be downloaded.

---

## 1. Requirements

| Item | Value |
|---|---|
| Hosting plan | Hostinger Premium, Business or Cloud (needs **SSH access**) |
| PHP | **8.3 or 8.4** (`composer.json` requires `^8.3`) |
| PHP extensions | `pdo_mysql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `fileinfo`, `gd`, `intl`, `zip`, `curl`. These are on by default in hPanel. |
| Database | MySQL / MariaDB (created in hPanel) |
| Node.js | **Not needed on the server.** Front-end assets are built on your PC or in GitHub Actions (step 7). |

---

## 2. Why we don't use hPanel's built-in "Git" tool

hPanel → **Advanced → Git** can only deploy *into* `public_html`. That would make the entire repo, including `.env`, `storage/` and `vendor/`, reachable from the browser. Instead we:

1. clone the repo **outside** `public_html` over SSH, and
2. point `public_html` at the repo's `public/` folder with a symlink.

---

## 3. One-time server setup

### 3.1 Turn on SSH and set PHP version

1. hPanel → **Advanced → SSH Access** → enable it. Note the **IP, port (usually `65002`) and username** (`u123456789`).
2. hPanel → **Advanced → PHP Configuration** → choose **PHP 8.3** (or 8.4).

Connect from your PC:

```bash
ssh -p 65002 u123456789@YOUR_SERVER_IP
php -v          # must print 8.3.x or 8.4.x
composer -V     # Composer 2 is preinstalled on Hostinger
```

> If `php -v` shows an older version, log out and back in after changing it in hPanel.

### 3.2 Create the database

hPanel → **Databases → MySQL Databases** → create:

- database: `u123456789_vakilneeti`
- user: `u123456789_vakil`
- a strong password

Keep these for `.env` (step 4).

### 3.3 Give the server read access to GitHub (private repo)

The repo `github.com/rahulyadavrkgit/VakilNeeti` is accessed over SSH, so the server needs a **deploy key**.

On the server:

```bash
ssh-keygen -t ed25519 -C "hostinger-vakilneeti" -f ~/.ssh/github_vakilneeti -N ""
cat ~/.ssh/github_vakilneeti.pub
```

Copy the printed key. On GitHub, go to the repo → **Settings → Deploy keys → Add deploy key**, paste it, and **leave "Allow write access" unticked**.

Tell SSH to use that key for GitHub:

```bash
cat >> ~/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile ~/.ssh/github_vakilneeti
    IdentitiesOnly yes
EOF
chmod 600 ~/.ssh/config

ssh -T git@github.com    # expect: "Hi rahulyadavrkgit/VakilNeeti! You've successfully authenticated..."
```

### 3.4 Clone the repository (outside `public_html`)

Replace `example.com` with your domain:

```bash
cd ~/domains/example.com
git clone git@github.com:rahulyadavrkgit/VakilNeeti.git vakilneeti-app
cd vakilneeti-app
```

Resulting layout:

```
~/domains/example.com/
├── vakilneeti-app/          ← git repo = Laravel app (not web-visible)
│   └── public/              ← the only web-visible folder
├── deploy-vakilneeti.sh     ← update script (step 6)
└── public_html  →  vakilneeti-app/public   (symlink, step 5)
```

---

## 4. Configure `.env`

There is no `.env.example` in the repo (it is gitignored), so create `.env` by hand:

```bash
cd ~/domains/example.com/vakilneeti-app
nano .env
```

```dotenv
APP_NAME=VakilNeeti
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://example.com

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_IN

LOG_CHANNEL=stack
LOG_STACK=daily
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=u123456789_vakilneeti
DB_USERNAME=u123456789_vakil
DB_PASSWORD=YOUR_DB_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true

CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local

# Email (Hostinger mailbox SMTP, or set MAIL_MAILER=resend + RESEND_API_KEY)
MAIL_MAILER=smtp
MAIL_HOST=smtp.hostinger.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=no-reply@example.com
MAIL_PASSWORD=YOUR_MAILBOX_PASSWORD
MAIL_FROM_ADDRESS=no-reply@example.com
MAIL_FROM_NAME="${APP_NAME}"
RESEND_API_KEY=

# Email sign-in codes via MSG91 (required for registration/login)
MSG91_AUTH_KEY=
MSG91_EMAIL_DOMAIN=
MSG91_EMAIL_FROM=
MSG91_EMAIL_FROM_NAME=VakilNiti
MSG91_EMAIL_OTP_TEMPLATE=

# MUST stay false in production (bypass only works when APP_ENV=local anyway)
OTP_BYPASS_ENABLED=false

# Optional: data.gov.in API (court/district directory imports)
DATA_GOV_IN_API_KEY=
```

Lock the file down:

```bash
chmod 600 .env
```

---

## 5. First deployment

```bash
cd ~/domains/example.com/vakilneeti-app

composer install --no-dev --optimize-autoloader --no-interaction
php artisan key:generate --force
php artisan migrate --force
php artisan storage:link
```

Upload the compiled front-end assets (`public/build`, see step 7), then cache the config:

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 5.1 Point the domain at `public/`

```bash
cd ~/domains/example.com
mv public_html public_html.bak          # keep Hostinger's default files, just in case
ln -s ~/domains/example.com/vakilneeti-app/public public_html
ls -la public_html                      # should show the symlink target
```

> **If the symlink gives a 403 or a blank page:** undo it with `rm public_html && mv public_html.bak public_html`. Then ask Hostinger live chat to *"set the document root of example.com to `domains/example.com/vakilneeti-app/public`"*. They do this routinely. Don't work around it by cloning the repo into `public_html`, because that exposes `.env`.

### 5.2 Folder permissions

```bash
cd ~/domains/example.com/vakilneeti-app
chmod -R 775 storage bootstrap/cache
```

### 5.3 Create the first admin

Create the one administrator account (it signs in with a password and an emailed code):

```bash
php artisan vakilneeti:make-admin you@example.com
```

### 5.4 SSL

hPanel → **Security → SSL** → install the free SSL certificate and turn on **Force HTTPS**.

---

## 6. Deploying updates (`git pull`)

Save this as `~/domains/example.com/deploy-vakilneeti.sh`. It goes next to the repo, not inside it, so `git reset` never touches it and it is never served:

```bash
#!/usr/bin/env bash
set -euo pipefail

APP_DIR="$HOME/domains/example.com/vakilneeti-app"
cd "$APP_DIR"

echo "→ Pulling latest code"
git fetch origin main
git reset --hard origin/main

php artisan down --retry=30 || true

echo "→ Installing PHP dependencies"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Migrating database"
php artisan migrate --force

echo "→ Rebuilding caches"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
echo "✓ Deployed $(git rev-parse --short HEAD)"
```

```bash
chmod +x ~/domains/example.com/deploy-vakilneeti.sh
~/domains/example.com/deploy-vakilneeti.sh
```

> `git reset --hard` throws away any edits made directly on the server. Make every change through Git. `.env`, `vendor/`, `storage/` and `public/build/` are gitignored, so they are kept.

---

## 7. Front-end assets (Vite / Tailwind)

`public/build/` is gitignored, and Hostinger shared hosting has no Node.js, so you build the assets somewhere else.

### Option A: build on your PC and upload (simple)

```bash
npm ci
npm run build
scp -P 65002 -r public/build u123456789@YOUR_SERVER_IP:~/domains/example.com/vakilneeti-app/public/
```

Do this every time you change CSS/JS in `resources/`.

### Option B: auto-deploy with GitHub Actions (recommended)

Every push to `main` builds the assets, uploads them, and runs `deploy-vakilneeti.sh`.

1. On your PC, create a key pair just for CI:
   ```bash
   ssh-keygen -t ed25519 -C "github-actions-deploy" -f ~/.ssh/hostinger_ci -N ""
   ```
2. Add the **public** key to Hostinger: hPanel → **Advanced → SSH Access → SSH Keys → Add SSH key** (paste `~/.ssh/hostinger_ci.pub`).
3. In GitHub, go to the repo → **Settings → Secrets and variables → Actions** and add:

   | Secret | Value |
   |---|---|
   | `HOSTINGER_HOST` | server IP |
   | `HOSTINGER_PORT` | `65002` |
   | `HOSTINGER_USER` | `u123456789` |
   | `HOSTINGER_SSH_KEY` | contents of `~/.ssh/hostinger_ci` (private key) |
   | `HOSTINGER_APP_DIR` | `/home/u123456789/domains/example.com/vakilneeti-app` |

4. Commit this as `.github/workflows/deploy.yml`:

```yaml
name: Deploy to Hostinger

on:
  push:
    branches: [main]
  workflow_dispatch:

concurrency: production

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: actions/setup-node@v4
        with:
          node-version: 22
          cache: npm

      - name: Build assets
        run: |
          npm ci
          npm run build

      - name: Set up SSH
        run: |
          mkdir -p ~/.ssh
          echo "${{ secrets.HOSTINGER_SSH_KEY }}" > ~/.ssh/id_ed25519
          chmod 600 ~/.ssh/id_ed25519
          ssh-keyscan -p "${{ secrets.HOSTINGER_PORT }}" "${{ secrets.HOSTINGER_HOST }}" >> ~/.ssh/known_hosts

      - name: Upload assets
        run: |
          rsync -az --delete -e "ssh -p ${{ secrets.HOSTINGER_PORT }}" \
            public/build/ \
            "${{ secrets.HOSTINGER_USER }}@${{ secrets.HOSTINGER_HOST }}:${{ secrets.HOSTINGER_APP_DIR }}/public/build/"

      - name: Run deploy script
        run: |
          ssh -p "${{ secrets.HOSTINGER_PORT }}" \
            "${{ secrets.HOSTINGER_USER }}@${{ secrets.HOSTINGER_HOST }}" \
            "bash ${{ secrets.HOSTINGER_APP_DIR }}/../deploy-vakilneeti.sh"
```

---

## 8. Cron (only if needed later)

The app currently has **no scheduled tasks and no queued jobs**, so you don't need a cron job yet. If you add some later, go to hPanel → **Advanced → Cron Jobs** and add one that runs every minute:

```
/usr/bin/php /home/u123456789/domains/example.com/vakilneeti-app/artisan schedule:run >> /dev/null 2>&1
```

For queued jobs, add another cron every minute:

```
/usr/bin/php /home/u123456789/domains/example.com/vakilneeti-app/artisan queue:work --stop-when-empty --max-time=50 >> /dev/null 2>&1
```

---

## 9. Optional: reference data

On a fresh database, the court, police and bar-council directories are empty. These seeders load the verified reference data. Run them **in this order**:

```bash
php artisan db:seed --class=CourtDataSourceSeeder --force
php artisan db:seed --class=SupremeCourtDataSourceSeeder --force
php artisan db:seed --class=InstitutionCategorySeeder --force
php artisan db:seed --class=CourtCatalogueSeeder --force
php artisan db:seed --class=HighCourtSeeder --force
php artisan db:seed --class=TribunalSeeder --force
php artisan db:seed --class=OfficialUpJudicialDistrictsSeeder --force
php artisan db:seed --class=VerifiedJudicialDirectorySeeder --force
php artisan db:seed --class=JurisdictionCrossLinkSeeder --force
php artisan db:seed --class=BarCouncilAndAssociationSeeder --force
php artisan db:seed --class=VerifiedStatePoliceHeadquartersSeeder --force
php artisan db:seed --class=DelhiPoliceVerifiedHierarchySeeder --force
php artisan db:seed --class=DelhiPoliceStationsSeeder --force
```

> Do **not** run `php artisan db:seed` with no class in production: it creates a `test@example.com` user.
> `LgdDistrictsSeeder`, `AllUttarPradeshDistrictsSeeder` and `UttarPradeshJurisdictionSeeder` fail right now: the first needs a missing LGD CSV, and the other two use a `headquarters_city` column that no migration creates.

---

## 10. Troubleshooting

| Symptom | Fix |
|---|---|
| `Your lock file does not contain a compatible set of packages` | Server PHP is older than the lock allows. Set PHP 8.3+ in hPanel. `composer.json` pins `config.platform.php` to 8.3 so the lock works on 8.3 and newer. Keep that pin when running `composer update`. |
| **500 error, blank page** | `tail -50 storage/logs/laravel-*.log`; check permissions (step 5.2) |
| `Vite manifest not found` | `public/build` is missing. Upload it (step 7). |
| Changed `.env` but nothing happens | `php artisan config:clear && php artisan config:cache` |
| `Permission denied (publickey)` on `git pull` | Re-check the deploy key (3.3) with `ssh -T git@github.com` |
| `Identifier name ... is too long` during migrate | Pull the latest code; this was fixed in the migrations |
| Sign-in code email not arriving | Run `php artisan app:test-login-email you@example.com`; check the `MSG91_*` values, that the sending domain is verified in MSG91, that the auth key allows the server's IP, and MSG91's email logs |
| Whole site shows Hostinger's default page | `public_html` isn't pointing at the repo's `public/` (step 5.1) |
| 419 "Page expired" on forms | `APP_URL` must match the real `https://` domain; clear the config cache |
