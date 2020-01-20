<?php

namespace Abs\CompanyPkg;
use Abs\CompanyPkg\Company;
use App\Address;
use App\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CompanyController extends Controller {

	public function __construct() {
	}

	public function getCompanyList(Request $request) {
		$companys = Company::withTrashed()
			->select(
				'companys.id',
				'companys.code',
				'companys.name',
				DB::raw('IF(companys.mobile_no IS NULL,"--",companys.mobile_no) as mobile_no'),
				DB::raw('IF(companys.email IS NULL,"--",companys.email) as email'),
				DB::raw('IF(companys.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->where('companys.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->company_code)) {
					$query->where('companys.code', 'LIKE', '%' . $request->company_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->company_name)) {
					$query->where('companys.name', 'LIKE', '%' . $request->company_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->mobile_no)) {
					$query->where('companys.mobile_no', 'LIKE', '%' . $request->mobile_no . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('companys.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->orderby('companys.id', 'desc');

		return Datatables::of($companys)
			->addColumn('code', function ($company) {
				$status = $company->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $company->code;
			})
			->addColumn('action', function ($company) {
				$edit_img = asset('public/theme/img/table/cndn/edit.svg');
				$delete_img = asset('public/theme/img/table/cndn/delete.svg');
				return '
					<a href="#!/company-pkg/company/edit/' . $company->id . '">
						<img src="' . $edit_img . '" alt="View" class="img-responsive">
					</a>
					<a href="javascript:;" data-toggle="modal" data-target="#delete_company"
					onclick="angular.element(this).scope().deleteCompany(' . $company->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete_img . '" alt="delete" class="img-responsive">
					</a>
					';
			})
			->make(true);
	}

	public function getCompanyFormData($id = NULL) {
		if (!$id) {
			$company = new Company;
			$address = new Address;
			$action = 'Add';
		} else {
			$company = Company::withTrashed()->find($id);
			$address = Address::where('address_of_id', 24)->where('entity_id', $id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['company'] = $company;
		$this->data['address'] = $address;
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function saveCompany(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Company Code is Required',
				'code.max' => 'Maximum 255 Characters',
				'code.min' => 'Minimum 3 Characters',
				'code.unique' => 'Company Code is already taken',
				'name.required' => 'Company Name is Required',
				'name.max' => 'Maximum 255 Characters',
				'name.min' => 'Minimum 3 Characters',
				'gst_number.required' => 'GST Number is Required',
				'gst_number.max' => 'Maximum 191 Numbers',
				'mobile_no.max' => 'Maximum 25 Numbers',
				// 'email.required' => 'Email is Required',
				'address_line1.required' => 'Address Line 1 is Required',
				'address_line1.max' => 'Maximum 255 Characters',
				'address_line1.min' => 'Minimum 3 Characters',
				'address_line2.max' => 'Maximum 255 Characters',
				// 'pincode.required' => 'Pincode is Required',
				// 'pincode.max' => 'Maximum 6 Characters',
				// 'pincode.min' => 'Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:3',
					'unique:companys,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => 'required|max:255|min:3',
				'gst_number' => 'required|max:191',
				'mobile_no' => 'nullable|max:25',
				// 'email' => 'nullable',
				'address' => 'required',
				'address_line1' => 'required|max:255|min:3',
				'address_line2' => 'max:255',
				// 'pincode' => 'required|max:6|min:6',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$company = new Company;
				$company->created_by_id = Auth::user()->id;
				$company->created_at = Carbon::now();
				$company->updated_at = NULL;
				$address = new Address;
			} else {
				$company = Company::withTrashed()->find($request->id);
				$company->updated_by_id = Auth::user()->id;
				$company->updated_at = Carbon::now();
				$address = Address::where('address_of_id', 24)->where('entity_id', $request->id)->first();
			}
			$company->fill($request->all());
			$company->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$company->deleted_at = Carbon::now();
				$company->deleted_by_id = Auth::user()->id;
			} else {
				$company->deleted_by_id = NULL;
				$company->deleted_at = NULL;
			}
			$company->gst_number = $request->gst_number;
			$company->axapta_location_id = $request->axapta_location_id;
			$company->save();

			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 24;
			$address->entity_id = $company->id;
			$address->address_type_id = 40;
			$address->name = 'Primary Address';
			$address->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json(['success' => true, 'message' => ['Company Details Added Successfully']]);
			} else {
				return response()->json(['success' => true, 'message' => ['Company Details Updated Successfully']]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function deleteCompany($id) {
		$delete_status = Company::withTrashed()->where('id', $id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 24)->where('entity_id', $id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
