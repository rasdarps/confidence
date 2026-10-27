set -eu

echo "entrypoint.sh: [$(whoami)] [PHP $(php -r 'echo phpversion();')]"

# Keep startup idempotent. Background services pass a custom command and exit above.
php artisan storage:link --no-interaction || true

if [ "$#" -gt 0 ]; then
	echo "Starting custom command: $*"
	if [ "$#" -eq 1 ]; then
		exec sh -c "$1"
	fi
	exec "$@"
fi

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
	echo "Running database migrations"
	php artisan migrate --no-interaction --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
	echo "Running database seeders"
	php artisan db:seed --no-interaction --force
fi

echo "Rebuilding Laravel configuration cache"
php artisan optimize:clear --no-interaction
php artisan config:cache --no-interaction

echo "Starting supervisord"
exec supervisord -c "$LARAVEL_PATH/.deploy/config/supervisor.conf"