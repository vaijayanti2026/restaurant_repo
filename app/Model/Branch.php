<?php

namespace App\Model;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Branch extends Authenticatable
{
    use Notifiable;
    
    protected $fillable=['restaurant_id','name','name','email','password','latitude','longitude','address','status','branch_promotion_status','coverage','image','phone','tool_free_number','fax','square_location_id','square_status','square_application_id','square_access_token','square_environment','square_webhook_signature_key','square_commission_status','square_commission_type','square_commission_value'];
    protected $casts = [
        'coverage' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
    protected $hidden = ['password', 'remember_token', 'square_access_token', 'square_webhook_signature_key'];

    public function branch_promotion(){
        return $this->hasMany(BranchPromotion::class);
    }

    public function table(){
        return $this->hasMany(Table::class, 'branch_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
    
    public function branchTimeSchedule()
    {
        return $this->hasMany(BranchTimeSchedule::class);
    }

}
