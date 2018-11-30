<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Role;
use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RolesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index(Request $request)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $keyword = $request->get('search');
            $perPage = 15;

            if (!empty($keyword)) {
                $roles = Role::where('name', 'LIKE', "%$keyword%")->orWhere('label', 'LIKE', "%$keyword%")
                    ->paginate($perPage);
            } else {
                $roles = Role::paginate($perPage);
            }

            return view('admin.roles.index', compact('roles'));
        } else {
            abort(401);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return void
     */
    public function create()
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $permissions = Permission::select('id', 'name', 'label')->get()->pluck('label', 'name');

            return view('admin.roles.create', compact('permissions'));
        } else {
            abort(401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function store(Request $request)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $this->validate($request, ['name' => 'required']);

            $role = Role::create($request->all());
            $role->permissions()->detach();

            if ($request->has('permissions')) {
                foreach ($request->permissions as $permission_name) {
                    $permission = Permission::whereName($permission_name)->first();
                    $role->givePermissionTo($permission);
                }
            }

            return redirect('admin/roles')->with('flash_message', 'Role added!');
        } else {
            abort(401);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function show($id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $role = Role::findOrFail($id);

            return view('admin.roles.show', compact('role'));
        } else {
            abort(401);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function edit($id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $role = Role::findOrFail($id);
            $permissions = Permission::select('id', 'name', 'label')->get()->pluck('label', 'name');

            return view('admin.roles.edit', compact('role', 'permissions'));
        } else {
            abort(401);
        } 
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     *
     * @return void
     */
    public function update(Request $request, $id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $this->validate($request, ['name' => 'required']);

            $role = Role::findOrFail($id);
            $role->update($request->all());
            $role->permissions()->detach();

            if ($request->has('permissions')) {
                foreach ($request->permissions as $permission_name) {
                    $permission = Permission::whereName($permission_name)->first();
                    $role->givePermissionTo($permission);
                }
            }

            return redirect('admin/roles')->with('flash_message', 'Role updated!');
        } else {
            abort(401);
        } 
        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     *
     * @return void
     */
    public function destroy($id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            Role::destroy($id);

            return redirect('admin/roles')->with('flash_message', 'Role deleted!');
            } else {
            abort(401);
        } 
    }
}
