<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\StockTransferDataTable;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Services\StockTransferService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\ValidationException;

class StockTransferController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:stock-transfer-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:stock-transfer-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:stock-transfer-receive', ['only' => ['receive']]);
        $this->middleware('permission:stock-transfer-cancel', ['only' => ['cancel']]);
    }

    public function index(StockTransferDataTable $dataTable)
    {
        $outlets = Outlet::where('is_active', true)->orderBy('is_central', 'desc')->orderBy('name')->get();
        return $dataTable->render('backend.stock_transfer.index', compact('outlets'));
    }

    public function create()
    {
        $user = Auth::user();
        $userOutletId = $user ? $user->assignedOutletId() : null;

        // If user is assigned to an outlet, default from_outlet is their outlet; otherwise Central
        $centralOutlet = Outlet::where('is_central', true)->first();
        $defaultFromOutletId = $userOutletId ?? ($centralOutlet?->id ?? Outlet::orderBy('id')->value('id'));

        $fromOutlets = Outlet::where('is_active', true)->get();
        $toOutlets = Outlet::where('is_active', true)->get();

        // Products with stock in source warehouse
        $products = Product::with(['category', 'unit', 'outletStocks'])->orderBy('name')->get();

        $transferNo = StockTransfer::generateTransferNo();
        $date = Carbon::now()->format('Y-m-d');

        return view('backend.stock_transfer.create', compact(
            'fromOutlets',
            'toOutlets',
            'products',
            'defaultFromOutletId',
            'transferNo',
            'date'
        ));
    }

    public function store(Request $request, StockTransferService $service)
    {
        $validated = $request->validate([
            'from_outlet_id' => ['required', 'exists:outlets,id'],
            'to_outlet_id' => ['required', 'exists:outlets,id', 'different:from_outlet_id'],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'product_id' => ['required', 'array', 'min:1'],
            'product_id.*' => ['required', 'exists:products,id'],
            'quantity' => ['required', 'array', 'min:1'],
            'quantity.*' => ['required', 'numeric', 'min:0.01'],
        ], [
            'to_outlet_id.different' => 'Destination outlet must be different from source outlet.',
            'product_id.required' => 'At least one product must be added to the transfer.',
            'product_id.min' => 'At least one product must be added to the transfer.',
        ]);

        try {
            $items = [];
            foreach ($request->product_id as $index => $prodId) {
                $qty = (float) ($request->quantity[$index] ?? 0);
                if ($qty > 0) {
                    $items[] = [
                        'product_id' => (int) $prodId,
                        'quantity' => $qty,
                    ];
                }
            }

            if (empty($items)) {
                throw ValidationException::withMessages(['product_id' => 'Please provide valid quantities for the transferred products.']);
            }

            $transferData = [
                'from_outlet_id' => (int) $validated['from_outlet_id'],
                'to_outlet_id' => (int) $validated['to_outlet_id'],
                'transfer_date' => $validated['transfer_date'],
                'notes' => $validated['notes'] ?? null,
                'items' => $items,
            ];

            $transfer = $service->createAndDispatch($transferData, Auth::id());

            return response()->json([
                'status' => 200,
                'message' => "Stock Transfer #{$transfer->transfer_no} dispatched successfully.",
                'redirect' => route('stock-transfers.show', Crypt::encrypt($transfer->id)),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $transfer = StockTransfer::with([
                'fromOutlet',
                'toOutlet',
                'items.product.unit',
                'dispatchedBy',
                'receivedBy'
            ])->findOrFail($id);

            $user = Auth::user();
            $userOutletId = $user ? $user->assignedOutletId() : null;
            $canReceive = $transfer->status === 'dispatched'
                && auth()->user()->can('stock-transfer-receive')
                && (!$userOutletId || $userOutletId === $transfer->to_outlet_id);

            return view('backend.stock_transfer.show', compact('transfer', 'canReceive'));
        } catch (\Exception $e) {
            return redirect()->route('stock-transfers.index')->with('error', 'Transfer not found.');
        }
    }

    public function receive(Request $request, $encryptedId, StockTransferService $service)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $transfer = StockTransfer::with('items')->findOrFail($id);

            $user = Auth::user();
            $userOutletId = $user ? $user->assignedOutletId() : null;
            if ($userOutletId && $userOutletId !== $transfer->to_outlet_id) {
                return redirect()->back()->with('error', 'You are not authorized to receive stock for this outlet.');
            }

            $receivedQuantities = [];
            if ($request->has('qty_received') && is_array($request->qty_received)) {
                foreach ($request->qty_received as $itemId => $qty) {
                    $receivedQuantities[(int) $itemId] = max(0, (float) $qty);
                }
            }

            $service->receive($transfer, $receivedQuantities, Auth::id());

            return redirect()->route('stock-transfers.show', $encryptedId)
                ->with('success', "Transfer #{$transfer->transfer_no} marked as received. Outlet stock has been updated.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, $encryptedId, StockTransferService $service)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $transfer = StockTransfer::with('items')->findOrFail($id);

            $reason = (string) $request->input('reason', 'Cancelled by user');
            $service->cancel($transfer, Auth::id(), $reason);

            return redirect()->route('stock-transfers.show', $encryptedId)
                ->with('success', "Transfer #{$transfer->transfer_no} was cancelled and inventory was returned to the source warehouse.");
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
