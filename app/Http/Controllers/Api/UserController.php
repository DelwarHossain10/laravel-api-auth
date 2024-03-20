<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function register(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required',
            'roles' => 'required',

        ]);

        if ($validated->fails()) {
            return response([
                'message' => $validated->errors(),
                'status' => 'error'
            ], 200);
        }
        DB::beginTransaction();
        try {

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password)
            ]);

            $user->assignRole($request->roles);

            $token = $user->createToken($request->email)->plainTextToken;
            DB::commit();
            return response([
                'token' => $token,
                'message' => 'Registration Success',
                'status' => 'success'
            ], 201);
        } catch (\Exception $e) {
            dd($e);
            DB::rollback();
        }
    }

    public function login(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validated->fails()) {
            return response([
                'message' => $validated->errors(),
                'status' => 'error'
            ], 200);
        }


        $user = User::where('email', $request->email)->first();
        if ($user && Hash::check($request->password, $user->password)) {

            $token = $user->createToken($request->email)->plainTextToken;

            return response([
                'token' => $token,
                'message' => 'Login Success',
                'status' => 'success'
            ], 200);
        }
        return response([
            'message' => 'The Provided Credentials are incorrect',
            'status' => 'failed'
        ], 401);
    }

    public function logout()
    {
        auth()->user()->tokens()->delete();
        return response([
            'message' => 'Logout Success',
            'status' => 'success'
        ], 200);
    }

    public function logged_user()
    {

        $role = null;
        $permissions = null;
        $loggeduser = auth()->user();


        if (isset(auth()->user()->roles[0])) {
            $roleID = auth()->user()->roles[0]->id;
            $role = Role::find($roleID);

            $rolePermissions = Permission::join("role_has_permissions", "role_has_permissions.permission_id", "=", "permissions.id")
                ->whereIn("role_has_permissions.role_id", auth()->user()->roles->pluck('id'))
                ->get();
            $permissions = $rolePermissions;
        }
        return response([
            'user' => $loggeduser,
            'permissions' => $permissions,
            'message' => 'Logged User Data',
            'status' => 'success'
        ], 200);
    }

    public function change_password(Request $request)
    {
        $request->validate([
            'password' => 'required',
        ]);
        $loggeduser = auth()->user();
        $loggeduser->password = Hash::make($request->password);
        $loggeduser->update();
        return response([
            'message' => 'Password Changed Successfully',
            'status' => 'success'
        ], 200);
    }

    public function role_list()
    {
        $roles = Role::pluck('name', 'name')->all();
        return response([
            'roles' => $roles,
            'message' => 'All Role List',
            'status' => 'success'
        ], 200);
    }

    public function permission_list()
    {
        $permission = Permission::select('name', 'id')->get();
        return response([
            'permission' => $permission,
            'message' => 'All Permission List',
            'status' => 'success'
        ], 200);
    }

    public function user_list()
    {
        $userList = User::get();
        return response([
            'users' => $userList,
            'message' => 'All User List',
            'status' => 'success'
        ], 200);
    }

    public function user_update(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
        ]);

        if ($validated->fails()) {
            return response([
                'message' => $validated->errors(),
                'status' => 'error'
            ], 200);
        }

        DB::beginTransaction();
        try {

            $user = User::find($id);
            $user->update([
                'name' => $request->name,
                'email' => $request->email
            ]);

            $user->syncRoles($request->roles);
            DB::commit();
            return response([
                'user' => $user,
                'message' => 'Registration Success',
                'status' => 'success'
            ], 201);

        } catch (\Exception $e) {
            dd($e);
            DB::rollback();
        }
    }
}
