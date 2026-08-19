@extends('layouts.admin.app')

@section('title', translate('Add new Badge'))

@push('css_or_js')

@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/attribute.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Add New Badge')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->


        <div class="row g-3">
            <div class="col-12">
                <div class="mt-3">
                    <div class="card">
                        <div class="card-top px-card pt-4">
                            <div class="d-flex flex-column flex-md-row flex-wrap gap-3 justify-content-md-between align-items-md-center">
                                <h5 class="d-flex align-items-center gap-2">
                                    {{translate('Badge Table')}}
                                    <span class="badge badge-soft-dark rounded-50 fz-12">{{ $badges->count() }}</span>
                                </h5>

                                <div class="d-flex flex-wrap justify-content-md-end gap-3">
                                    <form action="{{url()->current()}}" method="GET">
                                        <div class="input-group">
                                            <input id="datatableSearch_" type="search" name="search" class="form-control" placeholder="{{translate('Search by Badge name')}}" aria-label="Search" value="{{$search}}" required="" autocomplete="off">
                                            <div class="input-group-append">
                                                <button type="submit" class="btn btn-primary"> {{translate('Search')}}</button>
                                            </div>
                                        </div>
                                    </form>
                                    <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#adAddondModal">
                                        <i class="tio-add"></i>
                                        {{translate('Add Badge')}}
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="py-4">
                            <div class="table-responsive datatable-custom">
                                <table class="table table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>{{translate('SL')}}</th>
                                            <th>{{translate('Title')}}</th>
                                            <th>{{translate('Icon')}}</th>
                                            <th class="text-center">{{translate('action')}}</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        @php($i=0)
                                    @foreach($badges as $key=>$badge)
                                        <tr>
                                            <td>{{++$i}}</td>
                                            <td>
                                                <div>
                                                    {{$badge->title}}
                                                </div>
                                            </td>
                                            <td><img src="{{$badge->icon}}" style="width:40px"/></td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a class="btn btn-outline-info btn-sm edit square-btn"
                                                        href="{{route('admin.badge.edit',[$badge->id])}}"><i class="tio-edit"></i></a>
                                                    <button class="btn btn-outline-danger btn-sm delete square-btn" type="button"
                                                        onclick="form_alert('addon-{{$badge->id}}','{{translate('Want to delete this addon')}} ?')"><i class="tio-delete"></i></button>
                                                </div>
                                                <form action="{{route('admin.badge.delete',[$badge->id])}}"
                                                        method="post" id="addon-{{$badge->id}}">
                                                    @csrf @method('delete')
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-responsive mt-4 px-3">
                                <div class="d-flex justify-content-lg-end">
                                    <!-- Pagination -->
                                    {!! $badges->links() !!}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div class="modal fade" id="adAddondModal" tabindex="-1" role="dialog" aria-labelledby="adAddondModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-body">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <form  method="post" id="badgeCreateForm" onsubmit="addNewBadge()">
                            @csrf
                            @php($data = Helpers::get_business_settings('language'))
                            @php($default_lang = Helpers::get_default_language())

                           
                            <ul class="nav nav-tabs w-fit-content mb-4">
                                @foreach ($data as $lang)
                                    <li class="nav-item">
                                        <a class="nav-link lang_link {{ $lang['default'] == true ? 'active' : '' }}" href="#"
                                        id="{{ $lang['code'] }}-link">{{ Helpers::get_language_name($lang['code']) . '(' . strtoupper($lang['code']) . ')' }}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <div class="row">
                                <div class="col-sm-12">
                                   
                                  
                                    <div class="row">
                                        
                                         <div class="col-sm-6 from_part_2 mb-4">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('Title')}}</label>
                                            <input type="text" name="title" placeholder="Title" class="form-control"
                                                 required
                                                ">
                                        </div>
                                        <div class="col-sm-6 from_part_2 mb-4">
                                            <label class="input-label" for="exampleFormControlInput1">{{translate('Icon')}}</label>
                                            <input type="file" name="icon" class="form-control"
                                                 required
                                                ">
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex justify-content-end gap-3">
                                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                                <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
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
        function addNewBadge(){
            event.preventDefault()
            $('#badgeCreateForm .form-control').each(function(){
                $(this).addClass('is-invalid')
                $(this).next('p').remove()
            })
            $("#badgeCreateForm button[type='submit']").attr('disabled',true);
            $("#badgeCreateForm button[type='submit']").html('Please Wait...');
            var formData = new FormData();
            var title = $('#badgeCreateForm input[name="title"]').val();
            var file = $('#badgeCreateForm input[name="icon"]')[0].files[0];
            if (!file || !title) {
                console.error("File or title is missing.");
                return;
            }
            formData.append('_token', "{{csrf_token()}}");
            formData.append('title', title);
            formData.append('icon', file);
            $.ajax({
                url: "{{route('admin.badge.store')}}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    $("#badgeCreateForm button[type='submit']").attr('disabled',false);
                    $("#badgeCreateForm button[type='submit']").html('Submit');
                     const Toast = Swal.mixin({
                                      toast: true,
                                      position: "bottom-end",
                                      showConfirmButton: false,
                                      timer: 3000,
                                      timerProgressBar: true,
                                      didOpen: (toast) => {
                                        toast.onmouseenter = Swal.stopTimer;
                                        toast.onmouseleave = Swal.resumeTimer;
                                      }
                                    });
                                    if(response.status==true){
                                        Toast.fire({
                                          icon: "success",
                                          title: response.message
                                        });
                                        setTimeout(function() {location.reload()},3000)
                                    }else{
                                        Toast.fire({
                                          icon: "error",
                                          title: response.message
                                        });
                                    }
                                    
                },
                error: function(error) {
                    $("#badgeCreateForm button[type='submit']").attr('disabled',false);
                    $("#badgeCreateForm button[type='submit']").html('Submit');
                    if(error.status==422){
                        const errors=error.responseJSON.errors
                        $('#badgeCreateForm .form-control').each(function(){
                            var name=$(this).attr('name')
                            if(errors[name]){
                                $(this).addClass('is-invalid')
                                $(this).after('<p class="text-danger">'+errors[name]+'</p>')
                            }
                        })
                    }else{
                        const Toast = Swal.mixin({
                                      toast: true,
                                      position: "bottom-end",
                                      showConfirmButton: false,
                                      timer: 3000,
                                      timerProgressBar: true,
                                      didOpen: (toast) => {
                                        toast.onmouseenter = Swal.stopTimer;
                                        toast.onmouseleave = Swal.resumeTimer;
                                      }
                                    });
                                    Toast.fire({
                                      icon: "success",
                                      title: "An error occured please try again"
                                    });
                    }
                    // console.error('Error adding badge:', textStatus, errorThrown);
                }
            });
        }
    </script>

@endpush
