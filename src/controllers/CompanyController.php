<?php

namespace Abs\CompanyPkg;
use Abs\Basic\Address;
use Abs\Basic\Attachment;
use Abs\CompanyPkg\Company;
use Abs\LocationPkg\Country;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use File;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class CompanyController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getCompanyList(Request $request) {
		$companies = Company::withTrashed()
			->select(
				'companies.id',
				'companies.code',
				'companies.name',
				'companies.email',
				'companies.contact_number',
				DB::raw('COALESCE(companies.logo_id,"--") as logo'),
				DB::raw('COALESCE(companies.theme,"--") as theme'),
				'companies.domain',
				'attachments.name as logo_name',
				DB::raw('IF(companies.deleted_at IS NULL,"Active","Inactive") as status')
			)
			->leftJoin('attachments', function ($query) {
				$query->on('companies.logo_id', 'attachments.id')
					->where('attachments.attachment_of_id', 21);
			})
			->where(function ($query) use ($request) {
				if (!empty($request->company_code)) {
					$query->where('companies.code', 'LIKE', '%' . $request->company_code . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->company_name)) {
					$query->where('companies.name', 'LIKE', '%' . $request->company_name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->email)) {
					$query->where('companies.email', 'LIKE', '%' . $request->email . '%');
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->contact_number)) {
					$query->where('companies.contact_number', 'LIKE', '%' . $request->contact_number . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('companies.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('companies.deleted_at');
				}
			})
			->orderby('companies.id', 'desc');

		return Datatables::of($companies)
			->addColumn('name', function ($company) {
				$status = $company->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $company->name;
			})
			->addColumn('logo', function ($company) {
				$company_logo = $delete = asset('public/themes/' . $this->data['theme'] . '/img/company_logo/' . $company->logo_name);
				return '<img src="' . $company_logo . '" alt="' . $company->logo_name . '" style="width:50px;">';
			})
			->addColumn('action', function ($company) {
				$edit = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$edit_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$view_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');

				$action = '';
				if (Entrust::can('edit-company')) {
					$action .= '<a href="#!/company-pkg/company/edit/' . $company->id . '">
						<img src="' . $edit . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $edit_active . '" onmouseout=this.src="' . $edit . '" >
					</a>';
				}
				if (Entrust::can('view-company')) {
					$action .= '<a href="#!/company-pkg/company/view/' . $company->id . '">
						<img src="' . $view . '" alt="View" class="img-responsive" onmouseover=this.src="' . $view_active . '" onmouseout=this.src="' . $view . '" >
					</a>';

				}
				if (Entrust::can('delete-company')) {
					$action .= '<a href="javascript:;" data-toggle="modal" data-target="#delete_company"
					onclick="angular.element(this).scope().deleteCompany(' . $company->id . ')" dusk = "delete-btn" title="Delete">
					<img src="' . $delete . '" alt="Delete" class="img-responsive" onmouseover=this.src="' . $delete_active . '" onmouseout=this.src="' . $delete . '" >
					</a>
					';
				}
				return $action;
			})
			->make(true);
	}

	public function getCompanyFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$company = new Company;
			$address = new Address;
			$attachment = new Attachment;
			$action = 'Add';
		} else {
			$company = Company::withTrashed()->find($id);
			$address = Address::where('id', $company->address_id)->where('entity_id', $id)->first();
			$attachment = Attachment::where('id', $company->logo_id)->first();
			if (!$address) {
				$address = new Address;
			}
			$action = 'Edit';
		}
		$this->data['country_list'] = $country_list = Collect(Country::select('id', 'name')->get())->prepend(['id' => '', 'name' => 'Select Country']);
		$this->data['company'] = $company;
		$this->data['address'] = $address;
		$this->data['attachment'] = $attachment;
		$this->data['theme'];
		$this->data['action'] = $action;

		return response()->json($this->data);
	}

	public function viewCompany(Request $request) {
		$this->data['company'] = Company::withTrashed()->where('id', $request->id)->first();
		$this->data['address'] = Address::with(['country', 'state', 'city'])->where('entity_id', $request->id)->where('address_of_id', 60)->first();
		$this->data['attachment'] = Attachment::where('entity_id', $request->id)->where('attachment_of_id', 21)->first();
		$this->data['theme'];
		$this->data['action'] = 'View';

		return response()->json($this->data);
	}

	public function saveCompany(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'code.required' => 'Company Code is Required',
				'code.max' => 'Company Code Maximum 16 Characters',
				'code.min' => 'Company Code Minimum 2 Characters',
				'code.unique' => 'Company Code is already taken',
				'name.required' => 'Company Name is Required',
				'name.max' => 'Company Name Maximum 64 Characters',
				'name.min' => 'Company Name Minimum 3 Characters',
				'email.required' => 'Email ID is Required',
				'email.max' => 'Email ID Maximum 64 Characters',
				'contact_number.required' => 'Contact Number is Required',
				'contact_number.max' => 'Contact Number Maximum 16 Characters',
				'contact_number.min' => 'Contact Number Minimum 10 Characters',
				'theme.required' => 'Theme Name is Required',
				'theme.max' => 'Theme Name Maximum 64 Characters',
				'domain.required' => 'Domain Name is Required',
				'domain.max' => 'Domain Name Maximum 128 Characters',
				'address_line_1.required' => 'Address Line 1 is Required',
				'address_line_1.max' => 'Address Line 1 Maximum 255 Characters',
				'address_line_1.min' => 'Address Line 1 Minimum 4 Characters',
				'address_line_2.max' => 'Address Line 2 Minimum 255 Characters',
				'state_id.required' => 'State is Required',
				'city_id.required' => 'City is Required',
				'country_id.required' => 'Country is Required',
				'pincode.required' => 'Pincode is Required',
				'pincode.max' => 'Pincode Maximum 6 Characters',
				'pincode.min' => 'Pincode Minimum 6 Characters',
			];
			$validator = Validator::make($request->all(), [
				'code' => [
					'required:true',
					'max:255',
					'min:2',
					'unique:companies,code,' . $request->id . ',id',
				],
				'name' => [
					'required:true',
					'max:64',
					'min:3',
					'unique:companies,code,' . $request->id . ',id',
				],
				'logo_id' => 'mimes:jpeg,jpg,png,gif,ico,bmp,svg|nullable|max:10000',
				'contact_number' => 'required|max:16|min:10',
				'email' => 'required|max:64',
				'theme' => 'required|max:64',
				'domain' => 'required|max:128',
				'address_line_1' => 'required|max:255|min:4',
				'address_line_2' => 'nullable|max:255',
				'state_id' => 'required',
				'city_id' => 'required',
				'country_id' => 'required',
				'pincode' => 'required|min:6|max:6',
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
				$address = Address::where('id', $company->address_id)->where('entity_id', $request->id)->first();
			}
			if ($request->status == 'Inactive') {
				$company->deleted_at = Carbon::now();
				$company->deleted_by_id = Auth::user()->id;
			} else {
				$company->deleted_by_id = NULL;
				$company->deleted_at = NULL;
			}
			$company->fill($request->all());
			$company->save();
			if (!empty($request->logo_id)) {
				if (!File::exists(public_path() . '/themes/' . config('custom.admin_theme') . '/img/company_logo')) {
					File::makeDirectory(public_path() . '/themes/' . config('custom.admin_theme') . '/img/company_logo', 0777, true);
				}

				$attacement = $request->logo_id;
				$remove_previous_attachment = Attachment::where([
					'entity_id' => $request->id,
					'attachment_of_id' => 21,
				])->first();
				if (!empty($remove_previous_attachment)) {
					$remove = $remove_previous_attachment->forceDelete();
					$img_path = public_path() . '/themes/' . config('custom.admin_theme') . '/img/company_logo/' . $remove_previous_attachment->name;
					if (File::exists($img_path)) {
						File::delete($img_path);
					}
				}
				$random_file_name = $company->id . '_company_logo_file_' . rand(0, 1000) . '.';
				$extension = $attacement->getClientOriginalExtension();
				$attacement->move(public_path() . '/themes/' . config('custom.admin_theme') . '/img/company_logo', $random_file_name . $extension);

				$attachment = new Attachment;
				$attachment->company_id = Auth::user()->company_id;
				$attachment->attachment_of_id = 21;
				$attachment->attachment_type_id = 40;
				$attachment->entity_id = $company->id;
				$attachment->name = $random_file_name . $extension;
				$attachment->save();
				$company->logo_id = $attachment->id;
			}
			if (!$address) {
				$address = new Address;
			}
			$address->fill($request->all());
			$address->company_id = Auth::user()->company_id;
			$address->address_of_id = 60;
			$address->name = 'Address';
			$address->contact_person_id = Auth::user()->id;
			$address->entity_id = $company->id;
			$address->save();

			$company->address_id = $address->id;
			$company->save();

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
	public function deleteCompany(Request $request) {
		$delete_status = Company::withTrashed()->where('id', $request->id)->forceDelete();
		if ($delete_status) {
			$address_delete = Address::where('address_of_id', 60)->where('entity_id', $request->id)->forceDelete();
			$attachment_delete = Attachment::where('attachment_of_id', 21)->where('entity_id', $request->id)->forceDelete();
			return response()->json(['success' => true]);
		}
	}
}
