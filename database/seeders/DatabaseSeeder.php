<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            OutletSeeder::class,
            PermissionTableSeeder::class,
            CreateAdminUserSeeder::class,
            AccountingChartOfAccountsSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            CompanySeeder::class,
            CustomerSeeder::class,
            SupplierSeeder::class,
            ExpenseCategorySeeder::class,
            ProductSeeder::class,
        ]);
    }
}
