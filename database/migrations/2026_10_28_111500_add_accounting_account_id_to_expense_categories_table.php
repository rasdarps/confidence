<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add accounting_account_id column to expense_categories
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('accounting_account_id')->nullable()->after('name');
            $table->foreign('accounting_account_id')->references('id')->on('accounting_accounts')->nullOnDelete();
            $table->index('accounting_account_id');
        });

        // 2. Ensure standard operational expense accounts exist in accounting_accounts
        $subExpenseAccounts = [
            ['code' => '5210', 'name' => 'Utilities Expense', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5220', 'name' => 'Rent & Rates', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5230', 'name' => 'Salaries & Wages', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5240', 'name' => 'Motor & Travel Expense', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5250', 'name' => 'Advertising & Marketing', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5260', 'name' => 'Office & General Supplies', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5270', 'name' => 'Inventory Losses', 'type' => 'expense', 'is_bank_account' => false],
        ];

        foreach ($subExpenseAccounts as $acc) {
            DB::table('accounting_accounts')->updateOrInsert(
                ['code' => $acc['code']],
                [
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'is_bank_account' => $acc['is_bank_account'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 3. Map default categories to appropriate GL accounts
        $accountIds = DB::table('accounting_accounts')
            ->whereIn('code', ['5100', '5200', '5210', '5220', '5230', '5240', '5250', '5260', '5270'])
            ->pluck('id', 'code');

        $mappings = [
            'Light Bill' => '5210',
            'Water Bill' => '5210',
            'Internet' => '5210',
            'Phone Bills' => '5210',

            'Shop Rent' => '5220',
            'Warehouse Rent' => '5220',
            'Office Rent' => '5220',

            'Salaries' => '5230',

            'Transportation' => '5240',
            'Fuel' => '5240',
            'Vehicle Maintenance' => '5240',
            'Loading and offloading' => '5240',

            'Advertising' => '5250',

            'Stationary' => '5260',

            'Stock Shortage' => '5270',
            'Expired Goods' => '5270',

            'Import Duty and Clearing' => '5100',
            'Freight/Shipping' => '5100',
        ];

        foreach ($mappings as $catName => $code) {
            if (isset($accountIds[$code])) {
                DB::table('expense_categories')
                    ->where('name', $catName)
                    ->update(['accounting_account_id' => $accountIds[$code]]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('expense_categories', function (Blueprint $table) {
            $table->dropForeign(['accounting_account_id']);
            $table->dropColumn('accounting_account_id');
        });
    }
};

