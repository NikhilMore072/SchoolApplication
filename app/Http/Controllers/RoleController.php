<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
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


    // public function update(Request $request, $id){
    //     $validatedData = $request->validate([
    //         'rolename' => 'required|string|max:255',
    //         'is_active' => '|string|max:255',
    //     ]);

    //     $role = Role::find($id);

    //     if ($role) {
    //         $role->update($validatedData);
    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Role updated successfully.',
    //             'data' => $role
    //         ]);
    //     }

    //     return response()->json([
    //         'success' => false,
    //         'message' => 'Role not found.'
    //     ], 404); 
    // }

    public function update(Request $request, $id)
{
    $validatedData = $request->validate([
        'rolename' => 'required|string|max:255',
        'is_active' => 'string|max:255',
    ]);

    $role = Role::find($id);

    if ($role) {
        if ($validatedData['is_active'] === 'N') {
            $isRoleInUse = User::where('role_id', $id)->exists();

            if ($isRoleInUse) {
                return response()->json([
                    'success' => false,
                    'message' => 'Role cannot be deactivated as it is being used in another table.'
                ], 400);
            }
        }

        // Update the role
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







// public function navMenulist(Request $request)
// {
//     $roleId = 3;

//     // Get the menu IDs from RolesAndMenu where role_id is the specified value
//     $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
//         ->pluck('menu_id')
//         ->toArray();

//     // Get the parent menu names and their IDs where parent_id is 0
//     $parentMenus = Menu::where('parent_id', 0)
//         ->whereIn('menu_id', $assignedMenuIds)
//         ->get(['menu_id', 'name','url']);

//     // Prepare the final response structure
//     $menuList = [];

//     foreach ($parentMenus as $parentMenu) {
//         // Get the child menu names and their IDs where parent_id is the current parent menu ID
//         $childMenus = Menu::where('parent_id', $parentMenu->menu_id)
//             ->whereIn('menu_id', $assignedMenuIds)
//             ->get(['menu_id', 'name']);

//         // Add the parent menu and its children to the response structure
//         $menuList[] = [
//             'parent_menu_id' => $parentMenu->menu_id,
//             'parent_menu_name' => $parentMenu->name,
//             'parent_menu_url' => $parentMenu->url,
//             'child_menus' => $childMenus->map(function ($childMenu) {
//                 return [
//                     'child_menu_id' => $childMenu->menu_id,
//                     'child_menu_name' => $childMenu->name,
//                     'child_menu_url' => $childMenu->url,
//                 ];
//             })
//         ];
//     }

//     return response()->json($menuList);
// }


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
        ->get(['menu_id', 'name', 'url']);

    // Prepare the final response structure
    $menuList = [];

    foreach ($parentMenus as $parentMenu) {
        // Get the child menus where parent_id is the current parent menu ID
        $childMenus = Menu::where('parent_id', $parentMenu->menu_id)
            ->whereIn('menu_id', $assignedMenuIds)
            ->get(['menu_id', 'name', 'url']);

        // Prepare child menu list with their submenus
        $childMenuList = [];

        foreach ($childMenus as $childMenu) {
            // Get the submenus where parent_id is the current child menu ID
            $subMenus = Menu::where('parent_id', $childMenu->menu_id)
                ->whereIn('menu_id', $assignedMenuIds)
                ->get(['menu_id', 'name', 'url']);

            // Add the child menu and its submenus to the child menu list
            $childMenuList[] = [
                'child_menu_id' => $childMenu->menu_id,
                'child_menu_name' => $childMenu->name,
                'child_menu_url' => $childMenu->url,
                'sub_menus' => $subMenus->map(function ($subMenu) {
                    return [
                        'sub_menu_id' => $subMenu->menu_id,
                        'sub_menu_name' => $subMenu->name,
                        'sub_menu_url' => $subMenu->url,
                    ];
                })
            ];
        }

        // Add the parent menu and its children to the response structure
        $menuList[] = [
            'parent_menu_id' => $parentMenu->menu_id,
            'parent_menu_name' => $parentMenu->name,
            'parent_menu_url' => $parentMenu->url,
            'child_menus' => $childMenuList
        ];
    }

    return response()->json($menuList);
}





 
}
