@if(config('custom.PKG_DEV'))
    <?php $company_pkg_prefix = '/packages/abs/company-pkg/src';?>
@else
    <?php $company_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	var admin_theme = "{{$theme}}";
    var company_list_template_url = "{{asset($company_pkg_prefix.'/public/themes/'.$theme.'/company-pkg/company/list.html')}}";
    var company_form_template_url = "{{asset($company_pkg_prefix.'/public/themes/'.$theme.'/company-pkg/company/form.html')}}";
    var company_view_template_url = "{{asset($company_pkg_prefix.'/public/themes/'.$theme.'/company-pkg/company/view.html')}}";
</script>
<script type="text/javascript" src="{{asset($company_pkg_prefix.'/public/themes/'.$theme.'/company-pkg/company/controller.js?v=2')}}"></script>
