<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Reward;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class RewardController extends Controller
{
    /**
     * Display reward page.
     */
    public function index()
    {
        return view('admin-views.reward.index');
    }

    /**
     * Display reward list.
     */
    public function list(Request $request)
    {
        $search = $request->search;

        $queryParam = [
            'search' => $search,
        ];

        $rewards = Reward::when($search, function ($query) use ($search) {

            $keywords = explode(' ', trim($search));

            $query->where(function ($query) use ($keywords) {

                foreach ($keywords as $keyword) {

                    if ($keyword === '') {
                        continue;
                    }

                    $query->orWhere(
                        'title',
                        'LIKE',
                        '%' . $keyword . '%'
                    );

                    $query->orWhere(
                        'id',
                        'LIKE',
                        '%' . $keyword . '%'
                    );
                }
            });

        })
        ->latest()
        ->paginate(Helpers::getPagination())
        ->appends($queryParam);

        return view(
            'admin-views.reward.list',
            compact('rewards')
        );
    }

    /**
     * Create page.
     */
    public function create()
    {
        //
    }

    /**
     * Store new reward.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'image' => 'required|image',
            'reward_point' => 'required|numeric|min:0',
            'branch_text' => 'nullable|string|max:50',
            'instruction' => 'nullable|string',
            'status' => 'nullable',
        ], [
            'title.max' => translate('Title is too long'),
        ]);

        $reward = new Reward();

        $reward->title = $request->title;
        $reward->reward_point = $request->reward_point;
        $reward->instruction = $request->instruction;
        $reward->branch_txt = $request->branch_text;
        $reward->status = $request->status ?? 0;

        $reward->image = Helpers::upload(
            'reward/',
            'png',
            $request->file('image')
        );

        $reward->save();

        Toastr::success(
            translate('Reward added successfully!')
        );

        return redirect('admin/reward/list');
    }

    /**
     * Display specified reward.
     */
    public function show(Reward $reward)
    {
        //
    }

    /**
     * Edit reward.
     */
    public function edit($id)
    {
        $reward = Reward::findOrFail($id);

        return view(
            'admin-views.reward.edit',
            compact('reward')
        );
    }

    /**
     * Update reward.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'reward_point' => 'required|numeric|min:0',
            'branch_text' => 'nullable|string|max:50',
            'instruction' => 'nullable|string',
            'image' => 'nullable|image',
            'status' => 'nullable',
        ], [
            'title.max' => translate('Title is too long'),
        ]);

        $reward = Reward::findOrFail($id);

        $reward->title = $request->title;
        $reward->reward_point = $request->reward_point;
        $reward->instruction = $request->instruction;
        $reward->branch_txt = $request->branch_text;
        $reward->status = $request->status ?? $reward->status;

        if ($request->hasFile('image')) {

            $reward->image = Helpers::update(
                'reward/',
                $reward->image,
                'png',
                $request->file('image')
            );
        }

        $reward->save();

        Toastr::success(
            translate('Reward updated successfully!')
        );

        return redirect('admin/reward/list');
    }

    /**
     * Change reward status.
     */
    public function status(Request $request)
    {
        $request->validate([
            'id' => 'required',
            'status' => 'required',
        ]);

        $reward = Reward::findOrFail($request->id);

        $reward->status = $request->status;

        $reward->save();

        Toastr::success(
            translate('Reward status updated!')
        );

        return back();
    }

    /**
     * Delete reward.
     */
    public function delete(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $reward = Reward::findOrFail($request->id);

        if (!empty($reward->image)) {

            // FIXED:
            // Previous code incorrectly used banner/
            Helpers::delete(
                'reward/' . $reward->image
            );
        }

        $reward->delete();

        Toastr::success(
            translate('Reward removed!')
        );

        return back();
    }
}