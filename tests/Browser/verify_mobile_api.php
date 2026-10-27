<?php

require __DIR__.'/../../vendor/autoload.php';

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$baseUrl = rtrim($argv[1] ?? 'http://127.0.0.1:8017', '/');
if (!in_array(parse_url($baseUrl, PHP_URL_HOST), ['127.0.0.1', 'localhost', '::1'], true)) {
    throw new RuntimeException('This verification script only supports a local API host.');
}
$user = User::query()->orderBy('id')->firstOrFail();
if ($user->must_change_password) throw new RuntimeException('Selected account requires password change.');
$user->tokens()->whereIn('name', ['temporary-mobile-api-read-smoke', 'temporary-mobile-api-report-smoke'])->delete();
$issued = $user->createToken('temporary-mobile-api-verification', ['*'], now()->addMinutes(5));
$failures = 0;
try {
    foreach (['products', 'customers', 'suppliers', 'sales', 'customer-payments/credit', 'customer-payments/paid',
        'purchases', 'expense-categories', 'expenses', 'bank-accounts', 'bank-transactions', 'dashboard',
        'reports/sales', 'reports/collections', 'reports/stock', 'reports/low-stock', 'reports/expenses'] as $path) {
        $context = stream_context_create(['http' => ['method' => 'GET', 'ignore_errors' => true, 'timeout' => 15,
            'header' => "Accept: application/json\r\nAuthorization: Bearer ".$issued->plainTextToken."\r\n"]]);
        $body = file_get_contents($baseUrl.'/api/'.$path, false, $context);
        $json = json_decode($body, true);
        $status = $http_response_header[0] ?? 'No HTTP status';
        $success = str_contains($status, '200') && is_array($json) && array_key_exists('data', $json);
        if (!$success) $failures++;
        echo json_encode(['path' => '/api/'.$path, 'status' => $status, 'success' => $success]).PHP_EOL;
    }
} finally {
    $issued->accessToken->delete();
}
echo 'Verification complete; temporary token revoked. Failures: '.$failures.PHP_EOL;
exit($failures === 0 ? 0 : 1);