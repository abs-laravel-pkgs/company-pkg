<?php

Route::group(['namespace' => 'Abs\CompanyPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'company-pkg'], function () {
	Route::get('/companies/get-list', 'CompanyController@getCompanyList')->name('getCompanyList');
	Route::get('/company/get-form-data', 'CompanyController@getCompanyFormData')->name('getCompanyFormData');
	Route::post('/company/save', 'CompanyController@saveCompany')->name('saveCompany');
	Route::get('/company/delete', 'CompanyController@deleteCompany')->name('deleteCompany');
	Route::get('/company/view', 'CompanyController@viewCompany')->name('viewCompany');

});