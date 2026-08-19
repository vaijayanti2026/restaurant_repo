@extends('layouts.admin.app')

@section('title', translate('Update Review'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/attribute.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Review Update')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row g-3">
            <div class="col-12">
                <div class="card card-body">
                    <form action="{{route('admin.reviews.update',[$review['id']])}}" method="post" enctype="multipart/form-data">
                        @csrf
                        @php($data = Helpers::get_business_settings('language'))
                        @php($default_lang = Helpers::get_default_language())

                          
                            <div class="row">
                                <div class="col-sm-6">
                                   
                                  
                                        <div class="row">
                                           
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Branch Name')}}</label>
                                                    <input type="text" name="branch_name" class="form-control" value="{{$review->branch_name}}"/>
                                                    
                                                </div>
                                            </div>
                                            
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Name')}}</label>
                                                    <input type="text" name="name" class="form-control" value="{{$review->name}}"/>
                                                    
                                                </div>
                                            </div>
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Comment')}}</label>
                                                    <textarea  name="comment" class="form-control" >{{$review->comment}}</textarea>
                                                    
                                                </div>
                                            </div>
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Ratting')}}</label>
                                                    <input type="text" name="ratting" class="form-control" value="{{$review->ratting}}"/>
                                                    
                                                </div>
                                            </div>
                                            <div class="col-sm-6 from_part_2">
                                                <div class="form-group">
                                                    <label class="input-label" for="exampleFormControlInput1">{{translate('Attachment')}}</label>
                                                    <input type="file" name="attachment" class="form-control"/>
                                                    
                                                </div>
                                            </div>
                                            <div class="col-12 mb-2">
                                                <div class="d-flex justify-content-end gap-3">
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
