#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Ensure Docker DB settings when env vars are provided by compose
php -r '
$file = ".env";
$map = [
  "DB_CONNECTION" => getenv("DB_CONNECTION") ?: "mysql",
  "DB_HOST" => getenv("DB_HOST") ?: "db",
  "DB_PORT" => getenv("DB_PORT") ?: "3306",
  "DB_DATABASE" => getenv("DB_DATABASE") ?: "ardental",
  "DB_USERNAME" => getenv("DB_USERNAME") ?: "ardental",
  "DB_PASSWORD" => getenv("DB_PASSWORD") ?: "ardental",
  "APP_URL" => getenv("APP_URL") ?: "http://localhost:8000",
  "FRONTEND_URL" => getenv("FRONTEND_URL") ?: "http://localhost:8080",
  "MAIL_MAILER" => getenv("MAIL_MAILER") ?: "log",
  "SMS_DRIVER" => getenv("SMS_DRIVER") ?: "log",
];
$env = file_exists($file) ? file_get_contents($file) : "";
foreach ($map as $key => $value) {
  if (preg_match("/^{$key}=.*/m", $env)) {
    $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
  } else {
    $env .= "\n{$key}={$value}\n";
  }
}
file_put_contents($file, $env);
'

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

php artisan migrate --force --seed
php artisan storage:link || true

exec php artisan serve --host=0.0.0.0 --port=8000
