<?php

namespace Abs\CompanyPkg\Models;

use Abs\BasicPkg\Models\BaseModel;
use Abs\HelperPkg\Traits\SeederTrait;
use Abs\UserPkg\User;
use App\Models\Address;
use App\Models\Attachment;
use Faker\Factory as Faker;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Company extends BaseModel {
	use SeederTrait;
	use SoftDeletes;

	protected $table = 'companies';
	protected $fillable = [
		'code',
		'name',
		'contact_number',
		'email',
		'theme',
		'domain',
	];

	// Relationships --------------------------------------------------------------

	public function logo(): BelongsTo
	{
		return $this->belongsTo(Attachment::class, 'logo_id');
	}

	public function address(): BelongsTo
	{
		return $this->belongsTo(Address::class, 'address_id');
	}

	public function admin() {
		$admin = User::where('username', $this->code . 'a1')->where('company_id', $this->id)->first();
		if (!$admin) {
			dd('Default admin not found');
		}
		return $admin;
	}

	public static function createFromObject($record_data, $company = null) {
		$record = self::firstOrNew([
			'id' => $record_data->id,
		]);
		$record->code = $record_data->code;
		$record->name = $record_data->name;
		$record->contact_number = $record_data->contact_number;
		$record->email = $record_data->email;
		$record->save();

		$user = User::firstOrNew([
			'company_id' => $record->id,
			'username' => $record->code . 'a1',
		]);
		$user->user_type_id = 1;
		$user->entity_id = null;
		$user->first_name = $record->name;
		$user->last_name = 'Admin 1';
		$user->email = $record->code . 'a1@' . $record->code . '.com';
		$user->password = $record_data->password; //'$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2'; //
		$user->mobile_number = $record_data->mobile_number;
		$user->save();
		return $record;
	}

	public static function createFromId($company_id) {
		$record = self::firstOrNew([
			'id' => $company_id,
		]);

		$faker = Faker::create();

		$record->code = 'c' . $company_id;
		$record->name = $faker->company;
		// $record->address = $faker->address;
		// $record->cin_number = 'C' . $company_id . 'CIN1';
		// $record->gst_number = 'C' . $company_id . 'GST1';
		$record->domain = '';
		$record->email = $faker->safeEmail;
		$record->contact_number = $company_id . '0000000001';
		// $record->reference_code = $record->code;
		$record->save();

		$record->createDefaultAdmin();
		return $record;
	}

	public function createDefaultAdmin() {
		$user = User::firstOrNew([
			'company_id' => $this->id,
			'username' => $this->code . 'a1',
		]);
		$user->user_type_id = null;
		$user->entity_id = null;
		$user->first_name = $this->name;
		$user->last_name = ' Admin 1';
		$user->email = $this->code . 'a1@' . $this->code . '.com';
		$user->password = 'Test@123'; //Hash::make('Test@123'); //'$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2'; //
		$user->mobile_number = $this->id . '000000001';
		$user->has_mobile_login = 0;

		$user->save();

		$user->roles()->sync([1]);
		return $user;
	}

	//public function address() {
	//	return $this->morphOne(Address::class, 'addressable');
	//}
	//

	// Relationships to auto load
	public static function relationships($action = '', $format = ''): array
	{
		$relationships = [];

		if ($action === 'index') {
			$relationships = array_merge($relationships, [
				//'billingAddress',
				//'paymentMode',
				//'status',
			]);
		} elseif ($action === 'read') {
			$relationships = array_merge($relationships, [
				'logo',
				'address',
				'defaultCountry',
			]);
		} elseif ($action === 'options') {
			$relationships = array_merge($relationships, [
			]);
		}

		return $relationships;
	}

}
