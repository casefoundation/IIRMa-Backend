<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UsersController extends Controller
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
                $users = User::where('name', 'LIKE', "%$keyword%")->orWhere('email', 'LIKE', "%$keyword%")
                    ->paginate($perPage);
            } else {
                $users = User::paginate($perPage);
            }

            return view('admin.users.index', compact('users'));
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
            $roles = Role::select('id', 'name', 'label')->get();
            $roles = $roles->pluck('label', 'name');

            return view('admin.users.create', compact('roles'));
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
            $this->validate($request, ['name' => 'required', 'email' => 'required', 'password' => 'required', 'roles' => 'required']);

            $data = $request->except('password');
            $data['password'] = bcrypt($request->password);
            $user = User::create($data);

            foreach ($request->roles as $role) {
                $user->assignRole($role);
            }

            return redirect('admin/users')->with('flash_message', 'User added!');
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
            $user = User::findOrFail($id);

            return view('admin.users.show', compact('user'));
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
            $roles = Role::select('id', 'name', 'label')->get();
            $roles = $roles->pluck('label', 'name');

            $user = User::with('roles')->select('id', 'name', 'email')->findOrFail($id);
            $user_roles = [];
            foreach ($user->roles as $role) {
                $user_roles[] = $role->name;
            }

            return view('admin.users.edit', compact('user', 'roles', 'user_roles'));
        } else {
            abort(401);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int      $id
     *
     * @return void
     */
    public function update(Request $request, $id)
    {
        if (Auth::user() && Auth::user()->hasRole('admin')) {
            $this->validate($request, ['name' => 'required', 'email' => 'required', 'roles' => 'required']);

            $data = $request->except('password');
            if ($request->has('password')) {
                $data['password'] = bcrypt($request->password);
            }

            $user = User::findOrFail($id);
            $user->update($data);

            $user->roles()->detach();
            foreach ($request->roles as $role) {
                $user->assignRole($role);
            }

            return redirect('admin/users')->with('flash_message', 'User updated!');
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
            User::destroy($id);

            return redirect('admin/users')->with('flash_message', 'User deleted!');
        } else {
            abort(401);
        }
    }
}
