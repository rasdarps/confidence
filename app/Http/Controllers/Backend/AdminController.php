<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminController extends Controller
{
     public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        $notification = array(
            'message' => 'User Logout Successfully', 
            'alert-type' => 'success'
        );

        return redirect('/admin/login')->with($notification);
    } // End Method 


    public function Profile(){
        $id = Auth::user()->id;
        $adminData = User::find($id);
        return view('admin.admin_profile_view',compact('adminData'));

    }// End Method 


    public function EditProfile(){

        $id = Auth::user()->id;
        $editData = User::find($id);
        return view('admin.admin_profile_edit',compact('editData'));
    }// End Method 

    public function StoreProfile(Request $request){
        $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        $id = Auth::user()->id;
        $data = User::find($id);
        $data->name = $request->name;
        $data->email = $request->email;
        $data->username = $request->username;

        $data->save();

        $notification = array(
            'message' => 'Admin Profile Updated Successfully', 
            'alert-type' => 'info'
        );

        return redirect()->route('admin.profile')->with($notification);

    }// End Method


    public function ChangePassword(){

        return view('admin.admin_change_password');

    }// End Method


    public function UpdatePassword(Request $request){

        $validateData = $request->validate([
            'oldpassword' => 'required',
            'newpassword' => [
                'required',
                'different:oldpassword',
                Password::min(6)->mixedCase()->numbers()->symbols(),
            ],
            'confirm_password' => 'required|same:newpassword',
        ], [
            'oldpassword.required' => 'Current password is required',
            'newpassword.required' => 'New password is required',
            'newpassword.different' => 'New password must be different from current password',
            'confirm_password.required' => 'Password confirmation is required',
            'confirm_password.same' => 'New Password and Confirm Password must match',
        ]);

        $hashedPassword = Auth::user()->password;
        if (Hash::check($request->oldpassword,$hashedPassword )) {
            $users = User::find(Auth::id());
            $users->password = bcrypt($request->newpassword);
            $users->must_change_password = false;
            $users->save();

            session()->flash('message','Password Updated Successfully');
            return redirect()->back();
        } else{
            session()->flash('message','Old password is not match');
            return redirect()->back();
        }

    }// End Method



}
 