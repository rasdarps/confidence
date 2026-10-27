<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\UnitsDataTable;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UnitController extends Controller
{
    function __construct()
    {
            $this->middleware('permission:unit-list', ['only' => ['index','show']]);
         $this->middleware('permission:unit-create', ['only' => ['create','store']]);
         $this->middleware('permission:unit-edit', ['only' => ['edit','update']]);
         $this->middleware('permission:unit-delete', ['only' => ['destroy']]);
    } 

    public function index(UnitsDataTable $dataTable)
    {
        //
        return $dataTable->render('backend.unit.index');

    }

    
    public function create(){
        return view('backend.unit.create');
    } // End Method 

    public function store(Request $request){
        $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('units', 'name')->whereNull('deleted_at')],
        ], [
            'name.required' => 'Please enter a Unit name',
            'name.unique' => 'This unit already exists',
        ]);

        try {
            // Use only validated data and add created_by
            $data = $validatedData;
            $data['created_by'] = Auth::id();
            
            // Use a transaction to ensure data integrity
            DB::transaction(function () use ($data) {
                Unit::create($data);
            });

            return response()->json([
                'status' => 200,
                'message' => "Unit '{$data['name']}' was successfully created",
            ]);

        } catch(\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]); 
        }
    } // End Method 

    /**
     * Display the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);
            $unit = Unit::find($id);
            
            if (!$unit) {
                return redirect()->route('units.index')->with('error', 'Unit not found.');
            }
            
            return view('backend.unit.show', compact('unit'));
        } catch (\Exception $e) {
            return redirect()->route('units.index')->with('error', 'Invalid unit ID.');
        }
    }

    public function edit(string $encryptedId){
        try {
            $id = Crypt::decrypt($encryptedId);
            $unit = Unit::find($id);
            
            if (!$unit) {
                return redirect()->route('units.index')->with('error', 'Unit not found.');
            }
            
            return view('backend.unit.edit', compact('unit'));
        } catch (\Exception $e) {
            return redirect()->route('units.index')->with('error', 'Invalid unit ID.');
        }
    }// End Method 

    public function update(Request $request, string $encryptedId){ 
        try {
            $id = Crypt::decrypt($encryptedId);
            $unit = Unit::find($id);
            
            if (!$unit) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Unit not found.',
                ]);
            }

            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:units,name,' . $id,
            ], [
                'name.required' => 'Please enter a unit name',
                'name.unique' => 'This unit already exists',
            ]);

            try {
                DB::transaction(function () use ($validatedData, $unit) {
                    $unit->update([
                        'name' => $validatedData['name'],
                        'updated_by' => Auth::id(),
                    ]);
                });

                return response()->json([
                    'status' => 200,
                    'message' => "Unit '{$validatedData['name']}' was successfully updated",
                    'redirect' => route('units.index')
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
                'message' => 'Invalid unit ID.',
            ]);
        }
    }// End Method 

    public function destroy(string $encryptedId){
        try {
            $unit = Unit::findOrFail(Crypt::decrypt($encryptedId));

            if ($unit->products()->count() > 0) {
                $message = 'Cannot delete unit with associated products. Reassign products to another unit first.';
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['status' => 422, 'message' => $message], 422);
                }
                return redirect()->route('units.index')->with(['message' => $message, 'alert-type' => 'error']);
            }

            $unitName = $unit->name;

            DB::transaction(function () use ($unit) {
                $unit->delete();
            });

            $message = "Unit '{$unitName}' was successfully deleted.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('units.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);

        } catch (\Exception $e) {
            $message = 'Failed to delete unit.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('units.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    } // End Method 

    
}
