@extends('layouts.admin.app')

@section('title', translate('All Orders'))

@section('content')
    <div class="content container-fluid">
        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
            <h2 class="h1 mb-0 d-flex align-items-center gap-1">
                <img width="20" class="avatar-img" src="{{asset('public/assets/admin/img/icons/all_orders.png')}}" alt="">
                <span class="page-header-title">{{translate('All Orders')}}</span>
            </h2>
            <span class="badge badge-soft-dark rounded-50 fz-14">{{ $orders->total() }}</span>
        </div>

        <div class="card mb-3"><div class="card-body">
            <form method="GET" action="{{ route('admin.orders.all-orders') }}">
                <div class="row gy-3 gx-2 align-items-end">
                    <div class="col-md-3"><label>{{translate('Search')}}</label><input type="search" class="form-control" name="search" value="{{ $search }}" placeholder="{{translate('Order ID or status')}}"></div>
                    <div class="col-md-3"><label>{{translate('Select_Branch')}}</label><select class="form-control" name="branch"><option value="">{{translate('all')}} {{translate('branch')}}</option>@foreach($branches as $branch)<option value="{{ $branch->id }}" {{ (string) $branchId === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>@endforeach</select></div>
                    <div class="col-md-2"><label>{{translate('Start Date')}}</label><input type="date" class="form-control" name="from" value="{{ $from }}"></div>
                    <div class="col-md-2"><label>{{translate('End Date')}}</label><input type="date" class="form-control" name="to" value="{{ $to }}"></div>
                    <div class="col-md-2 d-flex gap-2"><a href="{{ route('admin.orders.all-orders') }}" class="btn btn-secondary flex-grow-1">{{translate('Clear')}}</a><button type="submit" class="btn btn-primary flex-grow-1">{{translate('Show Data')}}</button></div>
                </div>
            </form>
        </div></div>

        <div class="card"><div class="table-responsive">
            <table class="table table-hover table-borderless table-thead-bordered table-nowrap table-align-middle card-table">
                <thead class="thead-light"><tr><th>{{translate('SL')}}</th><th>{{translate('Order ID')}}</th><th>{{translate('Date')}}</th><th>{{translate('Customer')}}</th><th>{{translate('Branch')}}</th><th>{{translate('Amount')}}</th><th>{{translate('Status')}}</th><th>{{translate('Action')}}</th></tr></thead>
                <tbody>
                @forelse($orders as $key => $order)
                    <tr>
                        <td>{{ (($orders->currentPage() - 1) * $orders->perPage()) + $key + 1 }}</td>
                        <td><a href="{{route('admin.orders.details', ['id' => $order->id])}}">{{ $order->id }}</a></td>
                        <td>{{ date('d M Y', strtotime($order->created_at)) }}</td>
                       <td>@if($order->customer)<a class="text-body text-capitalize" href="{{route('admin.customer.view', [$order->user_id])}}">{{ $order->customer->f_name }} {{ $order->customer->l_name }}</a>@else<label class="badge badge-soft-info">{{translate('Walking customer')}}</label>@endif</td>
                        <td><label class="badge badge-soft-primary">{{ $order->branch ? $order->branch->name : translate('Branch deleted!') }}</label></td>
                        <td>{{ \App\CentralLogics\Helpers::set_symbol($order->invoice_total) }}</td>
                        <td><span class="badge badge-soft-info ml-2 ml-sm-3"><span class="legend-indicator bg-info"></span>{{ translate(str_replace('_', ' ', $order->order_status)) }}</span></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a class="btn btn-outline-secondary btn-sm" href="{{route('admin.orders.details', ['id' => $order->id])}}"><i class="tio-visible"></i> {{translate('view')}}</a>
                                <a class="btn btn-outline-success btn-sm" href="{{route('admin.orders.generate-invoice', [$order->id])}}" target="_blank"><i class="tio-download"></i> {{translate('invoice')}}</a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-4">{{translate('No data found')}}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($orders->hasPages())<div class="card-footer">{{ $orders->links() }}</div>@endif
        </div>
    </div>
@endsection