<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index()
    {
        $this->authorize('view-roles');
        return Role::with('permissions')->get();
    }

    public function store(Request $request)
    {
        $this->authorize('manage-roles');
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create(['name' => $validated['name'], 'guard_name' => 'web']);

        if (!empty($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'), 201);
    }

    public function update(Request $request, $id)
    {
        $this->authorize('manage-roles');
        $role = Role::findOrFail($id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id),
            ],
            'permissions' => 'array'
        ]);

        $role->update(['name' => $validated['name']]);

        if (isset($validated['permissions'])) {
            $role->syncPermissions($validated['permissions']);
        }

        return response()->json($role->load('permissions'));
    }

    public function destroy($id)
    {
        $this->authorize('manage-roles');
        $role = Role::findOrFail($id);

        // Evita di eliminare ruoli critici se necessario
        if (in_array($role->name, ['Amministratore'])) {
            return response()->json(['message' => 'Non è possibile eliminare il ruolo Amministratore.'], 403);
        }

        $role->delete();

        return response()->json(null, 204);
    }

    public function permissions()
    {
        $this->authorize('manage-roles');
        return Permission::all();
    }
}
