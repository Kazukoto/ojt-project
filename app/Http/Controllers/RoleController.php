<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Project;

class RoleController extends Controller
{
    /**
     * Display role management page
     */
    public function index()
    {
        $roles = Role::orderBy('id', 'asc')->get();
        
        return view('admin.rolemanagement', [
            'roles' => Role::all(),
            'projects' => Project::orderBy('name')->get(),
        ]);
    }


    /**
     * Store a new role
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name',
            'description' => 'nullable|string',
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        Role::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role created successfully!');
    }

    /**
     * Update an existing role
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'role_name' => 'required|string|max:255|unique:roles,role_name,' . $id,
            'description' => 'nullable|string',
            'hourly_rate' => 'required|numeric|min:0',
        ]);

        $role = Role::findOrFail($id);
        $role->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role updated successfully!');
    }

    /**
     * Delete a role
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Check if any employees are using this role
        $employeeCount = \App\Models\Employee::where('role_id', $id)->count();
        
        if ($employeeCount > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', "Cannot delete role. {$employeeCount} employee(s) are assigned to this role.");
        }

        $role->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Role deleted successfully!');
    }

    public function storeProject(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:projects,name',
            'description' => 'nullable|string',
        ]);

        Project::create($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Project created successfully!');
    }

    /**
     * Update an existing project
     */
    public function updateProject(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255|unique:projects,name,' . $id,
            'description' => 'nullable|string',
        ]);

        $project = Project::findOrFail($id);
        $project->update($validated);

        return redirect()->route('admin.roles.index')
            ->with('success', 'Project updated successfully!');
    }

    /**
     * Delete a project
     */
    public function destroyProject($id)
    {
        $project = Project::findOrFail($id);

        // Optional: check if project is in use before deleting
        // $inUse = \App\Models\Attendance::where('project_id', $id)->count();
        // if ($inUse > 0) {
        //     return redirect()->route('superadmin.roles.index')
        //         ->with('error', "Cannot delete project. It is assigned to {$inUse} attendance record(s).");
        // }

        $project->delete();

        return redirect()->route('admin.roles.index')
            ->with('success', 'Project deleted successfully!');
    }
}   