<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Artisan;
use Illuminate\Support\Facades\Auth;

class DatasetImporterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return void
     */
    public function index(Request $request)
    {
      if (Auth::user() && Auth::user()->hasRole('admin')) {
			  return view('admin.importer.index');
		  } else {
			  abort(401);
		  }
    }

    /**
     * Import data from file
     *
     * @return void
     */
    public function importData(Request $request)
    {
			if (Auth::user() && Auth::user()->hasRole('admin')) {
				$commandArg = [];
				$commandArg['--replace-data'] = "no";
				
				if ($request->has('file_type')) {
					$commandArg['--file-type'] = $request->file_type;
				}

				
				if ($request->hasFile('data_file')) {
					$file = $request->file('data_file');
					$name = $file->getClientOriginalName().'.'.$file->getClientOriginalExtension();
					$file->move(storage_path('uploads/'), $name);
					
					$commandArg['--file'] = storage_path('uploads/').$name;
					
				}
				if ($request->has('enity_type')) {
					$commandArg['--entity-type'] = $request->enity_type;
				}
			
				try {
					Artisan::call("impactspace:analyze", $commandArg);
				} catch (\Exception $e) {
					return Response::make($e->getMessage(), 500);
					// return redirect('admin/importer')->with('error_message', 'There was an error when processing your request');
				}
			
				return redirect('admin/importer')->with('flash_message', 'Data set imported succesfuly.');
			} else {
			  abort(401);
		  }
    }

}
