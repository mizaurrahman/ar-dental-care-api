#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

if [ ! -f .env ]; then
  cp .env.example .env
fi

# Render provides DATABASE_URL; Laravel expects DB_URL
if [ -n "${DATABASE_URL:-}" ] && [ -z "${DB_URL:-}" ]; then
  export DB_URL="$DATABASE_URL"
fi

USING_URL=0
if [ -n "${DB_URL:-}${DATABASE_URL:-}" ]; then
  USING_URL=1
  case "${DB_URL:-${DATABASE_URL:-}}" in
    postgres://*|postgresql://*)
      export DB_CONNECTION=pgsql
      export DB_SSLMODE="${DB_SSLMODE:-require}"
      ;;
  esac
fi

php -r '
$file = ".env";
$usingUrl = (getenv("DB_URL") || getenv("DATABASE_URL")) ? true : false;
$map = [
  "APP_ENV" => getenv("APP_ENV") ?: "production",
  "APP_DEBUG" => (getenv("APP_DEBUG") !== false && getenv("APP_DEBUG") !== "") ? (string) getenv("APP_DEBUG") : "false",
  "APP_URL" => getenv("APP_URL") ?: "http://localhost:8000",
  "FRONTEND_URL" => getenv("FRONTEND_URL") ?: "http://localhost:8080",
  "MAIL_MAILER" => getenv("MAIL_MAILER") ?: "log",
  "SMS_DRIVER" => getenv("SMS_DRIVER") ?: "log",
  "LOG_CHANNEL" => getenv("LOG_CHANNEL") ?: "stack",
  "SESSION_DRIVER" => getenv("SESSION_DRIVER") ?: "database",
  "CACHE_STORE" => getenv("CACHE_STORE") ?: "database",
  "QUEUE_CONNECTION" => getenv("QUEUE_CONNECTION") ?: "database",
  "FILESYSTEM_DISK" => getenv("FILESYSTEM_DISK") ?: "public",
];

if ($usingUrl) {
  $dbUrl = getenv("DB_URL") ?: getenv("DATABASE_URL");
  $map["DB_URL"] = $dbUrl;
  $map["DATABASE_URL"] = $dbUrl;
  $map["DB_CONNECTION"] = getenv("DB_CONNECTION") ?: "pgsql";
  $map["DB_SSLMODE"] = getenv("DB_SSLMODE") ?: "require";
  // Avoid leftover MySQL defaults from .env.example colliding with URL parsing
  $map["DB_HOST"] = "";
  $map["DB_PORT"] = "";
  $map["DB_DATABASE"] = "";
  $map["DB_USERNAME"] = "";
  $map["DB_PASSWORD"] = "";
} else {
  $conn = getenv("DB_CONNECTION") ?: "mysql";
  $map["DB_CONNECTION"] = $conn;
  $map["DB_HOST"] = getenv("DB_HOST") ?: "db";
  $map["DB_PORT"] = getenv("DB_PORT") ?: ($conn === "pgsql" ? "5432" : "3306");
  $map["DB_DATABASE"] = getenv("DB_DATABASE") ?: "ardental";
  $map["DB_USERNAME"] = getenv("DB_USERNAME") ?: "ardental";
  $map["DB_PASSWORD"] = getenv("DB_PASSWORD") ?: "ardental";
}

$env = file_exists($file) ? file_get_contents($file) : "";
foreach ($map as $key => $value) {
  $value = str_replace(["\n", "\r"], "", (string) $value);
  if (preg_match("/^{$key}=.*/m", $env)) {
    $env = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $env);
  } else {
    $env .= "\n{$key}={$value}\n";
  }
}

$appKey = getenv("APP_KEY");
if (is_string($appKey) && str_starts_with($appKey, "base64:")) {
  if (preg_match("/^APP_KEY=.*/m", $env)) {
    $env = preg_replace("/^APP_KEY=.*/m", "APP_KEY={$appKey}", $env);
  } else {
    $env .= "\nAPP_KEY={$appKey}\n";
  }
}
file_put_contents($file, $env);
'

if ! grep -q '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "Waiting for database…"
for i in $(seq 1 40); do
  if php artisan db:show >/dev/null 2>&1; then
    break
  fi
  sleep 2
done

if [ "${SEED_ON_BOOT:-true}" = "true" ]; then
  php artisan migrate --force --seed
else
  php artisan migrate --force
fi

php artisan storage:link || true
php artisan config:clear || true

PORT="${PORT:-8000}"
echo "Starting on 0.0.0.0:${PORT}"
exec php artisan serve --host=0.0.0.0 --port="$PORT"
