<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use DB;
use Illuminate\Support\Facades\Validator;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        //
        try {
            $roles = Role::orderBy('id', 'DESC')->get();

            return response([
                'role' => $roles,
                'message' => 'All Roles List',
                'status' => 'Success'
            ], 200);
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

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
        try {
            $permissions = Permission::get();
            return response([
                'permissions' => $permissions,
                'message' => 'All Roles List',
                'status' => 'Success'
            ], 200);
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

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        $validated = Validator::make($request->all(), [
            'name' => 'required|unique:roles,name',
            'permission' => 'required',
        ]);

        if ($validated->fails()) {
            return response([
                'message' => $validated->errors(),
                'status' => 'error'
            ], 200);
        }

        DB::beginTransaction();
        try {
            $role = Role::create(['name' => $request->get('name'), 'guard_name' => 'web']);
            $role->syncPermissions($request->permission);
            DB::commit();
            return response([
                'role' => $role,
                'message' => 'Role created successfully',
                'status' => 'Success'
            ], 200);
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

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(Role $role)
    {
        //
        try {
            $role = $role;
            $rolePermissions = $role->permissions;

            return response([
                'role' => $role,
                'permissions' => $rolePermissions,
                'message' => 'Role Individual Show',
                'status' => 'Success'
            ], 200);
        } catch (\Exception $e) {
            return response(
                [
                    "message" => $e->getMessage(),
                    "status" => "error",
                ],
                403
            );
        }

        // return view('roles.show', compact('role', 'rolePermissions'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        //
        try {
            $role = $role;
            $rolePermissions = $role->permissions->pluck('name')->toArray();
            $permissions = Permission::get();

            return response([
                'role' => $role,
                'permissions' => $rolePermissions,
                'message' => 'Role Individual Edit',
                'status' => 'Success'
            ], 200);
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

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        DB::beginTransaction();
        try {
            $validated = Validator::make($request->all(), [
                'name' => 'required',
                'permission' => 'required',
            ]);

            if ($validated->fails()) {
                return response([
                    'message' => $validated->errors(),
                    'status' => 'error'
                ], 200);
            }

            $role = Role::find($id);
            $role->update($request->only('name'));

            $role->syncPermissions($request->get('permission'));

            return response([
                'role' => $role,
                'message' => 'Role updated successfully',
                'status' => 'Success'
            ], 200);
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

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $role = Role::find($id);
            $role->delete();
            return response([
                'message' => 'Role deleted successfully',
                'status' => 'Success'
            ], 200);
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
}
