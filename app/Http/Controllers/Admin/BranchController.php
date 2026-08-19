<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\TimeSchedule;
use App\Model\BranchTimeSchedule;
use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class BranchController extends Controller
{
    public function index()
    {
        $days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    0 => 'Sunday',
    ];
        return view('admin-views.branch.index', compact('days'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|max:255|unique:branches',
            'email' => 'required|max:255|unique:branches',
            'password' => 'required|min:8|max:255',
            'image' => 'required|max:255',
            'tool_free_number'=>'required',
            'square_commission_type' => 'nullable|in:percent,fixed',
            'square_commission_value' => 'nullable|numeric|min:0',
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        //image upload
        if (!empty($request->file('image'))) {
            $image_name = Helpers::upload('branch/', 'png', $request->file('image'));
        } else {
            $image_name = 'def.png';
        }

        $branch = new Branch();
        $branch->name = $request->name;
        $branch->email = $request->email;
        $branch->longitude = $request->longitude;
        $branch->latitude = $request->latitude;
        $branch->coverage = $request->coverage ? $request->coverage : 0;
        $branch->address = $request->address;
        $branch->password = bcrypt($request->password);
        $branch->image = $image_name;
        $branch->tool_free_number = $request->tool_free_number;
        $branch->fax = $request->fax?? null;
        $branch->phone = $request->phone?? null;
        $this->syncSquareFields($branch, $request);
        $branch->save();
        
        foreach($request->days as $index => $data){
            BranchTimeSchedule::create([
                'branch_id' => $branch->id,
                'day' => $index,
                'opening_time' => $data['start_time'],
                'closing_time' => $data['closing_time'],
                ]);
        }
        Toastr::success(translate('Branch added successfully!'));
        return back();
    }

    public function edit($id)
    {
        $branch = Branch::find($id);
         $days = [
    1 => 'Monday',
    2 => 'Tuesday',
    3 => 'Wednesday',
    4 => 'Thursday',
    5 => 'Friday',
    6 => 'Saturday',
    0 => 'Sunday',
    ];
        $schedules = $branch->branchTimeSchedule->keyBy('day');
        // dd($schedules);
        return view('admin-views.branch.edit', compact('branch', 'days', 'schedules'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|max:255',
            'location_url'=>'required',
            'email' => ['required', 'unique:branches,email,'.$id.',id']
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        $request->validate([
            'name' => 'required',
            'email' => 'required',
            'square_commission_type' => 'nullable|in:percent,fixed',
            'square_commission_value' => 'nullable|numeric|min:0',
        ], [
            'name.required' => translate('Name is required!'),
        ]);

        $branch = Branch::find($id);
        if ($request->has('branch_status_present')) {
            $branch->status = $request->has('status') ? 1 : 0;
        }
        $branch->name = $request->name;
        $branch->location_url=$request->location_url;
        $branch->email = $request->email;
        $branch->longitude = $request->longitude;
        $branch->latitude = $request->latitude;
        $branch->coverage = $request->coverage ? $request->coverage : 0;
        $branch->address = $request->address;
        $branch->tool_free_number = $request->tool_free_number;
        $branch->image = $request->has('image') ? Helpers::update('branch/', $branch->image, 'png', $request->file('image')) : $branch->image;
        if ($request['password'] != null) {
            $branch->password = bcrypt($request->password);
        }
        $branch->phone = $request->phone?? '';
        $branch->fax = $request->fax?? '';
        $this->syncSquareFields($branch, $request);

        $branch->save();
        
         foreach($request->days as $index => $data){
            BranchTimeSchedule::updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'day' => $index,
                ],
                [
                    'opening_time' => $data['start_time'] ?? null,
                    'closing_time' => $data['closing_time'] ?? null,
                ]
            );
        }
        Toastr::success(translate('Branch updated successfully!'));
        return back();
    }

    public function delete(Request $request)
    {
        $branch = Branch::find($request->id);
        $branch->delete();
        Toastr::success(translate('Branch removed!'));
        return back();
    }

    public function status(Request $request)
    {
        $branch = Branch::find($request->id);
        $branch->status = $request->status;
        $branch->save();

        Toastr::success(translate('Branch status updated!'));
        return back();
    }

    public function list(Request $request)
    {
        $query_param = [];
        $search = $request['search'];
        $query = Branch::when($search, function ($q) use ($search) {
            $key = explode(' ', $search);
            foreach ($key as $value) {
                $q->orWhere('id', 'like', "%{$value}%")
                    ->orWhere('name', 'like', "%{$value}%");
            }
        });
        $query_param = ['search' => $request['search']];
        $branches = $query->orderBy('id', 'DESC')->paginate(Helpers::getPagination())->appends($query_param);

        return view('admin-views.branch.list', compact('branches', 'search'));
    }

    private function syncSquareFields(Branch $branch, Request $request)
    {
        $commissionType = in_array($request->square_commission_type, ['percent', 'fixed']) ? $request->square_commission_type : 'percent';
        $commissionValue = $request->square_commission_value !== null && $request->square_commission_value !== ''
            ? max(0, (float) $request->square_commission_value)
            : null;

        if ($commissionType === 'percent' && $commissionValue !== null) {
            $commissionValue = min($commissionValue, 90);
        }

        $fields = [
            'square_location_id' => $request->square_location_id ?: null,
            'square_status' => $request->square_status == 'on' ? 1 : 0,
            'square_application_id' => $request->square_application_id ?: null,
            'square_access_token' => $request->square_access_token ?: null,
            'square_environment' => $request->square_environment ?: 'sandbox',
            'square_webhook_signature_key' => $request->square_webhook_signature_key ?: null,
            'square_commission_status' => $request->square_commission_status == 'on' ? 1 : 0,
            'square_commission_type' => $commissionType,
            'square_commission_value' => $commissionValue,
        ];

        foreach ($fields as $column => $value) {
            if (Schema::hasColumn('branches', $column)) {
                $branch->{$column} = $value;
            }
        }
    }
}
