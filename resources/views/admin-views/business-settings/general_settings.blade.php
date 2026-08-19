@extends('layouts.admin.app')

@section('title', translate('Settings'))

@push('css_or_js')
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
{{--                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/product.png')}}" alt="">--}}
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/app.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('system_setup')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <!-- Inline Menu -->
        @include('admin-views.business-settings.partials._system-settings-inline-menu')

        <div class="row g-2">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header justify-content-center">
                        <h4 class="mb-0">{{translate('Fax Settings')}}</h4>
                    </div>
                    <div class="card-body">
                        @php($config=\App\CentralLogics\Helpers::get_business_settings('fax_settings'))
                        
                       
                        <form
                            action="{{env('APP_MODE')!='demo'?route('admin.business-settings.web-app.system-setup.app_setting',['platform' => 'fax_settings']):'javascript:'}}"
                            method="post">
                            @csrf
                           <div class="form-group">
                               <label>Fax</label>
                               <select name="status" class="form-control">
                                   <option {{isset($config['status']) && $config['status']=='1'?'selected':''}} value="1">Enable</option>
                                   <option {{isset($config['status']) && $config['status']=='0'?'selected':''}} value="0">Disable</option>
                               </select>
                           </div>

                            <div class="btn--container">
                                
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}"
                                        class="btn btn-primary">{{translate('save')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
             <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header justify-content-center">
                        <h4 class="mb-0">{{translate('Branch Call Notification Settings')}}</h4>
                    </div>
                    <div class="card-body">
                        @php($config=\App\CentralLogics\Helpers::get_business_settings('branch_call_notification_settings'))
                        
                       
                        <form
                            action="{{env('APP_MODE')!='demo'?route('admin.business-settings.web-app.system-setup.app_setting',['platform' => 'branch_call_notification_settings']):'javascript:'}}"
                            method="post">
                            @csrf
                           <div class="form-group">
                               <label>Notification</label>
                               <select name="status" class="form-control">
                                   <option {{isset($config['status']) && $config['status']=='1'?'selected':''}} value="1">Enable</option>
                                   <option {{isset($config['status']) && $config['status']=='0'?'selected':''}} value="0">Disable</option>
                               </select>
                           </div>

                            <div class="btn--container">
                                
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}"
                                        class="btn btn-primary">{{translate('save')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
             <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header justify-content-center">
                        <h4 class="mb-0">{{translate('Orders Menu')}}</h4>
                    </div>
                    <div class="card-body">
                        @php($config=\App\CentralLogics\Helpers::get_business_settings('customize_order_menu'))
                        
                       
                        <form
                            action="{{env('APP_MODE')!='demo'?route('admin.business-settings.web-app.system-setup.app_setting',['platform' => 'customize_order_menu_update']):'javascript:'}}"
                            method="post">
                            @csrf
                           <div class="form-group">
                               <label>Order Menu</label>
                               <select name="status" class="form-control">
                                   <option {{isset($config['status']) && $config['status']=='1'?'selected':''}} value="1">Enable</option>
                                   <option {{isset($config['status']) && $config['status']=='0'?'selected':''}} value="0">Disable</option>
                               </select>
                           </div>

                            <div class="btn--container">
                                
                                <button type="{{env('APP_MODE')!='demo'?'submit':'button'}}"
                                        onclick="{{env('APP_MODE')!='demo'?'':'call_demo()'}}"
                                        class="btn btn-primary">{{translate('save')}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
           
            </div>
        </div>


    </div>
@endsection

@push('script_2')

@endpush
