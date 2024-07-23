<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all(); 
        return response()->json([
            'success' => true,
            'data' => $roles
        ]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'rolename' => 'required|string|max:255',
        ]);

        $role = Role::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role
        ], 201); 
    }


    public function edit($id)
    {
        $role = Role::find($id);

        if ($role) {
            return response()->json([
                'success' => true,
                'data' => $role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Role not found.'
        ], 404);
    }


    public function update(Request $request, $id){
        $validatedData = $request->validate([
            'rolename' => 'required|string|max:255',
            'status' => '|string|max:255',
        ]);

        $role = Role::find($id);

        if ($role) {
            $role->update($validatedData);
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully.',
                'data' => $role
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Role not found.'
        ], 404); 
    }

    public function delete($id)
{
    $role = Role::find($id);

    if ($role) {
        $role->delete();

        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully.'
        ]);
    }

    return response()->json([
        'success' => false,
        'message' => 'Role not found.'
    ], 404);
}


 
}
