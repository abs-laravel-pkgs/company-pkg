<?php
namespace Abs\CompanyPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class CompanyPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//COMPANY
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'companies',
				'display_name' => 'Companies',
			],
			[
				'display_order' => 1,
				'parent' => 'companies',
				'name' => 'add-company',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'companies',
				'name' => 'delete-company',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'companies',
				'name' => 'delete-company',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}