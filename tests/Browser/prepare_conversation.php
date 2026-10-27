<?php

require __DIR__.'/../../vendor/autoload.php';

use App\Models\Company;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\ConversationReportService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$database = tempnam(sys_get_temp_dir(), 'darppos_chat_test_');
config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => $database]);
DB::purge('sqlite');
Artisan::call('migrate', ['--force' => true]);

$user = User::create([
    'name' => 'Chat Test Operator', 'username' => 'chat-browser-test',
    'email' => 'chat-browser@example.test', 'phone' => '0240000099',
    'password' => Hash::make('ChatBrowserTestOnly-2026!'),
    'user_type' => 'Staff', 'must_change_password' => false,
]);
foreach (array_unique(array_merge(['chat-use', 'dashboard-view', 'customer-list', 'supplier-list', 'user-filter-view', 'payment-sales-cash-list', 'payment-sales-credit-pay'], array_values(ConversationReportService::PERMISSIONS))) as $name) {
    $user->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
}
$user->assignRole(Role::firstOrCreate(['name' => 'Cashier', 'guard_name' => 'web']));
Company::create(['name' => 'Chat Test Company', 'address' => 'Test address', 'phone' => '0240000098', 'email' => 'company@example.test']);
$customer = Customer::create(['name' => 'Example Customer', 'mobile_no' => '0240000097']);
$sale = Sale::create(['sale_no' => 'CHAT-001', 'date' => now()->toDateString(), 'created_by' => $user->id]);
DB::table('payments')->insert(['sale_id' => $sale->id, 'customer_id' => $customer->id, 'paid_status' => 'partial_payment', 'total_amount' => 250, 'paid_amount' => 100, 'due_amount' => 150]);
DB::table('payment_details')->insert(['sale_id' => $sale->id, 'date' => now()->toDateString(), 'current_paid_amount' => 100]);
$olderCustomer = Customer::create(['name' => 'Older Customer', 'mobile_no' => '0240000096']);
foreach ([['CHAT-002', '2025-01-01', 100, 'full_credit', $olderCustomer->id], ['CHAT-003', now()->toDateString(), 50, 'full_payment', $customer->id]] as [$number, $date, $amount, $status, $customerId]) {
    $record = Sale::create(['sale_no' => $number, 'date' => $date, 'created_by' => $user->id]);
    DB::table('payments')->insert(['sale_id' => $record->id, 'customer_id' => $customerId, 'paid_status' => $status,
        'total_amount' => $amount, 'paid_amount' => $status === 'full_payment' ? $amount : 0, 'due_amount' => $status === 'full_credit' ? $amount : 0]);
}
$other = User::create(['name' => 'Other Cashier', 'username' => 'other-cashier-test', 'email' => 'other-cashier@example.test',
    'phone' => '0240000095', 'password' => Hash::make('ChatBrowserTestOnly-2026!'), 'user_type' => 'Staff', 'must_change_password' => false]);
$other->assignRole('Cashier');
$foreignCustomer = Customer::create(['name' => 'Other Cashier Customer', 'mobile_no' => '0240000094']);
$foreignSale = Sale::create(['sale_no' => 'OTHER-001', 'date' => now()->toDateString(), 'created_by' => $other->id]);
DB::table('payments')->insert(['sale_id' => $foreignSale->id, 'customer_id' => $foreignCustomer->id, 'paid_status' => 'full_payment', 'total_amount' => 900, 'paid_amount' => 900, 'due_amount' => 0]);
$foreignCredit = Sale::create(['sale_no' => 'OTHER-002', 'date' => now()->toDateString(), 'created_by' => $other->id]);
DB::table('payments')->insert(['sale_id' => $foreignCredit->id, 'customer_id' => $foreignCustomer->id, 'paid_status' => 'full_credit', 'total_amount' => 200, 'paid_amount' => 0, 'due_amount' => 200]);
$category = DB::table('expense_categories')->insertGetId(['name' => 'Transport']);
$voucher = DB::table('expense_vouchers')->insertGetId(['voucher_date' => now()->toDateString(), 'total_amount' => 40]);
DB::table('expense_voucher_items')->insert(['expense_voucher_id' => $voucher, 'expense_category_id' => $category, 'amount' => 40]);
$account = DB::table('bank_accounts')->insertGetId(['name' => 'Test Bank', 'currency' => 'GHS', 'current_balance' => 100]);
DB::table('bank_transactions')->insert(['bank_account_id' => $account, 'transaction_date' => now()->toDateString(), 'type' => 'deposit', 'amount' => 100]);
echo $database.PHP_EOL;