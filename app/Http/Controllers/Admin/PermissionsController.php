<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionsController extends Controller
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
                $permissions = Permission::where('name', 'LIKE', "%$keyword%")->orWhere('label', 'LIKE', "%$keyword%")
                    ->paginate($perPage);
            } else {
                $permissions = Permission::paginate($perPage);
            }

            return view('admin.permissions.index', compact('permissions'));
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
            return view('admin.permissions.create');
        } else {
            abort(401);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return void
     */
    public function store(Request $request)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $this->validate($request, ['name' => 'required']);

            Permission::create($request->all());

            return redirect('admin/permissions')->with('flash_message', 'Permission added!');
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
            $permission = Permission::findOrFail($id);

            return view('admin.permissions.show', compact('permission'));
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
            $permission = Permission::findOrFail($id);

            return view('admin.permissions.edit', compact('permission'));
        } else {
            abort(401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int  $id
     *
     * @return void
     */
    public function update(Request $request, $id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $this->validate($request, ['name' => 'required']);

            $permission = Permission::findOrFail($id);
            $permission->update($request->all());

            return redirect('admin/permissions')->with('flash_message', 'Permission updated!');
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
            Permission::destroy($id);

            return redirect('admin/permissions')->with('flash_message', 'Permission deleted!');
        } else {
            abort(401);
        }
    }
}
