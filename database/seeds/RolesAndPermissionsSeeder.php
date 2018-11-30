<?php

use Illuminate\Database\Seeder;
use App\Permission;
use App\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		DB::table('roles')->delete();
		DB::table('permissions')->delete();
		
		// create permissions
		$modify_data = Permission::create(['name' => 'modify data', 'label'=>'Modify API Data']);
	
		// create roles and assign existing permissions
		$role = Role::create(['name' => 'admin', 'label'=>'Administrator']);
		$role->givePermissionTo($modify_data);
	
		$role = Role::create(['name' => 'api', 'label'=>'API Client']);
		$role->givePermissionTo($modify_data);
    }
}
