<?php

use Illuminate\Database\Seeder;

use App\User;
use App\Role;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->delete();
	
		
		$admin= User::create(array(
			'name'     => 'Admin',
			'email'    => env('DEFAULT_ADMIN_EMAIL','admin@domain.com'),
			'password' => Hash::make(env('DEFAULT_ADMIN_PASSWORD')),
		));
		$api  = User::create(array(
			'name'     => 'Api User',
			'email'    => env('DEFAULT_API_EMAIL','api@domain.com'),
			'password' => Hash::make(env('DEFAULT_API_PASSWORD')),
		));
	
		$admin->assignRole('admin');
		$api->assignRole('api');
		
    }
}
