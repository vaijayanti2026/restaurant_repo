@extends('layouts.admin.app')

@section('title', translate('Update Advertisement'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('assets/admin/img/icons/banner.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Update_Rate_Us')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row gx-2 gx-lg-3">
            <div class="col-sm-12 col-lg-12 mb-3 mb-lg-2">
                <form action="{{route('admin.rate-us.update',[$rate_us['id']])}}" method="post"
                      enctype="multipart/form-data">
                    @csrf @method('put')

                    <div class="card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                            <label class="input-label">{{translate('Enable Rate Us Popup')}}<span
                                                    class="input-label-secondary">*</span></label>
                                            <select name="status" class="custom-select" required>
                                                <option value="" selected disabled>{{translate('select_status')}}</option>
                                                <option value="1" {{$rate_us['status']==1?'selected':''}}>{{translate('Enabled')}}</option>
                                                <option value="2"{{$rate_us['status']==0?'selected':''}}>{{translate('Disabled')}}</option>
                                            </select>
                                        </div>
                                          <div class="form-group">
                                            <label class="input-label">{{translate('Select Time Interval')}}<span
                                                    class="input-label-secondary">*</span></label>
                                            <select name="time_interval" class="custom-select" required>
                                                <option value="" selected disabled>{{translate('select_option')}}</option>
                                                <option value="{{$rate_us['time_interval']}}"{{$rate_us['time_interval']=='Every 24 Hours'?'selected':''}}>{{translate('Every 24 Hours')}}</option>
                                                <option vvalue="{{$rate_us['time_interval']}}"{{$rate_us['time_interval']=='Every Week'?'selected':''}}>{{translate('Every Week')}}</option>
                                            </select>
                                        </div>


                                        <div class="form-group">
                                            <label class="input-label">{{translate('Start Date')}} <span
                                                    class="input-label-secondary">*</span></label>
                                            <input type="date" value="{{ \Carbon\Carbon::parse($rate_us['start_date'])->format('Y-m-d') }}" name="start_date" class="form-control" placeholder="{{translate('Enter Start Date')}}" required>
                                        </div>
                                </div>
                                <div class="col-lg-6">
                                   
                                </div>
                            </div>

                            <div class="d-flex justify-content-end gap-3 mt-4">
                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
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
@endpush
