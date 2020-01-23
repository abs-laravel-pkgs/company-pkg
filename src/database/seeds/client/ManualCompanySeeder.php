<?php
use Illuminate\Database\Seeder;

class ManualCompanySeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$this->call(Abs\CompanyPkg\Database\Seeds\ManualCompanyPkgSeeder::class);
	}
}
