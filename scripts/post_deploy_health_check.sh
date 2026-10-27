#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

echo "[1/6] Checking Laravel bootstrap..."
php artisan --version >/dev/null

echo "[2/6] Checking database connection..."
php artisan tinker --execute="\Illuminate\Support\Facades\DB::connection()->getPdo(); echo 'db=ok'.PHP_EOL;"

echo "[3/6] Checking queue/session runtime drivers..."
php artisan tinker --execute="echo 'session_driver='.config('session.driver').PHP_EOL; echo 'queue_driver='.config('queue.default').PHP_EOL;"

echo "[4/6] Checking required tables..."
php artisan tinker --execute="echo 'has_jobs='.(\Illuminate\Support\Facades\Schema::hasTable('jobs') ? 'yes' : 'no').PHP_EOL; echo 'has_sessions='.(\Illuminate\Support\Facades\Schema::hasTable('sessions') ? 'yes' : 'no').PHP_EOL;"

echo "[5/6] Queue health snapshot..."
php artisan tinker --execute="echo 'pending_jobs='.\Illuminate\Support\Facades\DB::table('jobs')->count().PHP_EOL; echo 'failed_jobs='.\Illuminate\Support\Facades\DB::table('failed_jobs')->count().PHP_EOL;"

echo "[6/6] Validating scheduler registration..."
php artisan schedule:list >/dev/null

echo "health_check=ok"
