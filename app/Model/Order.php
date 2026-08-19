<?php

namespace App\Model;

use App\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $appends = [
        'preparation_time_minutes',
        'estimated_completion_at',
        'estimated_ready_at',
    ];

    protected $casts = [
        'order_amount' => 'float',
        'coupon_discount_amount' => 'float',
        'total_tax_amount' => 'float',
        'delivery_address_id' => 'integer',
        'delivery_man_id' => 'integer',
        'delivery_charge' => 'float',
        'user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'delivery_address' => 'array'
    ];

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function delivery_man()
    {
        return $this->belongsTo(DeliveryMan::class, 'delivery_man_id')->withCount('orders');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'user_id')->withCount('orders');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id')->withCount('orders');
    }

    public function delivery_address()
    {
        return $this->belongsTo(CustomerAddress::class, 'delivery_address_id');
    }

    public function table_order()
    {
        return $this->belongsTo(TableOrder::class, 'table_order_id', 'id');
    }

    public function table()
    {
        return $this->belongsTo(Table::class, 'table_id', 'id');
    }

    public function scopePos($query)
    {
        return $query->where('order_type', '=' , 'pos');
    }

    public function scopeDineIn($query)
    {
        return $query->where('order_type', '=' , 'dine_in');
    }


    public function scopeNotDineIn($query)
    {
        return $query->where('order_type', '!=' , 'dine_in');
    }

    public function scopeNotPos($query)
    {
        return $query->where('order_type', '!=' , 'pos');
    }

    public function scopeSchedule($query)
    {
        return $query->whereDate('delivery_date','>',\Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeNotSchedule($query)
    {
        return $query->whereDate('delivery_date','<=',\Carbon\Carbon::now()->format('Y-m-d'));
    }

    public function scopeEarningReport($query)
    {
        return $query->whereIn('order_status', ['delivered', 'completed']);
    }

    public function getPreparationTimeMinutesAttribute()
    {
        return max(0, (int) ($this->attributes['preparation_time'] ?? 0));
    }

    public function getEstimatedCompletionAtAttribute()
    {
        try {
            $createdAt = $this->created_at ?: ($this->attributes['created_at'] ?? null);
            if (!$createdAt) {
                return null;
            }

            return Carbon::parse($createdAt)
                ->addMinutes($this->preparation_time_minutes)
                ->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function getEstimatedReadyAtAttribute()
    {
        try {
            $completionAt = $this->estimated_completion_at
                ? Carbon::parse($this->estimated_completion_at)
                : null;

            if (empty($this->delivery_date) || empty($this->delivery_time)) {
                return $completionAt ? $completionAt->format('Y-m-d H:i:s') : null;
            }

            $scheduledAt = Carbon::parse($this->delivery_date . ' ' . $this->delivery_time);

            if ($completionAt && $completionAt->greaterThan($scheduledAt)) {
                return $completionAt->format('Y-m-d H:i:s');
            }

            return $scheduledAt->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return $this->estimated_completion_at;
        }
    }
}
