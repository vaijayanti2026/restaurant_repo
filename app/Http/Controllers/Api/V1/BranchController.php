<?php
namespace App\Http\Controllers\Api\V1;

use App\CentralLogics\CategoryLogic;
use App\Http\Controllers\Controller;
use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function get_branch_schedule($id)
    {
         try {
        $branch = Branch::find($id);
            return response()->json(['branch_schedule' => $branch->branchTimeSchedule], 200);
        } catch (\Exception $e) {
            return response()->json([], 200);
        }
    }
}
