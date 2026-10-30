<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\OutletsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OutletController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:outlet-list', ['only' => ['index']]);
        $this->middleware('permission:outlet-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:outlet-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:outlet-delete', ['only' => ['destroy']]);
    }

    public function index(OutletsDataTable $dataTable)
    {
        return $dataTable->render('backend.outlet.index');
    }

    public function create()
    {
        return view('backend.outlet.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('outlets', 'name')->whereNull('deleted_at')],
            'code' => ['required', 'string', 'max:50', Rule::unique('outlets', 'code')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_central' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        try {
            $validated['is_central'] = $request->boolean('is_central');
            $validated['is_active'] = $request->boolean('is_active', true);

            // If marking this as central, ensure no other outlet is central
            if ($validated['is_central']) {
                Outlet::where('is_central', true)->update(['is_central' => false]);
            }

            $outlet = DB::transaction(function () use ($validated) {
                return Outlet::create($validated);
            });

            return response()->json([
                'status' => 200,
                'message' => "Outlet '{$outlet->name}' was created successfully.",
                'redirect' => route('outlets.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function edit($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $outlet = Outlet::findOrFail($id);

            return view('backend.outlet.edit', compact('outlet'));
        } catch (\Exception $e) {
            return redirect()->route('outlets.index')->with('error', 'Invalid outlet ID.');
        }
    }

    public function update(Request $request, $encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $outlet = Outlet::findOrFail($id);

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255', Rule::unique('outlets', 'name')->ignore($outlet->id)->whereNull('deleted_at')],
                'code' => ['required', 'string', 'max:50', Rule::unique('outlets', 'code')->ignore($outlet->id)->whereNull('deleted_at')],
                'phone' => ['nullable', 'string', 'max:50'],
                'email' => ['nullable', 'email', 'max:100'],
                'address' => ['nullable', 'string', 'max:500'],
                'is_central' => ['nullable', 'boolean'],
                'is_active' => ['nullable', 'boolean'],
            ]);

            $validated['is_central'] = $request->boolean('is_central');
            $validated['is_active'] = $request->boolean('is_active', true);

            // Protect central warehouse from being un-centralized if it's the only one
            if ($outlet->is_central && !$validated['is_central']) {
                $otherCentral = Outlet::where('is_central', true)->where('id', '!=', $outlet->id)->exists();
                if (!$otherCentral) {
                    return response()->json([
                        'status' => 422,
                        'message' => 'The system must have at least one Central Warehouse.',
                    ], 422);
                }
            }

            if ($validated['is_central']) {
                Outlet::where('id', '!=', $outlet->id)->where('is_central', true)->update(['is_central' => false]);
            }

            DB::transaction(function () use ($outlet, $validated) {
                $outlet->update($validated);
            });

            return response()->json([
                'status' => 200,
                'message' => "Outlet '{$outlet->name}' was updated successfully.",
                'redirect' => route('outlets.index'),
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 422,
                'errors' => $e->errors(),
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function destroy($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $outlet = Outlet::findOrFail($id);

            if ($outlet->is_central) {
                return redirect()->route('outlets.index')->with('error', 'Central Warehouse cannot be deleted.');
            }

            // Deactivate or soft-delete
            $outlet->update(['is_active' => false]);
            $outlet->delete();

            return redirect()->route('outlets.index')->with('success', 'Outlet deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->route('outlets.index')->with('error', 'Failed to delete outlet.');
        }
    }
}

