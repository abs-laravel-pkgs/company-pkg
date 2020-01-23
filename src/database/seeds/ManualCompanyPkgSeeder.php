<?php
namespace Abs\CompanyPkg\Database\Seeds;

use Abs\CompanyPkg\Company;
use Illuminate\Database\Seeder;

class ManualCompanyPkgSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$no_of_companies = $this->command->ask("How many companies you want to create?", '1');

		for ($i = 1; $i <= $no_of_companies; $i++) {
			$company_id = $this->command->ask("Enter company id?", '1');
			Company::createFromId($company_id);
		}
	}
}
