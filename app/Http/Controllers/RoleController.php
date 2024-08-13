<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use App\Models\Role;
use App\Models\User;
use App\Models\RolesAndMenu;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Exceptions\JWTException;
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
            'role_id' => 'required|string|max:255|unique:roles,role_id',
            'rolename' => 'required|string|max:255|unique:roles,rolename',
        ]);

        $role = Role::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Role created successfully.',
            'data' => $role
        ], 201); 
    }

    public function edit($role_id)
    {
        $role = Role::find($role_id);

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

    public function update(Request $request, $role_id)
    {
        $validatedData = $request->validate([
            'rolename' => 'required|string|max:50',
            'is_active' => 'required|string|max:1',
        ]);

        $role = Role::find($role_id);

        if ($role) {
            // Check if the role is being used before deactivating it
            if ($validatedData['is_active'] === 'N') {
                $isRoleInUse = User::where('role_id', $role_id)->exists();

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

    public function delete($role_id)
    {
        $role = Role::find($role_id);

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

    public function showRoles()
    {
        $data = Role::all();
        return response()->json($data);
    }

    public function showAccess($role_id) {
        $role = Role::find($role_id);
        $menuList = Menu::all(); 

        $assignedMenuIds = RolesAndMenu::where('role_id', $role_id)
                                      ->pluck('menu_id')
                                      ->toArray();

        return response()->json([
            'role' => $role,
            'menuList' => $menuList,
            'assignedMenuIds' => $assignedMenuIds, 
        ]);
    }

    public function updateAccess(Request $request, $role_id)
    {
        $request->validate([
            'menu_ids' => 'required|array',
            'menu_ids.*' => 'exists:menus,menu_id',
        ]);

        RolesAndMenu::where('role_id', $role_id)->delete();
        $menuIds = $request->input('menu_ids');
        foreach ($menuIds as $menuId) {
            RolesAndMenu::create([
                'role_id' => $role_id,
                'menu_id' => $menuId,
            ]);
        }

        return response()->json(['message' => 'Access updated successfully']);
    }
    private function authenticateUser()
    {
        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return null;
        }
    }


    public function navMenulist(Request $request)
{
    // $roleId = 3;
    $user = $this->authenticateUser();
    $roleId = $user->role_id;

    // Get the menu IDs from RolesAndMenu where role_id is the specified value
    $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
        ->pluck('menu_id')
        ->toArray();

    // Get the parent menus where parent_id is 0 and order by sequence
    $parentMenus = Menu::where('parent_id', 0)
        ->whereIn('menu_id', $assignedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    // Prepare the final response structure
    $menuList = $parentMenus->map(function ($parentMenu) use ($assignedMenuIds) {
        return [
            'menu_id' => $parentMenu->menu_id,
            'name' => $parentMenu->name,
            'url' => $parentMenu->url,
            'sub_menus' => $this->getSubMenus($parentMenu->menu_id, $assignedMenuIds)
        ];
    });

    return response()->json($menuList);
}

public function getSubMenus($parentId, $assignedMenuIds)
{
    // Get the submenus where parent_id is the given parent ID and order by sequence
    $subMenus = Menu::where('parent_id', $parentId)
        ->whereIn('menu_id', $assignedMenuIds)
        ->orderBy('sequence')
        ->get(['menu_id', 'name', 'url']);

    // Recursively get each submenu's submenus
    return $subMenus->map(function ($subMenu) use ($assignedMenuIds) {
        return [
            'menu_id' => $subMenu->menu_id,
            'name' => $subMenu->name,
            'url' => $subMenu->url,
            'sub_menus' => $this->getSubMenus($subMenu->menu_id, $assignedMenuIds)
        ];
    });
}


    //Menu Methods 
     public function getMenus()
    {
        return response()->json(Menu::all());
    }

    public function storeMenus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:menus,menu_id',
            'sequence' => 'required|integer|unique:menus',
        ]);

        $validated['parent_id'] = $validated['parent_id'] ?? 0;


        $menu = Menu::create($validated);
        return response()->json($menu, 201);
    }

 

    public function showMenus($id)
{  
    $menu = Menu::findOrFail($id);
    $menu->parent_id_display = $menu->parent_id == 0 ? 'None' : $menu->parent_id;
    return response()->json($menu);
}


 

    public function updateMenus(Request $request, $id)
    {
        $menu = Menu::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'url' => 'required|string|max:255',
            'parent_id' => 'nullable|integer|exists:menus,menu_id',
            'sequence' => 'nullable|integer|unique:menus,sequence,' . $id . ',menu_id', // Unique except for the current menu
        ]);
        
        $validated['parent_id'] = $validated['parent_id'] ?? 0;
    
        $menu->update($validated);
        return response()->json($menu, 200);
    }
    


    public function destroy($id)
    {
        $menu = Menu::findOrFail($id);
        $menu->delete();
        return response()->json(null, 204);
    }

   

}
   // public function updateMenus(Request $request, $id)
    // {
    //     $menu = Menu::findOrFail($id);
    //     $validated = $request->validate([
    //         'name' => 'required|string|max:255',
    //         'url' => 'required|string|max:255',
    //         'parent_id' => 'nullable|integer|exists:menus,menu_id',
    //         'sequence' => 'required|integer|unique:menus,menu_id',
    //     ]);
        
    //     $validated['parent_id'] = $validated['parent_id'] ?? 0;


    //     $menu->update($validated);
    //     return response()->json($menu, 200);
    // }

       // public function showMenus($id)
    // {  
    //    $menu =  Menu::findOrFail($id);    
    //     return response()->json();

    // }


        // public function navMenulist(Request $request)
    // {
    //     // $roleId = 3;
    //     $user = $this->authenticateUser();
    //     $roleId = $user->role_id;

    //     // Get the menu IDs from RolesAndMenu where role_id is the specified value
    //     $assignedMenuIds = RolesAndMenu::where('role_id', $roleId)
    //         ->pluck('menu_id')
    //         ->toArray();

    //     // Get the parent menus where parent_id is 0
    //     $parentMenus = Menu::where('parent_id', 0)
    //         ->whereIn('menu_id', $assignedMenuIds)
    //         ->get(['menu_id', 'name', 'url']);

    //     // Prepare the final response structure
    //     $menuList = $parentMenus->map(function ($parentMenu) use ($assignedMenuIds) {
    //         return [
    //             'menu_id' => $parentMenu->menu_id,
    //             'name' => $parentMenu->name,
    //             'url' => $parentMenu->url,
    //             'sub_menus' => $this->getSubMenus($parentMenu->menu_id, $assignedMenuIds)
    //         ];
    //     });

    //     return response()->json($menuList);
    // }

    // public function getSubMenus($parentId, $assignedMenuIds)
    // {
    //     // Get the submenus where parent_id is the given parent ID
    //     $subMenus = Menu::where('parent_id', $parentId)
    //         ->whereIn('menu_id', $assignedMenuIds)
    //         ->get(['menu_id', 'name', 'url']);

    //     // Recursively get each submenu's submenus
    //     return $subMenus->map(function ($subMenu) use ($assignedMenuIds) {
    //         return [
    //             'menu_id' => $subMenu->menu_id,
    //             'name' => $subMenu->name,
    //             'url' => $subMenu->url,
    //             'sub_menus' => $this->getSubMenus($subMenu->menu_id, $assignedMenuIds)
    //         ];
    //     });
    // }