<?php
    
namespace App\Http\Controllers\Backend;
    
use App\DataTables\Admin\Settings\UserDataTable;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;
    
class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    function __construct()
    {
        // Change from role-* to user-*
        $this->middleware('permission:user-list', ['only' => ['index','show']]);
        $this->middleware('permission:user-create', ['only' => ['create','store']]);
        $this->middleware('permission:user-edit', ['only' => ['edit','update']]);
        $this->middleware('permission:user-delete', ['only' => ['destroy']]);
    }
    
    public function index(UserDataTable $dataTable)
    {
        return $dataTable->render('users.index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        // ensures super admin role is not assigned to anyone during user creation
        $roles = Role::where('name', '!=', 'Super Admin')->pluck('name','name')->all();
        return view('users.create',compact('roles'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validatedData = $this->validate($request, [
            'name' => ['required','string', 'max:255'],
            'username' => ['required','string', 'max:255'],
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|digits:10|numeric|unique:users,phone',
            'password' => 'required|same:confirm_password',
            'roles' => 'required',
            'user_type' => 'required|in:Admin,User'
        ], [
            'name.required' => 'Full Name is required',
            'username.required' => 'Username is required',
            'email.required' => 'Email is required',
            'email.email' => 'Enter valid email',
            'email.unique' => 'Email already exists',
            'password.required' => 'Password is required',
            'password.same' => 'Password and Confirm Password must match',
            'roles.required' => 'At least one role must be selected',
            'user_type.required' => 'User Type is required',
            'user_type.in' => 'User Type must be either Admin or User',
        ]);

        try {
            // Get all data and hash password
            $data = $validatedData;
            $data['password'] = Hash::make($data['password']);
            $data['created_by'] = Auth::id();
            
            // Use transaction for data integrity
            DB::transaction(function () use ($data, $request) {
                // Create user
                $user = User::create($data);
                
                // Assign roles to user
                $user->assignRole($request->input('roles'));
                
                // Mark for password change only if NOT Super Admin
                if (! $user->hasRole('Super Admin')) {
                    $user->update(['must_change_password' => true]);
                }
            });

            return response()->json([
                'status' => 200,
                'message' => "User '{$data['name']}' was successfully created",
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
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function show($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);

            $user = User::find($id);
            
            if (!$user) {
                return redirect()->route('users.index')->with('error', 'User not found.');
            }
            
            return view('users.show', compact('user'));
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Invalid user ID.');
        }
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function edit($encryptedId)
    {
        try {
            // $encryptedId = str_replace(' ', '+', $encryptedId);
            $id = Crypt::decrypt($encryptedId);
            $user = User::find($id);
            
            if (!$user) {
                return redirect()->route('users.index')->with('error', 'User not found.');
            }
            
            // ensures super admin role is not assigned to anyone during user update
            $roles = Role::where('name', '!=', 'Super Admin')->pluck('name','name')->all();
            $userRole = $user->roles->pluck('name','name')->all();

            return view('users.edit', compact('user', 'roles', 'userRole'));
        } catch (\Exception $e) {
            return redirect()->route('users.index')->with('error', 'Invalid user ID.');
        }
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $encryptedId)
    {
        try {
            // $encryptedId = str_replace(' ', '+', $encryptedId);
            $id = Crypt::decrypt($encryptedId);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid user ID.',
            ]);
        }

        $user = User::find($id);
        
        if (!$user) {
            return response()->json([
                'status' => 404,
                'message' => 'User not found.',
            ]);
        }

       $validatedData = $this->validate($request, [
            'name' => ['required','string', 'max:255'],
            'username' => ['required','string', 'max:255', 'unique:users,username,' . $id],
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|digits:10|numeric|unique:users,phone,' . $id,
            'password' => 'nullable|same:confirm_password',
            'roles' => 'required',
            'user_type' => 'required|in:Admin,User'
        ], [
            'name.required' => 'Full Name is required',
            'username.required' => 'Username is required',
            'username.unique' => 'Username already exists',
            'email.required' => 'Email is required',
            'email.email' => 'Enter valid email',
            'email.unique' => 'Email already exists',
            'phone.required' => 'Phone is required',
            'phone.unique' => 'Phone number already exists',
            'password.same' => 'Password and Confirm Password must match',
            'roles.required' => 'At least one role must be selected',
            'user_type.required' => 'User Type is required',
            'user_type.in' => 'User Type must be either Admin or User',
        ]);

        try {
            
            //fetch only validated data
            $data = $validatedData;
            
            // Only update password if provided
            if ($request->filled('password')) {
                $data['password'] = Hash::make($data['password']);
                // Mark for password change only if NOT Super Admin
                if (! $user->hasRole('Super Admin')) {
                    $data['must_change_password'] = true;
                }
            } else {
                $data = Arr::except($data, array('password', 'confirm_password'));
            }
            
            $data['updated_by'] = Auth::id();
            
            // Use transaction for data integrity
            DB::transaction(function () use ($data, $request, $user, $id) {
                // Update user
                $user->update($data);
                
                // Remove existing roles and assign new ones
                DB::table('model_has_roles')->where('model_id', $id)->delete();
                $user->assignRole($request->input('roles'));
            });

            return response()->json([
                'status' => 200,
                'message' => "User '{$data['name']}' was successfully updated",
                'redirect' => route('users.index')
            ]);

        } catch(\Throwable $e) {
            return response()->json([
                'status' => 400,
                'message' => $e->getMessage(),
            ]); 
        }
    }
    
    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function destroy($encryptedId)
    {
        try {
            $id = Crypt::decrypt($encryptedId);

            $user = User::find($id);
            
            if (!$user) {
                return response()->json([
                    'status' => 404,
                    'message' => 'User not found.',
                ]);
            }
            
            $userName = $user->name;
            $user->delete();
            
            return response()->json([
                'status' => 200,
                'message' => "User '{$userName}' was successfully deleted.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 400,
                'message' => 'Invalid user ID.',
            ]);
        }
    }
}