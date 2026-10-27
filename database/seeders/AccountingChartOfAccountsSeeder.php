<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingChartOfAccountsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            ['code' => '1000', 'name' => 'Cash On Hand', 'type' => 'asset', 'is_bank_account' => false],
            ['code' => '1010', 'name' => 'Bank Current Account', 'type' => 'asset', 'is_bank_account' => true],
            ['code' => '1100', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_bank_account' => false],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'is_bank_account' => false],
            ['code' => '1300', 'name' => 'Input VAT Receivable', 'type' => 'asset', 'is_bank_account' => false],
            ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability', 'is_bank_account' => false],
            ['code' => '2100', 'name' => 'VAT Payable', 'type' => 'liability', 'is_bank_account' => false],
            ['code' => '2200', 'name' => 'Tax Payable', 'type' => 'liability', 'is_bank_account' => false],
            ['code' => '2300', 'name' => 'Loan Payable - Bank', 'type' => 'liability', 'is_bank_account' => false],
            ['code' => '2310', 'name' => 'Loan Payable - Individual', 'type' => 'liability', 'is_bank_account' => false],
            ['code' => '3000', 'name' => 'Owner Equity', 'type' => 'equity', 'is_bank_account' => false],
            ['code' => '4000', 'name' => 'Sales Revenue', 'type' => 'income', 'is_bank_account' => false],
            ['code' => '4010', 'name' => 'Sales Discounts', 'type' => 'income', 'is_bank_account' => false],
            ['code' => '5000', 'name' => 'Cost of Goods Sold', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5100', 'name' => 'Purchases', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5200', 'name' => 'Operating Expenses', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5300', 'name' => 'Bank Charges', 'type' => 'expense', 'is_bank_account' => false],
            ['code' => '5400', 'name' => 'Interest Income', 'type' => 'income', 'is_bank_account' => false],
        ];

        foreach ($accounts as $account) {
            DB::table('accounting_accounts')->updateOrInsert(
                ['code' => $account['code']],
                [
                    'name' => $account['name'],
                    'type' => $account['type'],
                    'is_bank_account' => $account['is_bank_account'],
                    'is_active' => true,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        $mappingAccounts = DB::table('accounting_accounts')
            ->whereIn('code', ['1000', '1010', '1100', '1200', '2000', '4000', '5000', '5100', '5200', '5300', '5400'])
            ->pluck('id', 'code');

        $mappings = [
            ['event_key' => 'sale.full_cash', 'debit_code' => '1000', 'credit_code' => '4000'],
            ['event_key' => 'sale.full_credit', 'debit_code' => '1100', 'credit_code' => '4000'],
            ['event_key' => 'sale.partial_credit', 'debit_code' => '1100', 'credit_code' => '4000'],
            ['event_key' => 'sale.receipt', 'debit_code' => '1000', 'credit_code' => '1100'],
            ['event_key' => 'purchase.full_cash', 'debit_code' => '1200', 'credit_code' => '1000'],
            ['event_key' => 'purchase.full_credit', 'debit_code' => '1200', 'credit_code' => '2000'],
            ['event_key' => 'purchase.partial_credit', 'debit_code' => '1200', 'credit_code' => '2000'],
            ['event_key' => 'purchase.payment', 'debit_code' => '2000', 'credit_code' => '1000'],
            ['event_key' => 'expense.payment', 'debit_code' => '5200', 'credit_code' => '1000'],
            ['event_key' => 'stock.adjustment.increase', 'debit_code' => '1200', 'credit_code' => '3000'],
            ['event_key' => 'stock.adjustment.decrease', 'debit_code' => '5200', 'credit_code' => '1200'],
            ['event_key' => 'cash.reconciliation.overage', 'debit_code' => '1000', 'credit_code' => '5400'],
            ['event_key' => 'cash.reconciliation.shortage', 'debit_code' => '5200', 'credit_code' => '1000'],
            ['event_key' => 'bank.fee', 'debit_code' => '5300', 'credit_code' => '1010'],
            ['event_key' => 'bank.interest', 'debit_code' => '1010', 'credit_code' => '5400'],
        ];

        foreach ($mappings as $mapping) {
            DB::table('accounting_account_mappings')->updateOrInsert(
                ['event_key' => $mapping['event_key']],
                [
                    'debit_account_id' => $mappingAccounts[$mapping['debit_code']] ?? null,
                    'credit_account_id' => $mappingAccounts[$mapping['credit_code']] ?? null,
                    'is_active' => true,
                    'extra_config' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        DB::table('accounting_fiscal_periods')->updateOrInsert(
            [
                'start_date' => now()->startOfYear()->toDateString(),
                'end_date' => now()->endOfYear()->toDateString(),
            ],
            [
                'name' => 'FY ' . now()->year,
                'is_closed' => false,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
