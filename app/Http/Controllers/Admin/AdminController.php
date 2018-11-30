<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index()
    {
      if (Auth::user() && Auth::user()->hasRole('admin')) {
        return view('admin.dashboard');
	    } else {
	      abort(401);
	    }
    }
}
