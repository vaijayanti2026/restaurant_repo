@extends('layouts.admin.app')

@section('title', translate('Update Addon'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/attribute.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Addon_Update')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row g-3">
            <div class="col-12">
                <div class="card card-body">
                    <form action="{{route('admin.addon.update',[$addon['id']])}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @php($data = Helpers::get_business_settings('language'))
                        @php($default_lang = Helpers::get_default_language())

                        @if($data && array_key_exists('code', $data[0]))
                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach($data as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link {{$lang['default'] == true ? 'active':''}}" href="#" id="{{$lang['code']}}-link">{{ Helpers::get_language_name($lang['code']).'('.strtoupper($lang['code']).')'}}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="row">
                                <div class="col-sm-6">
                                    @foreach($data as $lang)
                                        <?php
                                        if(count($addon['translations'])){
                                            $translate = [];
                                            foreach($addon['translations'] as $t)
                                            {
                                                if($t->locale == $lang['code'] && $t->key=="name"){
                                                    $translate[$lang['code']]['name'] = $t->value;
                                                }
                                            }
                                        }
                                        ?>
                                            <div class="form-group {{$lang['default'] == false ? 'd-none':''}} lang_form" id="{{$lang['code']}}-form">
                                                <label class="input-label" for="exampleFormControlInput1">{{translate('name')}} ({{strtoupper($lang['code'])}})</label>
                                                <input type="text" name="name[]"
                                                    class="form-control"
                                                    placeholder="{{ translate('New Addon') }}"
                                                    value="{{$lang['code'] == 'en' ? $addon['name']:($translate[$lang['code']]['name']??'')}}"
                                                    {{$lang['status'] == true ? 'required':''}} maxlength="255"
                                                    @if($lang['status'] == true) oninvalid="document.getElementById('{{$lang['code']}}-link').click()" @endif>
                                            </div>
                                        <input type="hidden" name="lang[]" value="{{$lang['code']}}">
                                    @endforeach
                                    @else
                                        <div class="row">
                                            <div class="col-sm-6">
                                                <div class="form-group lang_form" id="{{$default_lang}}-form">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('name')}} ({{strtoupper($default_lang)}})</label>
                                                    <input type="text" name="name[]" value="{{$addon['name']}}" class="form-control" placeholder="{{translate('New Addon')}}" required maxlength="255">
                                                </div>
                                                <input type="hidden" name="lang[]" value="{{$default_lang}}">
                                                @endif
                                                <input name="position" value="0" style="display: none">
                                            </div>
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('price')}}</label>
                                                    <input type="number" min="0" step="any" name="price"
                                                        value="{{$addon['price']}}" class="form-control"
                                                        placeholder="{{translate('200')}}" required
                                                        oninvalid="document.getElementById('en-link').click()">
                                                </div>
                                            </div>
                                            <!-- shohel adding -->
                                             <div class="col-sm-6 from_part_2">
                                            <div class="form-group">
                                                <div class="d-flex justify-content-center mt-4">
                                                    <div class="card card-body h-100">
                                                        <div class="form-group">
                                                            <label class="font-weight-bold text-dark">{{translate('Icon')}}</label>
                                                            <small class="text-danger">* ( {{translate('ratio 3:3')}} )</small>
                                                            <div class="d-flex justify-content-center mt-4">
                                                                <div class="upload-file">
                                                                    <input type="file" name="icon" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input">
                                                                    <div class="upload-file__img_drag upload-file__img">
                                                                        <img width="176" src="{{asset('storage/app/public/addon')}}/{{$addon['icon']}}" onerror="this.src='{{asset('assets/admin/img/icons/upload_img2.png')}}'" alt="">
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                            <!-- shohel end -->
                                            <div class="col-12 mb-2">
                                                <div class="d-flex justify-content-end gap-3">
                                                    <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                                    <button type="submit" class="btn btn-primary">{{translate('update')}}</button>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection

@push('script_2')
    <script>
        $(".lang_link").click(function(e){
            e.preventDefault();
            $(".lang_link").removeClass('active');
            $(".lang_form").addClass('d-none');
            $(this).addClass('active');

            let form_id = this.id;
            let lang = form_id.split("-")[0];
            console.log(lang);
            $("#"+lang+"-form").removeClass('d-none');
            if(lang == '{{$default_lang}}')
            {
                $(".from_part_2").removeClass('d-none');
            }
            else
            {
                $(".from_part_2").addClass('d-none');
            }
        });
    </script>

@endpush
