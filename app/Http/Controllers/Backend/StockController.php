<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\Stock\ProductWiseStockReportDataTable;
use App\DataTables\Admin\Report\Stock\StockReportDataTable;
use App\DataTables\Admin\Report\Stock\StocksDataTable;
use App\DataTables\Admin\Report\Stock\StockValueReportDataTable;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
 
class StockController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stock-report', ['only' => ['StockReport']]);
        $this->middleware('permission:stock-ledger-report', ['only' => ['StockLedgerReport']]);
        $this->middleware('permission:stock-product-trail-report', ['only' => ['ProductWiseStockTrail']]);
        $this->middleware('permission:stock-value-report', ['only' => ['StockValue']]);
    }

    // public function StockReport(){

    //     $allData = Product::orderBy('name','asc')->orderBy('category_id','asc')->get();
    //     return view('backend.stock.stock_report',compact('allData'));

    // } // End Method

    public function StockReport(StocksDataTable $dataTable){

       $units = Unit::orderBy('name')->get();
       $categories = Category::orderBy('name')->get(); 
         return $dataTable->render('backend.stock.stock_report', compact('units', 'categories'));

    }

    public function StockLedgerReport(StockReportDataTable $dataTable)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $categories = Category::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.stock.stock_ledger_report', compact('products', 'categories', 'users'));
    }

    public function ProductWiseStockTrail(ProductWiseStockReportDataTable $dataTable)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);
        $users = User::orderBy('name')->get(['id', 'name']);

        return $dataTable->render('backend.stock.product_wise_stock_trail', compact('products', 'users'));
    }


    public function StockValue(StockValueReportDataTable $dataTable){
        $products = Product::orderBy('name')->get();

        return $dataTable->render('backend.stock.stock_value', compact('products'));

    } // End Method




}
 