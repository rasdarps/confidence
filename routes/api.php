<?php

use Illuminate\Http\Request;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\ReadController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Resources\Api\MobileUserResource;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1')->name('api.auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/change-password', [AuthController::class, 'changePassword'])
        ->middleware('throttle:5,1')->name('api.auth.change-password');
    Route::get('/user', fn (Request $request) => new MobileUserResource($request->user()));
    Route::post('/auth/logout', [AuthController::class, 'logout']);
    Route::post('/sales', [TransactionController::class, 'sale'])->middleware('can:sale-create');
    Route::post('/sales/{sale}/payments', [TransactionController::class, 'payment'])->whereNumber('sale')->middleware('can:payment-sales-credit-pay');
    Route::post('/purchases', [TransactionController::class, 'purchase'])->middleware('can:purchase-create');
    Route::post('/expenses', [TransactionController::class, 'expense'])->middleware('can:expense-create');

    Route::get('/products', [ReadController::class, 'products'])->middleware('permission:product-list|sale-create|purchase-create');
    Route::get('/products/{product}', [ReadController::class, 'product'])->whereNumber('product')->middleware('permission:product-list|sale-create|purchase-create');
    Route::get('/sales', [ReadController::class, 'sales'])->middleware('can:sale-list');
    Route::get('/sales/{sale}', [ReadController::class, 'sale'])->whereNumber('sale')->middleware('can:sale-list');
    Route::get('/customer-payments/credit', [ReadController::class, 'credit'])->middleware('can:payment-sales-credit-list');
    Route::get('/customer-payments/paid', [ReadController::class, 'paid'])->middleware('can:payment-sales-cash-list');
    Route::get('/customers', [ReadController::class, 'customers'])->middleware('permission:customer-list|sale-create');
    Route::get('/customers/{customer}', [ReadController::class, 'customer'])->whereNumber('customer')->middleware('can:customer-list');
    Route::get('/suppliers', [ReadController::class, 'suppliers'])->middleware('permission:supplier-list|purchase-create');
    Route::get('/purchases', [ReadController::class, 'purchases'])->middleware('can:purchase-list');
    Route::get('/purchases/{purchase}', [ReadController::class, 'purchase'])->whereNumber('purchase')->middleware('can:purchase-list');
    Route::get('/expense-categories', [ReadController::class, 'expenseCategories'])->middleware('permission:expense-category-list|expense-create|expense-list');
    Route::get('/expenses', [ReadController::class, 'expenses'])->middleware('can:expense-list');
    Route::get('/expenses/{expense}', [ReadController::class, 'expense'])->whereNumber('expense')->middleware('can:expense-list');
    Route::get('/bank-accounts', [ReadController::class, 'bankAccounts'])->middleware('can:bank-account-list');
    Route::get('/bank-transactions', [ReadController::class, 'bankTransactions'])->middleware('can:bank-transaction-list');
    Route::get('/dashboard', [ReportController::class, 'dashboard'])->middleware('can:dashboard-view');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->middleware('permission:sale-list|report-sales-lines');
    Route::get('/reports/collections', [ReportController::class, 'collections'])->middleware('can:report-sales-cash-movement');
    Route::get('/reports/stock', [ReportController::class, 'stock'])->middleware('can:stock-report');
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock'])->middleware('can:stock-report');
    Route::get('/reports/expenses', [ReportController::class, 'expenses'])->middleware('can:report-expense');
});
