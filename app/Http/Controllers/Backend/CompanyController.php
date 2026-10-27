<?php

namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\CompanyDataTable;
use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:company-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:company-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:company-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:company-delete', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(CompanyDataTable $dataTable)
    {
        //
        return $dataTable->render('backend.company.index');

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(CompanyDataTable $dataTable)
    {
        //
        return $dataTable->render('backend.company.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
         $validatedData = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('companies', 'name')->whereNull('deleted_at')],
            'address' => 'required|string|max:255',
            'phone' => ['required', 'numeric', 'digits:10', Rule::unique('companies', 'phone')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', Rule::unique('companies', 'email')->whereNull('deleted_at')],
            'tin_number' => ['required', 'regex:/^\d{10}$/'],
        ], [
            'name.required' => 'Please enter a Company name',
            'name.unique' => 'This company already exists',
            'address.required' => 'Please enter a Company address',
            'phone.required' => 'Please enter a Company phone number',
            'phone.unique' => 'This company phone number is already in use',
            'phone.digits' => 'Phone number must be 10 digits',
            'phone.numeric' => 'Phone number must be numeric',
            'email.email' => 'Please enter a valid Company email address',
            'email.unique' => 'This company email is already in use',
            'tin_number.required' => 'Please enter a TIN number',
            'tin_number.regex' => 'TIN number must be exactly 10 digits',
        ]);

        $formattedTin = $this->formatTinNumber($validatedData['tin_number']);
        if (Company::where('tin_number', $formattedTin)->whereNull('deleted_at')->exists()) {
            throw ValidationException::withMessages([
                'tin_number' => ['This company TIN number is already in use'],
            ]);
        }

        try {
            // Use only validated data and add created_by
            $data = $validatedData;
            $data['tin_number'] = $formattedTin;
            $data['created_by'] = Auth::id();
            
            // Use a transaction to ensure data integrity
            DB::transaction(function () use ($data) {
                Company::create($data);
            });

            return response()->json([
                'status' => 200,
                'message' => "Company '{$data['name']}' was successfully created",
                'redirect' => route('companies.index'),
            ]);

        } catch(\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]); 
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(string $id)
    {
        //
        $company = Company::findOrFail(Crypt::decrypt($id)); // if using encrypted IDs
        return view('backend.company.edit', compact('company'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {
            $companyId = Crypt::decrypt($id);
            $company = Company::find($companyId);

            if (!$company) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Company not found.',
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid company ID.',
            ]);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255|unique:companies,name,' . $companyId,
            'address' => 'required|string|max:255',
            'phone' => 'required|numeric|digits:10|unique:companies,phone,' . $companyId,
            'email' => 'nullable|email|unique:companies,email,' . $companyId,
            'tin_number' => ['required', 'regex:/^\d{10}$/'],
        ], [
            'name.required' => 'Please enter a Company name',
            'name.unique' => 'This company already exists',
            'address.required' => 'Please enter a Company address',
            'phone.required' => 'Please enter a Company phone number',
            'phone.unique' => 'This company phone number is already in use',
            'phone.digits' => 'Phone number must be 10 digits',
            'phone.numeric' => 'Phone number must be numeric',
            'email.email' => 'Please enter a valid Company email address',
            'email.unique' => 'This company email is already in use',
            'tin_number.required' => 'Please enter a TIN number',
            'tin_number.regex' => 'TIN number must be exactly 10 digits',
        ]);

        $formattedTin = $this->formatTinNumber($validatedData['tin_number']);
        if (Company::where('tin_number', $formattedTin)->where('id', '!=', $companyId)->exists()) {
            throw ValidationException::withMessages([
                'tin_number' => ['This company TIN number is already in use'],
            ]);
        }

        try {
            DB::transaction(function () use ($company, $validatedData, $formattedTin) {
                $company->update(array_merge($validatedData, [
                    'tin_number' => $formattedTin,
                    'updated_by' => Auth::id(),
                ]));
            });

            return response()->json([
                'status' => 200,
                'message' => "Company '{$validatedData['name']}' was successfully updated",
                'redirect' => route('companies.index'),
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]);
        }
    }

    private function formatTinNumber(string $tinNumber): string
    {
        $digits = preg_replace('/\D/', '', $tinNumber);
        return 'GHA-' . substr($digits, 0, 9) . '-' . substr($digits, 9, 1);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $companyId = Crypt::decrypt($id);
            $company = Company::find($companyId);

            if (!$company) {
                $message = 'Company not found.';

                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'status' => 404,
                        'message' => $message,
                    ], 404);
                }

                return redirect()->route('companies.index')->with([
                    'message' => $message,
                    'alert-type' => 'error',
                ]);
            }

            $companyName = $company->name;

            DB::transaction(function () use ($company) {
                $company->delete();
            });

            $message = "Company '{$companyName}' was successfully deleted.";

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 200,
                    'message' => $message,
                ]);
            }

            return redirect()->route('companies.index')->with([
                'message' => $message,
                'alert-type' => 'success',
            ]);
        } catch (\Exception $e) {
            $message = 'Failed to delete company.';

            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 400,
                    'message' => $message,
                ], 400);
            }

            return redirect()->route('companies.index')->with([
                'message' => $message,
                'alert-type' => 'error',
            ]);
        }
    }
}
