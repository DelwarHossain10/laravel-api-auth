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
            "name" => "required",
            "email" => "required|email|unique:users",
            "password" => "required",
            "roles" => "required",
        ]);

        if ($validated->fails()) {
            return response(
                [
                    "message" => $validated->errors(),
                    "status" => "error",
                ],
                200
            );
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                "name" => $request->name,
                "email" => $request->email,
                "password" => Hash::make($request->password),
            ]);
            $user->assignRole($request->roles);
            $token = $user->createToken($request->email)->plainTextToken;
            DB::commit();
            return response(
                [
                    "token" => $token,
                    "message" => "Registration Success",
                    "status" => "success",
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function login(Request $request)
    {

        $validated = Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required",
        ]);

        if ($validated->fails()) {
            return response(
                [
                    "message" => $validated->errors(),
                    "status" => "error",
                ],
                200
            );
        }

        try {

            $user = User::where("email", $request->email)->first();
            if ($user && Hash::check($request->password, $user->password)) {
                $token = $user->createToken($request->email)->plainTextToken;

                return response(
                    [
                        "token" => $token,
                        "message" => "Login Success",
                        "status" => "success",
                    ],
                    200
                );
            }
            return response(
                [
                    "message" => "The Provided Credentials are incorrect",
                    "status" => "failed",
                ],
                401
            );
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function logout()
    {
        try {
            auth()->user()->tokens()->delete();
            return response(
                [
                    "message" => "Logout Success",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {

            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function logged_user()
    {
        try {
            $role = null;
            $permissions = null;
            $loggeduser = auth()->user();
            $extraPermission = collect(auth()->user()->permissions)->pluck(
                "name"
            );

            if (isset(auth()->user()->roles[0])) {
                $roleID = auth()->user()->roles[0]->id;
                $role = Role::find($roleID);

                $rolePermissions = Permission::join(
                    "role_has_permissions",
                    "role_has_permissions.permission_id",
                    "=",
                    "permissions.id"
                )
                    ->whereIn(
                        "role_has_permissions.role_id",
                        auth()
                            ->user()
                            ->roles->pluck("id")
                    )
                    ->get();

                $permissionsList = collect($rolePermissions)->pluck("name");
                $permissionsList = $permissionsList
                    ->merge($extraPermission)
                    ->unique();
            }
            return response(
                [
                    "user" => $loggeduser,
                    "Permissions List" => $permissionsList,
                    "message" => "Logged User Data",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function change_password(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                "password" => "required",
            ]);
            $loggeduser = auth()->user();
            $loggeduser->password = Hash::make($request->password);
            $loggeduser->update();
            DB::commit();
            return response(
                [
                    "message" => "Password Changed Successfully",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollback();
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function role_list()
    {
        try {
            $roles = Role::pluck("name", "name")->all();
            return response(
                [
                    "roles" => $roles,
                    "message" => "All Role List",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function permission_list()
    {
        try {
            $permission = Permission::select("name", "id")->get();
            return response(
                [
                    "permission" => $permission,
                    "message" => "All Permission List",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function user_list()
    {
        try {
            $userList = User::get();
            return response(
                [
                    "users" => $userList,
                    "message" => "All User List",
                    "status" => "success",
                ],
                200
            );
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function user_update(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            "name" => "required",
            "email" => "required|email|unique:users,email," . $id,
        ]);

        if ($validated->fails()) {
            return response(
                [
                    "message" => $validated->errors(),
                    "status" => "error",
                ],
                200
            );
        }

        DB::beginTransaction();
        try {
            $user = User::find($id);
            $user->update([
                "name" => $request->name,
                "email" => $request->email,
            ]);

            $user->syncRoles($request->roles);
            DB::commit();
            return response(
                [
                    "user" => $user,
                    "message" => "User Update successfully",
                    "status" => "success",
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }

    public function assign_permission(Request $request, $id)
    {
        $validated = Validator::make($request->all(), [
            "permissions" => "required",
        ]);

        if ($validated->fails()) {
            return response(
                [
                    "message" => $validated->errors(),
                    "status" => "error",
                ],
                200
            );
        }

        DB::beginTransaction();
        try {
            $user = User::find($id);
            $user->givePermissionTo($request->permissions);

            DB::commit();
            return response(
                [
                    "permissions" => $request->permissions,
                    "message" => "Assign Permission Successfully",
                    "status" => "success",
                ],
                201
            );
        } catch (\Exception $e) {
            DB::rollback();
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }
    }
}
