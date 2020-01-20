<?php
Route::group(['namespace' => 'Abs\CompanyPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'company-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});