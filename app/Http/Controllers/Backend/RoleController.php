<?php
    
namespace App\Http\Controllers\Backend;


use App\DataTables\Admin\Settings\RoleDataTable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use Spatie\Permission\Models\Role;
use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
    
class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    function __construct()
    {
         $this->middleware('permission:role-list', ['only' => ['index','show']]);
         $this->middleware('permission:role-create', ['only' => ['create','store']]);
         $this->middleware('permission:role-edit', ['only' => ['edit','update']]);
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
        $permission = Permission::get();
        return view('roles.create',compact('permission'));
    }
    
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);
    
         $role = Role::create([
              'name' => $request->input('name'),
            ]);
        // $role->syncPermissions($request->input('permission'));
        $permissionNames = Permission::whereIn('id', $request->input('permission'))->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);
    
        return redirect()->route('roles.index')
                        ->with('success','Role created successfully');
    }
    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);
        $role = Role::find($id);
        $rolePermissions = Permission::join("role_has_permissions","role_has_permissions.permission_id","=","permissions.id")
            ->where("role_has_permissions.role_id",$id)
            ->get();
    
        return view('roles.show',compact('role','rolePermissions'));
    }
    
    /**
     * Show the form for editing the specified resource.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function edit(string $encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);
        $role = Role::find($id);
        $permission = Permission::get();
        $rolePermissions = DB::table("role_has_permissions")->where("role_has_permissions.role_id",$id)
            ->pluck('role_has_permissions.permission_id','role_has_permissions.permission_id')
            ->all();
    
        return view('roles.edit',compact('role','permission','rolePermissions'));
    }
    
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);

        $this->validate($request, [
            'name' => 'required',
            'permission' => 'required',
        ]);
    
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->save();
    
        // $role->syncPermissions($request->input('permission'));
        $permissionNames = Permission::whereIn('id', $request->input('permission'))->pluck('name')->toArray();
        $role->syncPermissions($permissionNames);
    
        return redirect()->route('roles.index')
                        ->with('success','Role updated successfully');
    }
    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $encryptedId
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $encryptedId)
    {
        $id = Crypt::decrypt($encryptedId);
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

        $roleName = $role->name;
        $role->delete();

        if (request()->expectsJson() || request()->ajax()) {
            return response()->json([
                'status' => 200,
                'message' => "Role '{$roleName}' was successfully deleted.",
            ]);
        }

        return redirect()->route('roles.index')
                        ->with('success','Role deleted successfully');
    }
}