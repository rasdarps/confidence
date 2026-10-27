<?php
$kernel = require __DIR__ . '/bootstrap/app.php';
$app = $kernel->make('Illuminate\Contracts\Console\Kernel')->getApplication();

$artisan = $app->make('Illuminate\Contracts\Console\Kernel');
$artisan->call('db:seed', ['--class' => 'Database\\Seeders\\PermissionTableSeeder']);

echo "Permissions seeded\n";
