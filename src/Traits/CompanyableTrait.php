<?php
namespace Abs\CompanyPkg\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait CompanyableTrait {
	
	public static function hasCompany(){
		return self::$has_company = true;
	}


	public function getHasCompanyAttribute(){
		return $this->has_ompany;
	}

	public function company(): BelongsTo{
		return $this->belongsTo('App\Company');
	}

}
