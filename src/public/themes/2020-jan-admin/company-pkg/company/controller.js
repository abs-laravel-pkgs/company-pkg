app.config(['$routeProvider', function($routeProvider) {
    $routeProvider.
    when('/company-pkg/company/list', {
        template: '<company-list></company-list>',
        title: 'Companies',
    }).
    when('/company-pkg/company/add', {
        template: '<company-form></company-form>',
        title: 'Add Company',
    }).
    when('/company-pkg/company/edit/:id', {
        template: '<company-form></company-form>',
        title: 'Edit Company',
    }).
    when('/company-pkg/company/view/:id', {
        template: '<company-view></company-view>',
        title: 'View Company',
    });
}]);

app.component('companyList', {
    templateUrl: company_list_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope, $location) {
        $scope.loading = true;
        var self = this;
        self.theme = admin_theme;
        self.hasPermission = HelperService.hasPermission;
        var dataTable = $('#company_list').DataTable({
            "dom": dom_structure,
            "language": {
                "search": "",
                "searchPlaceholder": "Search",
                "lengthMenu": "Rows _MENU_",
                "paginate": {
                    "next": '<i class="icon ion-ios-arrow-forward"></i>',
                    "previous": '<i class="icon ion-ios-arrow-back"></i>'
                },
            },
            pageLength: 10,
            processing: true,
            serverSide: true,
            paging: true,
            stateSave: true,
            ordering: false,
            ajax: {
                url: laravel_routes['getCompanyList'],
                type: "GET",
                dataType: "json",
                data: function(d) {
                    d.company_code = $('#company_code').val();
                    d.company_name = $('#company_name').val();
                    d.mobile_no = $('#mobile_no').val();
                    d.email = $('#email').val();
                },
            },

            columns: [
                { data: 'action', class: 'action', name: 'action', searchable: false },
                { data: 'id', name: 'companies.id' },
                { data: 'name', name: 'companies.name' },
                { data: 'code', name: 'companies.code' },
                { data: 'logo', name: 'companies.logo_id', searchable: false },
                { data: 'contact_number', name: 'companies.contact_number' },
                { data: 'email', name: 'companies.email' },
                { data: 'theme', name: 'companies.theme' },
                { data: 'domain', name: 'companies.domain' },
            ],
            "initComplete": function(settings, json) {
                $('.dataTables_length select').select2();
                $('#modal-loading').modal('hide');
            },
            "infoCallback": function(settings, start, end, max, total, pre) {
                $('#table_info').html(total + ' / ' + max)
            },
            rowCallback: function(row, data) {
                $(row).addClass('highlight-row');
            }
        });
        /* Page Title Appended */
        $('.page-header-content .display-inline-block .data-table-title').html('Companies <span class="badge badge-secondary" id="table_info">0</span>');
        $('.page-header-content .search.display-inline-block .add_close_button').html('<button type="button" class="btn btn-img btn-add-close"><img src="' + image_scr2 + '" class="img-responsive"></button>');
        $('.page-header-content .refresh.display-inline-block').html('<button type="button" class="btn btn-refresh"><img src="' + image_scr3 + '" class="img-responsive"></button>');
        if (self.hasPermission('add-company')) {
            // var addnew_block = $('#add_new_wrap').html();
            $('.page-header-content .alignment-right .add_new_button').html(
                '<a href="#!/company-pkg/company/add" role="button" class="btn btn-secondary">Add New</a>'
                // '<a role="button" id="open" data-toggle="modal"  data-target="#modal-company-filter" class="btn btn-img"> <img src="' + image_scr + '" alt="Filter" onmouseover=this.src="' + image_scr1 + '" onmouseout=this.src="' + image_scr + '"></a>'
                // '' + addnew_block + ''
            );
        }
        $('.btn-add-close').on("click", function() {
            $('#company_list').DataTable().search('').draw();
        });

        $('.btn-refresh').on("click", function() {
            $('#company_list').DataTable().ajax.reload();
        });

        //FOCUS ON SEARCH FIELD
        setTimeout(function() {
            $('div.dataTables_filter input').focus();
        }, 2500);
        //DELETE
        $scope.deleteCompany = function($id) {
            $('#company_id').val($id);
        }
        $scope.deleteConfirm = function() {
            $id = $('#company_id').val();
            $http.get(
                laravel_routes['deleteCompany'], {
                    params: {
                        id: $id,
                    }
                }
            ).then(function(response) {
                if (response.data.success) {
                    custom_noty('success', 'Company Deleted Successfully');
                    $('#company_list').DataTable().ajax.reload();
                    $location.path('/company-pkg/company/list');
                }
            });
        }

        //FOR FILTER
        $('#company_code').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#company_name').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#contact_number').on('keyup', function() {
            dataTables.fnFilter();
        });
        $('#email').on('keyup', function() {
            dataTables.fnFilter();
        });
        $scope.reset_filter = function() {
            $("#company_name").val('');
            $("#company_code").val('');
            $("#contact_number").val('');
            $("#email").val('');
            dataTables.fnFilter();
        }

        $rootScope.loading = false;
    }
});
//------------------------------------------------------------------------------------------------------------------------
//------------------------------------------------------------------------------------------------------------------------
app.component('companyForm', {
    templateUrl: company_form_template_url,
    controller: function($http, $location, HelperService, $scope, $routeParams, $rootScope) {
        var self = this;
        self.hasPermission = HelperService.hasPermission;
        self.angular_routes = angular_routes;
        fileUpload();
        $http.get(
            laravel_routes['getCompanyFormData'], {
                params: {
                    id: typeof($routeParams.id) == 'undefined' ? null : $routeParams.id,
                }
            }
        ).then(function(response) {
            console.log(response);
            self.company = response.data.company;
            self.address = response.data.address;
            self.attachment = response.data.attachment;
            self.country_list = response.data.country_list;
            self.theme = response.data.theme;
            self.action = response.data.action;
            $rootScope.loading = false;
            if (self.action == 'Edit') {
                $scope.onSelectedCountry(self.address.country_id);
                $scope.onSelectedState(self.address.state_id);
                if (self.company.deleted_at) {
                    self.switch_value = 'Inactive';
                } else {
                    self.switch_value = 'Active';
                }
                if (self.attachment) {
                    // $scope.SelectFile(self.attachment.name);
                    $('#edited_file_name').val(self.attachment.name);
                } else {
                    $('#edited_file_name').val('');
                }
            } else {
                self.switch_value = 'Active';
                self.state_list = [{ 'id': '', 'name': 'Select State' }];
                self.city_list = [{ 'id': '', 'name': 'Select City' }];
            }
        });

        /* Tab Funtion */
        $('.btn-nxt').on("click", function() {
            $('.editDetails-tabs li.active').next().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-prev').on("click", function() {
            $('.editDetails-tabs li.active').prev().children('a').trigger("click");
            tabPaneFooter();
        });
        $('.btn-pills').on("click", function() {
            tabPaneFooter();
        });
        $scope.btnNxt = function() {}
        $scope.prev = function() {}

        // $scope.SelectFile = function(e) {
        //     console.log(e);
        //     var reader = new FileReader();
        //     reader.onload = function(e) {
        //         $scope.PreviewImage = e.target.result;
        //         $scope.$apply();
        //     };
        //     reader.readAsDataURL(e.target.files[0]);
        // };

        //SELECT STATE BASED COUNTRY
        $scope.onSelectedCountry = function(id) {
            if (id) {
                $http.get(
                    laravel_routes['getStateBasedCountry'], {
                        params: {
                            country_id: id,
                        }
                    }
                ).then(function(response) {
                    // console.log(response);
                    self.state_list = response.data.state_list;
                });
            }
        }

        //SELECT company BASED STATE
        $scope.onSelectedState = function(id) {
            if (id) {
                $http.get(
                    laravel_routes['getCityBasedState'], {
                        params: {
                            state_id: id,
                        }
                    }
                ).then(function(response) {
                    // console.log(response);
                    self.city_list = response.data.city_list;
                });
            }
        }

        var form_id = '#form';
        var v = jQuery(form_id).validate({
            ignore: '',
            errorPlacement: function(error, element) {
                if (element.attr("name") == "logo_id") {
                    error.insertAfter("#attachment_error");
                } else {
                    error.insertAfter(element);
                }
            },
            rules: {
                'code': {
                    required: true,
                    minlength: 2,
                    maxlength: 16,
                },
                'name': {
                    required: true,
                    minlength: 3,
                    maxlength: 64,
                },
                'contact_number': {
                    required: true,
                    number: true,
                    minlength: 10,
                    maxlength: 16,
                },
                'email': {
                    required: true,
                    email: true,
                    minlength: 5,
                    maxlength: 64,
                },
                'logo_id': {
                    required: function() {
                        if (self.action == 'Edit') {
                            if (self.attachment) {
                                return false;
                            } else {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    }
                },
                'theme': {
                    required: true,
                    maxlength: 64,
                },
                'domain': {
                    required: true,
                    maxlength: 128,
                },
                'address_line_1': {
                    required: true,
                    minlength: 3,
                    maxlength: 255,
                },
                'address_line_2': {
                    minlength: 3,
                    maxlength: 255,
                },
                'country_id': {
                    required: true,
                },
                'state_id': {
                    required: true,
                },
                'city_id': {
                    required: true,
                },
                'pincode': {
                    required: true,
                    minlength: 6,
                    maxlength: 6,
                },
            },
            invalidHandler: function(event, validator) {
                custom_noty('error', 'You have errors,Please check all tabs');
            },
            submitHandler: function(form) {
                let formData = new FormData($(form_id)[0]);
                $('.submit').button('loading');
                $.ajax({
                        url: laravel_routes['saveCompany'],
                        method: "POST",
                        data: formData,
                        processData: false,
                        contentType: false,
                    })
                    .done(function(res) {
                        if (res.success == true) {
                            custom_noty('success', res.message);
                            $location.path('/company-pkg/company/list');
                            $scope.$apply();
                        } else {
                            if (!res.success == true) {
                                $('.submit').button('reset');
                                var errors = '';
                                for (var i in res.errors) {
                                    errors += '<li>' + res.errors[i] + '</li>';
                                }
                                custom_noty('error', errors);
                            } else {
                                $('.submit').button('reset');
                                $location.path('/company-pkg/company/list');
                                $scope.$apply();
                            }
                        }
                    })
                    .fail(function(xhr) {
                        $('.submit').button('reset');
                        custom_noty('error', 'Something went wrong at server');
                    });
            }
        });
    }
});
