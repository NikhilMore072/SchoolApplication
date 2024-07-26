<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\RolesAndMenu;
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
            'is_active' => '|string|max:255',
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


    public function showRoles(){
        $data = Role::all();
        return response()->json($data);
    }

public function showAccess($roleId) {
    $role = Role::find($roleId);
    $menuList = Menu::all(); 

    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
                                  ->pluck('menu_id')
                                  ->toArray();

    return response()->json([
        'role' => $role,
        'menuList' => $menuList,
        'assignedMenuIds' => $assignedMenuIds, 
    ]);
}


public function updateAccess(Request $request, $roleId)
{
    $request->validate([
        'menu_ids' => 'required|array',
        'menu_ids.*' => 'exists:menus,menu_id',
    ]);
    RolesAndMenu::where('role_id', $roleId)->delete();
    $menuIds = $request->input('menu_ids');
    foreach ($menuIds as $menuId) {
        RolesAndMenu::create([
            'role_id' => $roleId,
            'menu_id' => $menuId,
        ]);
    }

    return response()->json(['message' => 'Access updated successfully']);
}

public function navMenulist(Request $request)
{
    $roleId = 3;

    // Get the menu IDs from RolesAndMenu where role_id is the specified value
    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
        ->pluck('menu_id')
        ->toArray();

    // Get the parent menu names and their IDs where parent_id is 0
    $parentMenus = Menu::where('parent_id', 0)
        ->whereIn('menu_id', $assignedMenuIds)
        ->get(['menu_id', 'name']);

    // Prepare the final response structure
    $menuList = [];

    foreach ($parentMenus as $parentMenu) {
        // Get the child menu names and their IDs where parent_id is the current parent menu ID
        $childMenus = Menu::where('parent_id', $parentMenu->menu_id)
            ->whereIn('menu_id', $assignedMenuIds)
            ->get(['menu_id', 'name']);

        // Add the parent menu and its children to the response structure
        $menuList[] = [
            'parent_menu_id' => $parentMenu->menu_id,
            'parent_menu_name' => $parentMenu->name,
            'child_menus' => $childMenus->map(function ($childMenu) {
                return [
                    'child_menu_id' => $childMenu->menu_id,
                    'child_menu_name' => $childMenu->name,
                ];
            })
        ];
    }

    return response()->json($menuList);
}




 
}
