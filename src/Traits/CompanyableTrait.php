<?php
namespace Abs\CompanyPkg\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

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

	public function scopeFilterCurrentUserCompany($query) {
		$query->where('company_id',  Auth::user()->company_id);
	}


}
