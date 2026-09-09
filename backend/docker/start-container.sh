#!/bin/sh
set -eu

: "${PORT:=10000}"

envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/conf.d/default.conf

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

if [ "${APP_ENV:-production}" = "production" ] && [ "${DB_CONNECTION:-}" != "pgsql" ]; then
    echo "ERROR: Production requires DB_CONNECTION=pgsql"
    exit 1
fi

php artisan package:discover --ansi >/dev/null

php artisan migrate --force

if [ "${RUN_ADMIN_SEED:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
