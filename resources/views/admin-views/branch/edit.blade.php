@extends('layouts.admin.app')

@section('title', translate('Update branch'))

@push('css_or_js')
    <style>
        #location_map_div #pac-input{
            height: 40px;
            border: 1px solid #fbc1c1;
            outline: none;
            box-shadow: none;
            top: 7px !important;
            transform: translateX(7px);
            padding-left: 10px;
        }
    </style>
@endpush

@section('content')
    <div class="content container-fluid">
        <!-- Page Header -->
        <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
            <h2 class="h1 mb-0 d-flex align-items-center gap-2">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/branch.png')}}" alt="">
                <span class="page-header-title">
                    {{translate('Update_Branch')}}
                </span>
            </h2>
        </div>
        <!-- End Page Header -->

        <div class="row g-2">
            <div class="col-12">
                <form action="{{route('admin.branch.update', ['id' => $branch['id']])}}" method="post" enctype="multipart/form-data">
                    @csrf
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                <i class="tio-user"></i>
                                {{translate('branch_Information')}}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <label class="input-label"
                                               for="exampleFormControlInput1">{{translate('name')}}</label>
                                        <input value="{{$branch['name']}}" type="text" name="name" class="form-control" maxlength="255"
                                               placeholder="{{translate('New branch')}}" required>
                                    </div>
                                    <div class="form-group">
                                        <label class="input-label" for="">{{translate('address')}}</label>
                                        <input value="{{$branch['address']}}" type="text" name="address" class="form-control" placeholder="" required>
                                    </div>
                                </div>
                                <div class="col-lg-6">
                                    <div class="form-group">
                                        <div class="d-flex align-items-center justify-content-center gap-1">
                                            <label class="mb-0">{{translate('branch_Image')}}</label>
                                            <small class="text-danger">* ( {{translate('ratio 1:1')}} )</small>
                                        </div>
                                        <div class="d-flex justify-content-center mt-4">
                                            <div class="upload-file">
                                                <input type="file" name="image" accept=".jpg, .png, .jpeg, .gif, .bmp, .tif, .tiff|image/*" class="upload-file__input">
                                                <div class="upload-file__img_drag upload-file__img">
                                                    <img width="150"
                                                         onerror="this.src='{{asset('public/assets/admin/img/160x160/img2.jpg')}}'"
                                                         src="{{asset('storage/app/public/branch')}}/{{$branch['image']}}" alt="">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('fax')}}</label>
                                        <input value="{{$branch['fax']}}" type="tel" name="fax" class="form-control"
                                               placeholder="{{translate('Ex: +098538534')}}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('phone')}}</label>
                                        <input value="{{$branch['phone']}}" type="tel" name="phone" class="form-control"
                                               placeholder="{{translate('Ex: +098538534')}}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('Toll Free Number')}}</label>
                                        <input value="{{$branch['tool_free_number']}}" type="tel" name="tool_free_number" class="form-control"
                                               placeholder="{{translate('Ex: +098538534')}}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label d-block">{{translate('branch')}} {{translate('status')}}</label>
                                        <input type="hidden" name="branch_status_present" value="1">
                                        <label class="switcher">
                                            <input class="switcher_input" name="status" value="1" type="checkbox" {{$branch['status'] == 1 ? 'checked' : ''}}>
                                            <span class="switcher_control"></span>
                                        </label>
                                        <small class="text-muted d-block mt-1">{{translate('Close or open only this location.')}}</small>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('location')}} {{translate('id')}}</label>
                                        <input value="{{$branch['square_location_id'] ?? ''}}" type="text" name="square_location_id" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label d-block">Square {{translate('status')}}</label>
                                        <label class="switcher">
                                            <input class="switcher_input" name="square_status" type="checkbox" {{($branch['square_status'] ?? 0) == 1 ? 'checked' : ''}}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('application')}} {{translate('id')}}</label>
                                        <input value="{{$branch['square_application_id'] ?? ''}}" type="text" name="square_application_id" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('access token')}}</label>
                                        <input value="{{$branch['square_access_token'] ?? ''}}" type="text" name="square_access_token" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('environment')}}</label>
                                        <select name="square_environment" class="form-control">
                                            @php($squareEnvironment=$branch['square_environment'] ?? 'sandbox')
                                            <option value="sandbox" {{$squareEnvironment == 'sandbox' ? 'selected' : ''}}>sandbox</option>
                                            <option value="production" {{$squareEnvironment == 'production' ? 'selected' : ''}}>production</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('webhook')}} {{translate('signature')}} {{translate('key')}}</label>
                                        <input value="{{$branch['square_webhook_signature_key'] ?? ''}}" type="text" name="square_webhook_signature_key" class="form-control">
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label d-block">Square OAuth</label>
                                        <a href="{{route('square.oauth.connect', [$branch['id']])}}" class="btn btn-outline-primary btn-sm">
                                            Connect Square OAuth
                                        </a>
                                        @if(!empty($branch['square_merchant_id']))
                                            <div class="mt-2">
                                                <small class="text-success d-block">Connected merchant: {{$branch['square_merchant_id']}}</small>
                                                @if(!empty($branch['square_oauth_token_expires_at']))
                                                    <small class="text-muted d-block">Token expires: {{$branch['square_oauth_token_expires_at']}}</small>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label d-block">Square {{translate('commission')}} {{translate('status')}}</label>
                                        <label class="switcher">
                                            <input class="switcher_input" name="square_commission_status" type="checkbox" {{($branch['square_commission_status'] ?? config('services.square.commission_status', 0)) == 1 ? 'checked' : ''}}>
                                            <span class="switcher_control"></span>
                                        </label>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('commission')}} {{translate('type')}}</label>
                                        @php($squareCommissionType=$branch['square_commission_type'] ?? config('services.square.commission_type', 'percent'))
                                        <select name="square_commission_type" class="form-control">
                                            <option value="percent" {{$squareCommissionType == 'percent' ? 'selected' : ''}}>percent</option>
                                            <option value="fixed" {{$squareCommissionType == 'fixed' ? 'selected' : ''}}>fixed</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">Square {{translate('commission')}} {{translate('value')}}</label>
                                        <input value="{{$branch['square_commission_value'] ?? config('services.square.commission_value', 0)}}" type="number" step="0.01" min="0" name="square_commission_value" class="form-control">
                                        <small class="text-muted">Percent of order or fixed dollar amount.</small>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('email')}}</label>
                                        <input value="{{$branch['email']}}" type="email" name="email" class="form-control" maxlength="255"
                                               placeholder="{{translate('EX : example@example.com')}}" required>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <div class="form-group">
                                        <label class="input-label">{{translate('password')}} <span class="text-danger">{{translate('*(input_if_you_want_to_reset)') }}</span></label>
                                        <input type="text" name="password" class="form-control" placeholder="{{translate('Password')}}" >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                <i class="tio-map"></i>
                                {{translate('Store_Location ')}}
                            </h4>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                       for="latitude">{{ translate('latitude') }}<span
                                                        class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('click_on_the_map_select_your_defaul_location') }}"></span></label>
                                                <input type="number" step="any" id="latitude" name="latitude" class="form-control"
                                                       placeholder="{{ translate('Ex:') }} 23.8118428"
                                                       value="{{ $branch['latitude'] }}" required >
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="form-label text-capitalize"
                                                       for="longitude">{{ translate('longitude') }}<span
                                                        class="form-label-secondary" data-toggle="tooltip" data-placement="right"
                                                        data-original-title="{{ translate('click_on_the_map_select_your_defaul_location') }}"></span></label>
                                                <input type="number" step="any" name="longitude" class="form-control"
                                                       placeholder="{{ translate('Ex:') }} 90.356331" id="longitude"
                                                       value="{{ $branch['longitude'] }}" required>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="input-label">
                                                    <i class="tio-info-outined"
                                                       data-toggle="tooltip"
                                                       data-placement="top"
                                                       title="{{ translate('This value is the radius from your restaurant location, and customer can order food inside  the circle calculated by this radius.') }}"></i>
                                                    {{translate('coverage (km)')}}
                                                </label>
                                                <input type="number" name="coverage" min="1" max="1000" class="form-control" placeholder="{{ translate('Ex : 3') }}" value="{{ $branch['coverage'] }}" required>
                                            </div>
                                        </div>
                                         <div class="col-12">
                                            <div class="form-group mb-0">
                                                <label class="input-label">
                                                   
                                                    {{translate('Locatin URL')}}
                                                </label>
                                                <input type="text" name="location_url" class="form-control" placeholder="{{ translate('Location URL') }}" value="{{ $branch['location_url'] }}" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6" id="location_map_div">
                                    <input id="pac-input" class="controls rounded" data-toggle="tooltip"
                                           data-placement="right"
                                           data-original-title="{{ translate('search_your_location_here') }}"
                                           type="text" placeholder="{{ translate('search_here') }}" />
                                    <div id="location_map_canvas" class="overflow-hidden rounded" style="height: 100%"></div>
                                </div>

                            </div>
                        </div>
                    </div>
                    <div class="card mt-3">
                        <div class="card-header">
                            <h4 class="mb-0 d-flex gap-2 align-items-center">
                                <i class="tio-calender"></i>
                                {{translate('Branch_Opening-Closing_Schedules')}}
                            </h4>
                        </div>
                        @include('admin-views.business-settings.partials._branch_schedule')
                        <div class="btn--container mb-4">
                                <button type="reset" class="btn btn-secondary">{{translate('reset')}}</button>
                                <button type="submit" class="btn btn-primary">{{translate('submit')}}</button>
                            </div>
                    </div>
                </form>
            </div>

        </div>
    </div>

@endsection

@push('script_2')

    <script src="https://maps.googleapis.com/maps/api/js?key={{ \App\Model\BusinessSetting::where('key', 'map_api_key')->first()->value }}&libraries=places&v=3.45.8"></script>

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


        $( document ).ready(function() {
            function initAutocomplete() {
                var myLatLng = {

                    lat: 23.811842872190343,
                    lng: 90.356331
                };
                const map = new google.maps.Map(document.getElementById("location_map_canvas"), {
                    center: {
                        lat: 23.811842872190343,
                        lng: 90.356331
                    },
                    zoom: 13,
                    mapTypeId: "roadmap",
                });

                var marker = new google.maps.Marker({
                    position: myLatLng,
                    map: map,
                });

                marker.setMap(map);
                var geocoder = geocoder = new google.maps.Geocoder();
                google.maps.event.addListener(map, 'click', function(mapsMouseEvent) {
                    var coordinates = JSON.stringify(mapsMouseEvent.latLng.toJSON(), null, 2);
                    var coordinates = JSON.parse(coordinates);
                    var latlng = new google.maps.LatLng(coordinates['lat'], coordinates['lng']);
                    marker.setPosition(latlng);
                    map.panTo(latlng);

                    document.getElementById('latitude').value = coordinates['lat'];
                    document.getElementById('longitude').value = coordinates['lng'];


                    geocoder.geocode({
                        'latLng': latlng
                    }, function(results, status) {
                        if (status == google.maps.GeocoderStatus.OK) {
                            if (results[1]) {
                                document.getElementById('address').innerHtml = results[1].formatted_address;
                            }
                        }
                    });
                });
                // Create the search box and link it to the UI element.
                const input = document.getElementById("pac-input");
                const searchBox = new google.maps.places.SearchBox(input);
                map.controls[google.maps.ControlPosition.TOP_CENTER].push(input);
                // Bias the SearchBox results towards current map's viewport.
                map.addListener("bounds_changed", () => {
                    searchBox.setBounds(map.getBounds());
                });
                let markers = [];
                // Listen for the event fired when the user selects a prediction and retrieve
                // more details for that place.
                searchBox.addListener("places_changed", () => {
                    const places = searchBox.getPlaces();

                    if (places.length == 0) {
                        return;
                    }
                    // Clear out the old markers.
                    markers.forEach((marker) => {
                        marker.setMap(null);
                    });
                    markers = [];
                    // For each place, get the icon, name and location.
                    const bounds = new google.maps.LatLngBounds();
                    places.forEach((place) => {
                        if (!place.geometry || !place.geometry.location) {
                            console.log("Returned place contains no geometry");
                            return;
                        }
                        var mrkr = new google.maps.Marker({
                            map,
                            title: place.name,
                            position: place.geometry.location,
                        });
                        google.maps.event.addListener(mrkr, "click", function(event) {
                            document.getElementById('latitude').value = this.position.lat();
                            document.getElementById('longitude').value = this.position.lng();
                        });

                        markers.push(mrkr);

                        if (place.geometry.viewport) {
                            // Only geocodes have viewport.
                            bounds.union(place.geometry.viewport);
                        } else {
                            bounds.extend(place.geometry.location);
                        }
                    });
                    map.fitBounds(bounds);
                });
            };
            initAutocomplete();
        });


    </script>

@endpush
