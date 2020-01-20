@if(config('custom.PKG_DEV'))
    <?php $company_pkg_prefix = '/packages/abs/company-pkg/src';?>
@else
    <?php $company_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var company_list_template_url = "{{URL::asset($company_pkg_prefix.'/public/angular/company-pkg/pages/company/list.html')}}";
    var company_get_form_data_url = "{{url('company-pkg/company/get-form-data/')}}";
    var company_form_template_url = "{{URL::asset($company_pkg_prefix.'/public/angular/company-pkg/pages/company/form.html')}}";
    var company_delete_data_url = "{{url('company-pkg/company/delete/')}}";
</script>
<script type="text/javascript" src="{{URL::asset($company_pkg_prefix.'/public/angular/company-pkg/pages/company/controller.js?v=2')}}"></script>
