<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Report\ProductPriceHistoryDataTable;
use App\DataTables\Admin\Settings\ProductPriceDataTable;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

class ProductPriceController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:product-price-list', ['only' => ['index']]);
        $this->middleware('permission:product-price-history-list', ['only' => ['ProductPriceHistory']]);
        $this->middleware('permission:product-price-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:product-price-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ProductPriceDataTable $dataTable)
    {
        //
        // $products = Product::all();
        return $dataTable->render('backend.product_price.index');
      
    }

    public function ProductPriceHistory(ProductPriceHistoryDataTable $dataTable)
    {
        $products = Product::orderBy('name')->get();
        $users = User::orderBy('name')->get();
        return $dataTable->render('backend.product_price.product_price_history', compact('products', 'users'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $product = Product::findOrFail($id);

            return view('backend.product_price.edit', compact('product', 'encryptedId'));
        } catch (\Throwable $e) {
            return redirect()->route('product-prices.index')->with('error', 'Invalid product ID.');
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $encryptedId)
    {
        //
        $validated = $request->validate([
            'cost_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
        ], [
            'cost_price.required' => 'Please enter a cost price',
            'cost_price.numeric' => 'Cost price must be a number',
            'cost_price.min' => 'Cost price cannot be negative',
            'selling_price.required' => 'Please enter a selling price',
            'selling_price.numeric' => 'Selling price must be a number',
            'selling_price.min' => 'Selling price cannot be negative',
        ]);

        try {
            $id = Crypt::decrypt($encryptedId);
            $product = Product::find($id);

            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found.',
                ]);
            }

            DB::transaction(function () use ($validated, $product) {
                $product->cost_price = $validated['cost_price'];
                $product->selling_price = $validated['selling_price'];
                $product->updated_by = Auth::id();
                $product->save();

                ProductPrice::create([
                    'product_id' => $product->id,
                    'cost_price' => $validated['cost_price'],
                    'selling_price' => $validated['selling_price'],
                    'created_by' => Auth::id(),
                ]);
            });

            return response()->json([
                'status' => 200,
                'message' => 'Product price updated successfully.',
                'redirect' => route('product-prices.index'),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid product ID.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Failed to update product price.',
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
