<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class BranchTimeSchedule extends Model
{
    protected $fillable=['branch_id','day','opening_time','closing_time'];
    
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

}
