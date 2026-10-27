<?php

namespace App\Http\Controllers\Backend;

use App\Imports\ProductImport;
use App\DataTables\Admin\Settings\ProductDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductCreationRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    function __construct()
    {
            $this->middleware('permission:product-list', ['only' => ['index','show', 'export']]);
         $this->middleware('permission:product-create', ['only' => ['create','store']]);
            $this->middleware('permission:product-edit', ['only' => ['edit','update', 'updateStock']]);
            $this->middleware('permission:product-edit|purchase-create|sale-create', ['only' => ['findByBarcode']]);
            $this->middleware('permission:product-delete', ['only' => ['destroy']]);
            $this->middleware('permission:product-list', ['only' => ['printBarcodes']]);

    }

    public function index(ProductDataTable $dataTable){
     
        $categories = Category::orderBy('name', 'asc')->get();
        return $dataTable->render('backend.product.index', compact('categories'));
    } // End Method 

    //barcode lookup method for POS scanning
    public function findByBarcode(Request $request)
    {
        $barcode = trim((string) $request->input('barcode', ''));
        $product = Product::with(['category', 'unit'])->where('barcode', $barcode)->first();

        if (!$product) {
            return response()->json([
                'status' => 404,
                'message' => 'Product not found for this barcode.',
            ]);
        }

        $outletId = auth()->check() ? auth()->user()->assignedOutletId() : null;
        if (!$outletId) {
            $outletId = \App\Models\Outlet::where('is_central', true)->value('id');
        }

        $productData = $product->toArray();
        $productData['quantity'] = $outletId ? (float) $product->quantityForOutlet($outletId) : (float) $product->quantity;

        return response()->json($productData);
    }

    public function create(){
        $units = Unit::orderBy('name', 'asc')->get();
        $categories = Category::orderBy('name', 'asc')->get();

        return view('backend.product.create', compact('units', 'categories'));
    } // End Method 

    public function import(Request $request)
    {
        try {
            $validated = $request->validate([
                'file' => ['required', 'file', 'mimes:xls,xlsx,csv', 'max:5120'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }

        try {
            $import = new ProductImport(Auth::id());
            Excel::import($import, $validated['file']);

            $summary = $import->summary();
            $message = "Imported {$summary['imported']} product(s).";

            if ($summary['failed'] > 0) {
                $message .= " {$summary['failed']} row(s) were skipped.";
            }

            return response()->json([
                'status' => 'success',
                'message' => $message,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(ProductCreationRequest $request){
            $productData = $request->getProductData();

            
        try {

            DB::transaction(function () use ($productData , $request) {
                $product = Product::create($productData);

                if (empty($product->barcode)) {
                    $barcode = Product::generateBarcode($product->name, $product->id, $product->created_at);
                    $product->barcode = $barcode;
                    $product->saveQuietly();
                }

                $centralOutletId = \App\Models\Outlet::where('is_central', true)->value('id')
                    ?? \App\Models\Outlet::orderBy('id')->value('id');
                if ($centralOutletId) {
                    \App\Models\OutletStock::firstOrCreate([
                        'outlet_id' => $centralOutletId,
                        'product_id' => $product->id,
                    ], [
                        'quantity' => 0,
                        'stock_level' => 0,
                    ]);
                }
            });
            return response()->json([
                'status' => 'success',
                'message' => 'Product created successfully!',
            ], 200);

        }
        catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } 
         catch(\Throwable $e) {

            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]); 
        }
    } // End Method 
    
    /**
     * Generate a unique barcode for the product.
     */
    // private function generateUniqueBarcode()
    // {
    //     $barcode = '6' . str_pad(random_int(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
        
    //     while (Product::where('barcode', $barcode)->exists()) {
    //         $barcode = '6' . str_pad(random_int(10000000000, 99999999999), 11, '0', STR_PAD_LEFT);
    //     }
        
    //     return $barcode;
    // }
    
    /**
     * Display the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    // public function show($encryptedId)
    // {
    //     try {
    //         $id = Crypt::decrypt($encryptedId);
    //         $product = Product::with(['unit', 'category'])->find($id);
            
    //         if (!$product) {
    //             return redirect()->route('products.index')->with('error', 'Product not found.');
    //         }
            
    //         return view('backend.product.show', compact('product'));
    //     } catch (\Exception $e) {
    //         return redirect()->route('products.index')->with('error', 'Invalid product ID.');
    //     }
    // }

     /**
     * Show product details (for quick view modal)
     */
    public function show(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            
            // Return HTML for modal
            $html = view('backend.product.partials.product-details-modal', compact('product','id'))->render();
            
            return response()->json([
                'status' => 'success',
                'html' => $html
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 404);
        }
    }

    public function edit(string $encryptedId){
        try {
            $id = Crypt::decrypt($encryptedId);
            $product = Product::with(['unit', 'category'])->find($id);
            
            if (!$product) {
                return redirect()->route('products.index')->with('error', 'Product not found.');
            }
            
          $units = Unit::orderBy('name', 'asc')->get();
            $categories = Category::orderBy('name', 'asc')->get();
                                                return view('backend.product.edit', compact('product', 'units', 'categories', 'encryptedId'));
        } catch (\Exception $e) {
            return redirect()->route('products.index')->with('error', 'Invalid product ID.');
        }
    }// End Method 

    public function update(Request $request, string $encryptedId){ 
        try {
            $id = Crypt::decrypt($encryptedId);
            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found.',
                ]);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:products,name,' . $id,
                'barcode'     => 'nullable|string|max:50|unique:products,barcode,' . $id,
                'unit_id' => 'required|exists:units,id',
                'category_id' => 'required|exists:categories,id',
                'stock_level' => 'required|numeric|min:0',
            ], [
                'name.required' => 'Please enter a product name',
                'name.unique' => 'This product already exists',
                'barcode.unique'       => 'This barcode is already registered',
                'barcode.string'       => 'Barcode must be a string',
                'barcode.max'          => 'Barcode must not exceed 50 characters',
                'unit_id.required' => 'Please select a unit',
                'unit_id.exists' => 'Selected unit is invalid',
                'category_id.required' => 'Please select a category',
                'category_id.exists' => 'Selected category is invalid',
                'stock_level.required' => 'Please enter stock level',
                'stock_level.numeric' => 'Stock level must be a number',
                'stock_level.min' => 'Stock level cannot be negative',
            ]);

            try {
                DB::transaction(function () use ($validatedData, $product) {
                    $barcodeInput = isset($validatedData['barcode']) ? trim((string) $validatedData['barcode']) : '';

                    $updateData = [
                        'name' => $validatedData['name'],
                        'barcode' => $barcodeInput !== '' ? $barcodeInput : $product->barcode,
                        'unit_id' => $validatedData['unit_id'],
                        'category_id' => $validatedData['category_id'],
                        'stock_level' => $validatedData['stock_level'],
                        'updated_by' => Auth::id(),
                    ];
                    
                    // Update quantity if it's less than the new stock level
                    if ($product->quantity < $validatedData['stock_level']) {
                        $updateData['quantity'] = $validatedData['stock_level'];
                    }
                    
                    $product->update($updateData);
                });

                return response()->json([
                    'status' => 200,
                    'message' => "Product '{$validatedData['name']}' was successfully updated",
                    'redirect' => route('products.index')
                ]);

            } catch(\Throwable $e) {
                return response()->json([
                    'status' => 400,
                    'message' => $e->getMessage(),
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid product ID.',
            ]);
        }
    }// End Method 


    /**
     * Print barcode labels for selected products.
     */
    public function printBarcodes(Request $request)
    {
        $idsInput = $request->query('ids', []);

        if (is_string($idsInput)) {
            $ids = array_filter(array_map('intval', explode(',', $idsInput)));
        } elseif (is_array($idsInput)) {
            $ids = array_filter(array_map('intval', $idsInput));
        } else {
            $ids = [];
        }

        if (empty($ids)) {
            return redirect()->route('products.index')->with([
                'message' => 'Please select at least one product to print barcodes.',
                'alert-type' => 'warning'
            ]);
        }

        $products = Product::whereIn('id', $ids)
            ->whereNotNull('barcode')
            ->where('barcode', '!=', '')
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'barcode']);

        if ($products->isEmpty()) {
            return redirect()->route('products.index')->with([
                'message' => 'No printable barcodes found for the selected products.',
                'alert-type' => 'warning'
            ]);
        }

        return view('backend.product.print_barcodes', compact('products'));
    }


    public function destroy(string $encryptedId){
        try {
            $id = Crypt::decrypt($encryptedId);
            $product = Product::find($id);
            
            if (!$product) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Product not found.',
                ]);
            }

            // Prevent deleting products that are already referenced by a purchase.
            if ($product->purchasedetails()->exists()) {
                return response()->json([
                    'status' => 422,
                    'message' => "Product '{$product->name}' cannot be deleted because it has purchase transaction records.",
                ], 422);
            }

            // Prevent deleting products that were already sold.
            if ($product->invoicedetails()->exists()) {
                return response()->json([
                    'status' => 422,
                    'message' => "Product '{$product->name}' cannot be deleted because it has sales records.",
                ], 422);
            }
            
            $productName = $product->name;
            
            DB::transaction(function () use ($product) {
                $product->delete();
            });
            
            return response()->json([
                'status' => 200,
                'message' => "Product '{$productName}' was successfully deleted.",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid product ID.',
            ]);
        }
    } // End Method 

}

