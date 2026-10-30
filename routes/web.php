<?php

use App\Http\Controllers\Backend\AdminController;
use App\Http\Controllers\Backend\AccountingReportController;
use App\Http\Controllers\Backend\AccountingSetupController;
use App\Http\Controllers\Backend\BankingController;
use App\Http\Controllers\Backend\CategoryController;
use App\Http\Controllers\Backend\CashReconciliationController;
use App\Http\Controllers\Backend\CompanyController;
use App\Http\Controllers\Backend\ConversationController;
use App\Http\Controllers\Backend\CapitalLoanController;
use App\Http\Controllers\Backend\CustomerController;
use App\Http\Controllers\Backend\DailyTransactionEmailController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\ExpenseCategoryController;
use App\Http\Controllers\Backend\ExpenseVoucherController;
use App\Http\Controllers\Backend\OutletController;
use App\Http\Controllers\Backend\SaleController;
use App\Http\Controllers\Backend\ProductController;
use App\Http\Controllers\Backend\ProductPriceController;
use App\Http\Controllers\Backend\PurchaseController;
use App\Http\Controllers\Backend\ReportController;
use App\Http\Controllers\Backend\RoleController;
use App\Http\Controllers\Backend\StockController;
use App\Http\Controllers\Backend\StockAdjustmentController;
use App\Http\Controllers\Backend\StockTransferController;
use App\Http\Controllers\Backend\SupplierController;
use App\Http\Controllers\Backend\SettingsController;
use App\Http\Controllers\Backend\UnitController;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;



//Route::get('/dashboard', function () {
   // return view('admin.index');
//})->middleware(['auth'])->name('dashboard');

//require __DIR__.'/auth.php';

Route::get('/admin/home', function () {
    return redirect()->route('dashboard');
});

// Route::get('/', function () {
//     return view('welcome');
// });

Route::get('/', function () {
    return redirect('/admin/login');
});


Route::prefix('admin')->group(function () {

    Auth::routes(['register' => false]);


        Route::middleware(['auth', 'force.password.change'])->group(function(){
          
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/insight', [DashboardController::class, 'insight'])->name('insight');

            Route::get('/chat', [ConversationController::class, 'index'])->name('chat.index');
            Route::post('/chat', [ConversationController::class, 'ask'])->middleware('throttle:30,1')->name('chat.ask');


             // Admin All Route 
                Route::controller(AdminController::class)->group(function () {
                Route::post('/admin/logout', 'destroy')->name('admin.logout');
                Route::get('/admin/logout', function() {
                    return redirect('/admin/login');
                });
                Route::get('/admin/profile', 'Profile')->name('admin.profile');
                Route::get('/edit/profile', 'EditProfile')->name('edit.profile');
                Route::post('/store/profile', 'StoreProfile')->name('store.profile');

                Route::get('/change/password', 'ChangePassword')->name('change.password');
                Route::post('/update/password', 'UpdatePassword')->name('update.password');
                
            });


            Route::prefix('suppliers')->name('suppliers.')->group(function () {
                Route::get('/', [SupplierController::class, 'index'])->name('index');
                // Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
                Route::get('/create', [SupplierController::class, 'create'])->name('create');
                Route::post('/store', [SupplierController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [SupplierController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [SupplierController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [SupplierController::class, 'destroy'])->name('destroy');
                

            });


            // Customer All Route 
            Route::prefix('customers')->name('customers.')->group(function () {
                Route::get('/', [CustomerController::class, 'index'])->name('index');
                // Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
                Route::get('/create', [CustomerController::class, 'create'])->name('create');
                Route::post('/store', [CustomerController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [CustomerController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [CustomerController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [CustomerController::class, 'destroy'])->name('destroy');
                

            });
            Route::controller(CustomerController::class)->group(function () {
                Route::get('/credit/customer', 'CreditCustomer')->name('credit.customer');

                Route::get('/customer/edit/sale/{sale_id}', 'CustomerEditSale')->name('customer.edit.sale');
                Route::post('/customer/update/sale/{sale_id}', 'CustomerUpdateSale')->name('customer.update.sale');


                Route::get('/paid/customer', 'PaidCustomer')->name('paid.customer');

                
            });

            Route::controller(SupplierController::class)->group(function () {
                Route::get('/credit/supplier', 'CreditSupplier')->name('credit.supplier');
                Route::get('/supplier/edit/purchase/{purchase_id}', 'SupplierEditPurchase')->name('supplier.edit.purchase');
                Route::post('/supplier/update/purchase/{purchase_id}', 'SupplierUpdatePurchase')->name('supplier.update.purchase');
                Route::get('/paid/supplier', 'PaidSupplier')->name('paid.supplier');
            });


            Route::prefix('units')->name('units.')->group(function () {
                Route::get('/', [UnitController::class, 'index'])->name('index');
                // Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
                Route::get('/create', [UnitController::class, 'create'])->name('create');
                Route::post('/store', [UnitController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [UnitController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [UnitController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [UnitController::class, 'destroy'])->name('destroy');
                

            });

            Route::prefix('categories')->name('categories.')->group(function () {
                Route::get('/', [CategoryController::class, 'index'])->name('index');
                // Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
                Route::get('/create', [CategoryController::class, 'create'])->name('create');
                Route::post('/store', [CategoryController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [CategoryController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [CategoryController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [CategoryController::class, 'destroy'])->name('destroy');

            });


            Route::prefix('products')->name('products.')->group(function () {
                Route::get('/', [ProductController::class, 'index'])->name('index');
                Route::get('/show/{id}', [ProductController::class, 'show'])->name('show');
                Route::get('/create', [ProductController::class, 'create'])->name('create');
                Route::post('/store', [ProductController::class, 'store'])->name('store');
                Route::post('/import', [ProductController::class, 'import'])->name('import');
                Route::get('/edit/{encryptedId}', [ProductController::class, 'edit'])->name('edit');
                Route::patch('/update/{encryptedId}', [ProductController::class, 'update'])->name('update');
                Route::delete('/destroy/{encryptedId}', [ProductController::class, 'destroy'])->name('destroy');
                
                // Barcode lookup supports both scanner-driven GET and form POST callers
                Route::match(['get', 'post'], '/find-by-barcode', [ProductController::class, 'findByBarcode'])->name('findByBarcode');
                Route::get('/print-barcodes', [ProductController::class, 'printBarcodes'])->name('print-barcodes');
                Route::post('/update-stock', [ProductController::class, 'updateStock'])->name('update-stock');
                Route::get('/export', [ProductController::class, 'export'])->name('export');

            });

            Route::prefix('expense-vouchers')->name('expense-vouchers.')->group(function () {
                Route::get('/', [ExpenseVoucherController::class, 'index'])->name('index');
                Route::get('/create', [ExpenseVoucherController::class, 'create'])->name('create');
                Route::get('/show/{id}', [ExpenseVoucherController::class, 'show'])->name('show');
                Route::get('/print/{id}', [ExpenseVoucherController::class, 'print'])->name('print');
                Route::post('/store', [ExpenseVoucherController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [ExpenseVoucherController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [ExpenseVoucherController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [ExpenseVoucherController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('expense-categories')->name('expense-categories.')->group(function () {
                Route::get('/', [ExpenseCategoryController::class, 'index'])->name('index');
                Route::get('/create', [ExpenseCategoryController::class, 'create'])->name('create');
                Route::post('/store', [ExpenseCategoryController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [ExpenseCategoryController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [ExpenseCategoryController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [ExpenseCategoryController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('stock-adjustments')->name('stock-adjustments.')->group(function () {
                Route::get('/', [StockAdjustmentController::class, 'index'])->name('index');
                Route::get('/create', [StockAdjustmentController::class, 'create'])->name('create');
                Route::post('/store', [StockAdjustmentController::class, 'store'])->name('store');
                Route::get('/show/{id}', [StockAdjustmentController::class, 'show'])->name('show');
                Route::get('/print/{id}', [StockAdjustmentController::class, 'print'])->name('print');
                Route::get('/edit/{id}', [StockAdjustmentController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [StockAdjustmentController::class, 'update'])->name('update');
                Route::delete('/destroy/{id}', [StockAdjustmentController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('stock-transfers')->name('stock-transfers.')->group(function () {
                Route::get('/', [StockTransferController::class, 'index'])->name('index');
                Route::get('/create', [StockTransferController::class, 'create'])->name('create');
                Route::post('/store', [StockTransferController::class, 'store'])->name('store');
                Route::get('/show/{id}', [StockTransferController::class, 'show'])->name('show');
                Route::post('/receive/{id}', [StockTransferController::class, 'receive'])->name('receive');
                Route::post('/cancel/{id}', [StockTransferController::class, 'cancel'])->name('cancel');
            });

            Route::prefix('cash-reconciliations')->name('cash-reconciliations.')->group(function () {
                Route::get('/', [CashReconciliationController::class, 'index'])->name('index');
                Route::get('/create', [CashReconciliationController::class, 'create'])->name('create');
                Route::get('/expected', [CashReconciliationController::class, 'expected'])->name('expected');
                Route::post('/store', [CashReconciliationController::class, 'store'])->name('store');
                Route::get('/show/{id}', [CashReconciliationController::class, 'show'])->name('show');
                Route::get('/edit/{id}', [CashReconciliationController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [CashReconciliationController::class, 'update'])->name('update');
                Route::post('/approve/{id}', [CashReconciliationController::class, 'approve'])->name('approve');
                Route::post('/reverse-approval/{id}', [CashReconciliationController::class, 'reverseApproval'])->name('reverse-approval');
            });

            //Product Price routes
            Route::prefix('product-prices')->name('product-prices.')->group(function () {
                Route::get('/', [ProductPriceController::class, 'index'])->name('index');
                Route::get('/edit/{encryptedId}', [ProductPriceController::class, 'edit'])->name('edit');
                Route::patch('/update/{encryptedId}', [ProductPriceController::class, 'update'])->name('update');
                Route::delete('/destroy/{encryptedId}', [ProductPriceController::class, 'destroy'])->name('destroy');

                //price change history
                Route::get('/product_price_history', [ProductPriceController::class, 'ProductPriceHistory'])->name('product_price_history');

            });


            
            // Purchase All Route 
            Route::prefix('purchases')->name('purchases.')->group(function () {
                Route::get('/', [PurchaseController::class, 'index'])->name('index');
                // Route::get('/show/{id}', [CategoryController::class, 'show'])->name('show');
                Route::get('/create', [PurchaseController::class, 'create'])->name('create');
                Route::post('/store', [PurchaseController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [PurchaseController::class, 'edit'])->name('edit');
                Route::patch('/update/{id}', [PurchaseController::class, 'update'])->name('update');
                Route::post('/cancel/{id}', [PurchaseController::class, 'cancel'])->name('cancel');
            
            });

            Route::controller(PurchaseController::class)->group(function () {

                Route::get('/print/purchase/list', 'PrintPurchaseList')->name('print.purchase.list');
                Route::get('/print/purchase/{id}', 'PrintPurchase')->name('print.purchase');

                
            });
        Route::prefix('companies')->name('companies.')->group(function () {
            Route::get('/', [CompanyController::class, 'index'])->name('index');
            Route::get('/create', [CompanyController::class, 'create'])->name('create');
            Route::post('/store', [CompanyController::class, 'store'])->name('store');
            Route::get('/edit/{id}', [CompanyController::class, 'edit'])->name('edit');
            Route::patch('/update/{id}', [CompanyController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [CompanyController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('outlets')->name('outlets.')->group(function () {
            Route::get('/', [OutletController::class, 'index'])->name('index');
            Route::get('/create', [OutletController::class, 'create'])->name('create');
            Route::post('/store', [OutletController::class, 'store'])->name('store');
            Route::get('/edit/{id}', [OutletController::class, 'edit'])->name('edit');
            Route::patch('/update/{id}', [OutletController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [OutletController::class, 'destroy'])->name('destroy');

        });

        Route::prefix('settings')->name('settings.')->group(function () {
            Route::get('/', [SettingsController::class, 'index'])->name('index');
            Route::patch('/update', [SettingsController::class, 'update'])->name('update');
        });

        Route::prefix('daily-transaction-email')->name('daily-transaction-email.')->group(function () {
            Route::get('/', [DailyTransactionEmailController::class, 'index'])->name('index');
            Route::get('/logs', [DailyTransactionEmailController::class, 'logs'])->name('logs');
            Route::post('/send', [DailyTransactionEmailController::class, 'sendManual'])->name('send');
            Route::get('/status', [DailyTransactionEmailController::class, 'getStatus'])->name('status');
        });

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/show/{id}', [UserController::class, 'show'])->name('show');
            Route::get('/create', [UserController::class, 'create'])->name('create');
            Route::post('/store', [UserController::class, 'store'])->name('store');
            Route::get('/edit/{id}', [UserController::class, 'edit'])->name('edit');
            Route::patch('/update/{id}', [UserController::class, 'update'])->name('update');
            Route::delete('/destroy/{id}', [UserController::class, 'destroy'])->name('destroy');

        });

         Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/show/{id}', [RoleController::class, 'show'])->where('id', '.*')->name('show');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/store', [RoleController::class, 'store'])->name('store');
                Route::get('/edit/{id}', [RoleController::class, 'edit'])->where('id', '.*')->name('edit');
                Route::patch('/update/{id}', [RoleController::class, 'update'])->where('id', '.*')->name('update');
                Route::delete('/destroy/{id}', [RoleController::class, 'destroy'])->where('id', '.*')->name('destroy');

        });


            // Sale All Route 
            Route::prefix('sales')->name('sales.')->group(function () {
                Route::get('/', [SaleController::class, 'index'])->name('index');
                Route::get('/create', [SaleController::class, 'create'])->name('create');
                Route::get('/daily-total-sales', [SaleController::class, 'dailyTotalSales'])->name('daily-total-sales');
                Route::post('/store', [SaleController::class, 'store'])->name('store');
                Route::post('/cancel/{id}', [SaleController::class, 'cancel'])->name('cancel');
                Route::post('/{id}/receipt/reprint', [SaleController::class, 'reprintSale'])->name('receipt.reprint');
                Route::get('/delivery/verify', [SaleController::class, 'deliveryVerificationPage'])->name('delivery.verify.page');
                Route::post('/delivery/verify', [SaleController::class, 'verifyDelivery'])->name('delivery.verify');
            });


            // Sale All Route 
            Route::controller(SaleController::class)->group(function () {
                Route::get('/print/sale/list', 'printSaleList')->name('print.sale.list');
                Route::get('/print/sale/{id}', 'printSale')->name('print.sale');
            });

            Route::get('/reports/sales-receipt-audit', [SaleController::class, 'receiptAudit'])->name('reports.sales-receipt-audit');
            Route::get('/reports/sales-receipt-audit/export', [SaleController::class, 'exportReceiptAudit'])->name('reports.sales-receipt-audit.export');

            //Reports Routes
            Route::prefix('reports')->name('reports.')->group(function () {
                Route::get('/profit', [ReportController::class, 'profitReport'])->name('profit');
                Route::get('/profit/summary', [ReportController::class, 'profitSummary'])->name('profit.summary');
                Route::get('/sales-lines', [ReportController::class, 'salesLinesReport'])->name('sales-lines');
                Route::get('/purchase-lines', [ReportController::class, 'purchaseLinesReport'])->name('purchase-lines');
                Route::get('/purchase-edit-trail', [ReportController::class, 'purchaseEditTrailReport'])->name('purchase-edit-trail');
                Route::get('/sales-lines/summary', [ReportController::class, 'salesLinesSummary'])->name('sales-lines.summary');
                Route::get('/purchase-lines/summary', [ReportController::class, 'purchaseLinesSummary'])->name('purchase-lines.summary');
                Route::get('/sales-cash-movement', [ReportController::class, 'salesCashMovementReport'])->name('sales-cash-movement');
                Route::get('/purchase-cash-movement', [ReportController::class, 'purchaseCashMovementReport'])->name('purchase-cash-movement');
                Route::get('/sales-cash-movement/summary', [ReportController::class, 'salesCashMovementSummary'])->name('sales-cash-movement.summary');
                Route::get('/purchase-cash-movement/summary', [ReportController::class, 'purchaseCashMovementSummary'])->name('purchase-cash-movement.summary');
                Route::get('/sales-credit-movement', [ReportController::class, 'salesCreditMovementReport'])->name('sales-credit-movement');
                Route::get('/purchase-credit-movement', [ReportController::class, 'purchaseCreditMovementReport'])->name('purchase-credit-movement');
                Route::get('/sales-credit-movement/summary', [ReportController::class, 'salesCreditMovementSummary'])->name('sales-credit-movement.summary');
                Route::get('/purchase-credit-movement/summary', [ReportController::class, 'purchaseCreditMovementSummary'])->name('purchase-credit-movement.summary');
                Route::get('/summary-ledger', [ReportController::class, 'summaryLedger'])->name('summary-ledger');
                Route::get('/expense', [ReportController::class, 'expenseReport'])->name('expense');
                Route::get('/stock-adjustment', [ReportController::class, 'stockAdjustmentReport'])->name('stock-adjustment');

            });

            Route::prefix('accounting')->name('accounting.')->group(function () {
                Route::get('/trial-balance', [AccountingReportController::class, 'trialBalance'])->name('trial-balance');
                Route::get('/general-ledger', [AccountingReportController::class, 'generalLedger'])->name('general-ledger');
                Route::get('/income-statement', [AccountingReportController::class, 'incomeStatement'])->name('income-statement');
                Route::get('/balance-sheet', [AccountingReportController::class, 'balanceSheet'])->name('balance-sheet');
                Route::get('/balance-sheet/pdf', [AccountingReportController::class, 'balanceSheetPdf'])->name('balance-sheet.pdf');
                Route::get('/cash-flow', [AccountingReportController::class, 'cashFlow'])->name('cash-flow');
                Route::get('/ar-aging', [AccountingReportController::class, 'arAging'])->name('ar-aging');
                Route::get('/ap-aging', [AccountingReportController::class, 'apAging'])->name('ap-aging');
            });

            Route::prefix('accounting/setup')->name('accounting.setup.')->group(function () {
                Route::get('/coa', [AccountingSetupController::class, 'coaIndex'])->name('coa.index');
                Route::post('/coa', [AccountingSetupController::class, 'coaStore'])->name('coa.store');
                Route::patch('/coa/{id}', [AccountingSetupController::class, 'coaUpdate'])->name('coa.update');

                Route::get('/fiscal-periods', [AccountingSetupController::class, 'fiscalPeriodsIndex'])->name('fiscal-periods.index');
                Route::post('/fiscal-periods', [AccountingSetupController::class, 'fiscalPeriodStore'])->name('fiscal-periods.store');
                Route::patch('/fiscal-periods/{id}', [AccountingSetupController::class, 'fiscalPeriodUpdate'])->name('fiscal-periods.update');
                Route::post('/fiscal-periods/{id}/close', [AccountingSetupController::class, 'fiscalPeriodClose'])->name('fiscal-periods.close');
                Route::post('/fiscal-periods/{id}/reopen', [AccountingSetupController::class, 'fiscalPeriodReopen'])->name('fiscal-periods.reopen');

                Route::get('/bank-accounts', [BankingController::class, 'bankAccountsIndex'])->name('bank-accounts.index');
                Route::post('/bank-accounts', [BankingController::class, 'bankAccountStore'])->name('bank-accounts.store');
                Route::patch('/bank-accounts/{id}', [BankingController::class, 'bankAccountUpdate'])->name('bank-accounts.update');
                Route::delete('/bank-accounts/{id}', [BankingController::class, 'bankAccountDestroy'])->name('bank-accounts.destroy');

                Route::get('/bank-transactions', [BankingController::class, 'bankTransactionsIndex'])->name('bank-transactions.index');
                Route::post('/bank-transactions', [BankingController::class, 'bankTransactionStore'])->name('bank-transactions.store');
                Route::patch('/bank-transactions/{id}', [BankingController::class, 'bankTransactionUpdate'])->name('bank-transactions.update');
                Route::delete('/bank-transactions/{id}', [BankingController::class, 'bankTransactionDestroy'])->name('bank-transactions.destroy');

                Route::get('/capital-loans', [CapitalLoanController::class, 'index'])->name('capital-loans.index');
                Route::post('/capital-loans', [CapitalLoanController::class, 'store'])->name('capital-loans.store');
                Route::patch('/capital-loans/{id}', [CapitalLoanController::class, 'update'])->name('capital-loans.update');
                Route::delete('/capital-loans/{id}', [CapitalLoanController::class, 'destroy'])->name('capital-loans.destroy');
            });





            // Stock All Route 
            Route::controller(StockController::class)->group(function () {
                
                Route::get('/stock/report', 'StockReport')->name('stock.report');
                Route::get('/stock/ledger/report', 'StockLedgerReport')->name('stock.ledger_report');
                Route::get('/stock/product-trail/report', 'ProductWiseStockTrail')->name('stock.product_trail_report');
                Route::get('/stock_value', 'StockValue')->name('stock.stock_value');


                
            
            });



    }); // End Group Middleware
  
});
