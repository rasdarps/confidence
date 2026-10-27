<?php

namespace Database\Seeders;

use App\Models\ExpenseCategory;
use Illuminate\Database\Seeder;

class ExpenseCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Import Duty and Clearing',
            'Freight/Shipping',
            'Light Bill',
            'Water Bill',
            'Salaries',
            'Loading and offloading',
            'Transportation',
            'Shop Rent',
            'Warehouse Rent',
            'Stationary',
            'Office Rent',
            'Internet',
            'Phone Bills',
            'Fuel',
            'Vehicle Maintenance',
            'Advertising',
            'Stock Shortage',
            'Expired Goods',
        ];

        foreach ($categories as $name) {
            ExpenseCategory::firstOrCreate(['name' => $name]);
        }
    }
}
