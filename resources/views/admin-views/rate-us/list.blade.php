@extends('layouts.admin.app')

@section('title', translate('Rate Us list'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('assets/admin/img/icons/banner.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Rate_Us_Setup')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="content">
            <!-- Page Header -->
            <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
                <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                    <span class="page-header-title">
                    {{translate('Add_New_Rate_Us')}}
                </span>
                </h2>
            </div>
            <!-- End Page Header -->


            <div class="row g-2">
                <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                    <form action="{{route('admin.rate-us.store')}}" method="post" enctype="multipart/form-data">
                        @csrf
                        <div class="card">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-lg-6">

                                        <div class="form-group">
                                            <label class="input-label">{{translate('Enable Rate Us Popup')}}<span
                                                    class="input-label-secondary">*</span></label>
                                            <select name="status" class="custom-select" required>
                                                <option value="" selected disabled>{{translate('select_status')}}</option>
                                                <option value="0">{{translate('Enabled')}}</option>
                                                <option value="1">{{translate('Disabled')}}</option>
                                            </select>
                                        </div>
                                          <div class="form-group">
                                            <label class="input-label">{{translate('Select Time Interval')}}<span
                                                    class="input-label-secondary">*</span></label>
                                            <select name="time_interval" class="custom-select" required>
                                                <option value="" selected disabled>{{translate('select_option')}}</option>
                                                <option value="Every 24 Hours">{{translate('Every 24 Hours')}}</option>
                                                <option value="Every Week">{{translate('Every Week')}}</option>
                                            </select>
                                        </div>


                                        <div class="form-group">
                                            <label class="input-label">{{translate('Start Date')}} <span
                                                    class="input-label-secondary">*</span></label>
                                            <input type="date" name="start_date" class="form-control" placeholder="{{translate('Enter Start Date')}}" required>
                                        </div>
                                       
                                    </div>
                                   
                                </div>

                                <div class="d-flex justify-content-end gap-3 mt-4">
                                    <button type="reset" id="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                    <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="row g-2">
            <div class="col-12">
                <!-- Card -->
                <div class="card">
                    <div class="card-top px-card pt-4">
                        <div class="row align-items-center gy-2">
                            <div class="col-sm-4 col-md-6 col-lg-8">
                                <h5 class="d-flex align-items-center gap-2 mb-0">
                                    {{translate('Rate_Us_List')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $rate_us->count() }}</span>
                                </h5>
                            </div>
                          
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="py-4">
                        <div class="table-responsive datatable-custom">
                            <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                <thead class="thead-light">
                                <tr>
                                    <th>{{translate('SL')}}</th>
                                    <th>{{translate('Enabled')}}</th>
                                    <th>{{translate('Time Interval')}}</th>
                                    <th>{{translate('Start Date')}}</th>
                                    <th class="text-center">{{translate('action')}}</th>
                                </tr>
                                </thead>

                                <tbody>
                                @foreach($rate_us as $key=>$rate_us_s)
                                    <tr>
                                        <td>{{$key+1}}</td>
                                        
                                        <td>
                                            <label class="switcher">
                                                <input class="switcher_input" type="checkbox" {{$rate_us_s['status']==1 ? 'checked' : ''}} id="{{$rate_us_s['id']}}"
                                                    data-url="{{route('admin.rate-us.status',[$rate_us_s['id'],0])}}" onchange="status_change(this)"
                                                >
                                                <span class="switcher_control"></span>
                                            </label>
                                        </td>
                                        <td>
                                            {{$rate_us_s->time_interval}}
                                        </td>
                                        <td>
                                            {{ \Carbon\Carbon::parse($rate_us_s['start_date'])->format('Y-m-d') }}
                                        </td>
                                        <td>
                                            <div class="d-flex justify-content-center gap-2">
                                                <a class="btn btn-outline-info btn-sm edit square-btn"
                                                    href="{{route('admin.rate-us.edit',[$rate_us_s['id']])}}"><i class="tio-edit"></i></a>
                                                <button type="button" class="btn btn-outline-danger btn-sm delete square-btn"
                                                    onclick="form_alert('RateUs-{{$rate_us_s['id']}}','{{translate('Want to delete this Rate Us')}}')"><i class="tio-delete"></i></button>
                                            </div>
                                            <form action="{{route('admin.rate-us.delete',[$rate_us_s['id']])}}"
                                                method="post" id="RateUs-{{$rate_us_s['id']}}">
                                                @csrf @method('delete')
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>

                        
                    </div>
                    <!-- End Table -->
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(document).on('ready', function () {
            $('.js-select2-custom').each(function () {
                var select2 = $.HSCore.components.HSSelect2.init($(this));
            });
        });
    </script>

    <script>
        function readURL(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();

                reader.onload = function (e) {
                    $('#viewer').attr('src', e.target.result);
                }

                reader.readAsDataURL(input.files[0]);
            }
        }

        $("#customFileEg1").change(function () {
            readURL(this);
        });


        function show_item(type) {
            if (type === 'product') {
                $("#type-product").show();
                $("#type-category").hide();
            } else {
                $("#type-product").hide();
                $("#type-category").show();
            }
        }
    </script>

    <script>
        $(".js-select2-custom").select2({
            placeholder: "Select a state",
            allowClear: true
        });
    </script>
@endpush
