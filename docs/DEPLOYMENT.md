# Deployment — AR Dental Care

Documented deployment routes for Vibe96 (minimum two).

## Target 1 — Docker Compose (Linux VPS)

**Requirements:** Docker Engine + Docker Compose plugin, 1–2 vCPU, 2 GB RAM recommended.

```bash
git clone <your-repo> ardental && cd ardental
cp backend/.env.example backend/.env
cp frontend/.env.example frontend/.env
```

Edit `backend/.env` for production:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
FRONTEND_URL=https://app.yourdomain.com
DB_CONNECTION=mysql
DB_HOST=db
DB_PORT=3306
DB_DATABASE=ardental
DB_USERNAME=ardental
DB_PASSWORD=REPLACE_WITH_RANDOM_PASSWORD
MAIL_MAILER=smtp
# MAIL_HOST / MAIL_USERNAME / MAIL_PASSWORD …
```

Edit `frontend/.env`:

```env
VITE_API_URL=https://api.yourdomain.com/api
```

Start:

```bash
docker compose up -d --build
```

Put nginx/Caddy TLS in front of ports `8080` (SPA) and `8000` (API), or map hostnames to those services.

## Target 2 — Manual VPS (nginx + PHP-FPM + Node build)

**Requirements:** Ubuntu 22.04+, PHP 8.2–8.4 FPM, Composer, Node 20+, MySQL 8, nginx.

### Database

```bash
sudo mysql -e "CREATE DATABASE ardental CHARACTER SET utf8mb4; CREATE USER 'ardental'@'localhost' IDENTIFIED BY 'REPLACE_PASSWORD'; GRANT ALL ON ardental.* TO 'ardental'@'localhost'; FLUSH PRIVILEGES;"
```

### API

```bash
cd /var/www/ardental/backend
cp .env.example .env
# set APP_URL, FRONTEND_URL, DB_*, MAIL_*, APP_DEBUG=false
composer install --no-dev --optimize-autoloader
php artisan key:generate
php artisan migrate --seed --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
```

Point nginx root for the API host at `backend/public`.

### SPA

```bash
cd /var/www/ardental/frontend
cp .env.example .env
# VITE_API_URL=https://api.yourdomain.com/api
npm ci
npm run build
```

Serve `frontend/dist` as a static site (SPA fallback to `index.html`).

### Process notes

- Optional queue worker: `php artisan queue:work` if you switch mail/SMS to queues.
- Scheduler (optional reminders later): `* * * * * cd /var/www/ardental/backend && php artisan schedule:run`

## Hosting estimate

| | Minimum | Recommended |
|--|---------|-------------|
| CPU | 1 vCPU | 2 vCPU |
| RAM | 2 GB | 4 GB |
| Est. monthly | ~$12–20 | ~$20–40 |

Third-party SMTP / SMS / Oushodh fees are not included.
