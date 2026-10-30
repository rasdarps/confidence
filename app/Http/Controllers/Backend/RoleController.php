<?php
    
namespace App\Http\Controllers\Backend;

use App\DataTables\Admin\Settings\RoleDataTable;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
    
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('permission:role-list', ['only' => ['index', 'show']]);
        $this->middleware('permission:role-create', ['only' => ['create', 'store']]);
        $this->middleware('permission:role-edit', ['only' => ['edit', 'update']]);
        $this->middleware('permission:role-delete', ['only' => ['destroy']]);
    }
    
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(RoleDataTable $dataTable)
    {
        return $dataTable->render('roles.index');
    }
    
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (!Auth::user()->isCentral()) {
            return redirect()->route('roles.index')->with('error', 'Unauthorized. Only Central Headquarters staff can create system roles.');
        }

        $permission = Permission::get();
        return view('roles.create', compact('permission'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (!Auth::user()->isCentral()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized. Only Central Headquarters staff can create system roles.',
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'Unauthorized. Only Central Headquarters staff can create system roles.');
        }

        if (strtolower(trim((string)$request->input('name'))) === 'super admin') {
            $msg = 'The "Super Admin" role is reserved for system administration.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 422,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return redirect()->back()->withErrors(['name' => $msg])->withInput();
        }

        $this->validate($request, [
            'name' => 'required|string|max:255|unique:roles,name',
            'permission' => 'required|array|min:1',
        ], [
            'name.required' => 'Please enter a role name',
            'name.unique' => 'A role with this name already exists',
            'permission.required' => 'Please select at least one permission for this role',
            'permission.min' => 'Please select at least one permission for this role',
        ]);

        $role = DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => trim((string)$request->input('name')),
            ]);
            $permissionNames = Permission::whereIn('id', (array)$request->input('permission'))->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
            return $role;
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 200,
                'message' => "Role '{$role->name}' was successfully created",
                'redirect' => route('roles.index'),
            ]);
        }

        return redirect()->route('roles.index')
                        ->with('success', 'Role created successfully');
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
        } catch (\Exception $e) {
            return redirect()->route('roles.index')->with('error', 'Invalid role ID.');
        }

        $role = Role::find($id);
        if (!$role) {
            return redirect()->route('roles.index')->with('error', 'Role not found.');
        }

        $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
            ->where("role_has_permissions.role_id", $id)
            ->get();
    
        return view('roles.show', compact('role', 'rolePermissions'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function edit(string $encryptedId)
    {
        if (!Auth::user()->isCentral()) {
            return redirect()->route('roles.index')->with('error', 'Unauthorized. Only Central Headquarters staff can modify system roles.');
        }

        try {
            $id = Crypt::decrypt($encryptedId);
        } catch (\Exception $e) {
            return redirect()->route('roles.index')->with('error', 'Invalid role ID.');
        }

        $role = Role::find($id);
        if (!$role) {
            return redirect()->route('roles.index')->with('error', 'Role not found.');
        }

        if (strtolower(trim((string)$role->name)) === 'super admin') {
            return redirect()->route('roles.index')->with('error', 'The Super Admin role is protected and cannot be modified.');
        }

        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id", $id)
            ->pluck('role_has_permissions.permission_id', 'role_has_permissions.permission_id')
            ->all();
    
        return view('roles.edit', compact('role', 'permission', 'rolePermissions'));
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
        if (!Auth::user()->isCentral()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized. Only Central Headquarters staff can modify system roles.',
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'Unauthorized. Only Central Headquarters staff can modify system roles.');
        }

        try {
            $id = Crypt::decrypt($encryptedId);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 400, 'message' => 'Invalid role ID.'], 400);
            }
            return redirect()->route('roles.index')->with('error', 'Invalid role ID.');
        }

        $role = Role::find($id);
        if (!$role) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['status' => 404, 'message' => 'Role not found.'], 404);
            }
            return redirect()->route('roles.index')->with('error', 'Role not found.');
        }

        if (strtolower(trim((string)$role->name)) === 'super admin') {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'The Super Admin role is protected and cannot be modified.',
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'The Super Admin role is protected and cannot be modified.');
        }

        if (strtolower(trim((string)$request->input('name'))) === 'super admin') {
            $msg = 'The "Super Admin" role is reserved for system administration.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'status' => 422,
                    'message' => $msg,
                    'errors' => ['name' => [$msg]],
                ], 422);
            }
            return redirect()->back()->withErrors(['name' => $msg])->withInput();
        }

        $this->validate($request, [
            'name' => 'required|string|max:255|unique:roles,name,' . $id,
            'permission' => 'required|array|min:1',
        ], [
            'name.required' => 'Please enter a role name',
            'name.unique' => 'A role with this name already exists',
            'permission.required' => 'Please select at least one permission for this role',
            'permission.min' => 'Please select at least one permission for this role',
        ]);

        DB::transaction(function () use ($request, $role) {
            $role->name = trim((string)$request->input('name'));
            $role->save();

            $permissionNames = Permission::whereIn('id', (array)$request->input('permission'))->pluck('name')->toArray();
            $role->syncPermissions($permissionNames);
        });

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'status' => 200,
                'message' => "Role '{$role->name}' was successfully updated",
                'redirect' => route('roles.index'),
            ]);
        }

        return redirect()->route('roles.index')
                        ->with('success', 'Role updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $encryptedId)
    {
        if (!Auth::user()->isCentral()) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'Unauthorized. Only Central Headquarters staff can delete system roles.',
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'Unauthorized. Only Central Headquarters staff can delete system roles.');
        }

        try {
            $id = Crypt::decrypt($encryptedId);
        } catch (\Exception $e) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['status' => 400, 'message' => 'Invalid role ID.'], 400);
            }
            return redirect()->route('roles.index')->with('error', 'Invalid role ID.');
        }

        $role = Role::find($id);

        if (!$role) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 404,
                    'message' => 'Role not found.',
                ]);
            }
            return redirect()->route('roles.index')->with('error', 'Role not found.');
        }

        if (strtolower(trim((string)$role->name)) === 'super admin') {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'status' => 403,
                    'message' => 'The Super Admin role is protected and cannot be deleted.',
                ], 403);
            }
            return redirect()->route('roles.index')->with('error', 'The Super Admin role is protected and cannot be deleted.');
        }

        $roleName = $role->name;
        $role->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => "Role '{$roleName}' was successfully deleted.",
            ]);
        }

        return redirect()->route('roles.index')
                        ->with('success', 'Role deleted successfully');
    }
}
