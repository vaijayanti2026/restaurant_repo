<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class RewardTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'type',
        'reward_points',
        'description',
    ];

    public static function getEarnedPoints(int $userId, int $orderId): int
    {
        return static::where('user_id', $userId)
            ->where('order_id', $orderId)
            ->where('type', 'earned')
            ->sum('reward_points');
    }

    public static function getAvailablePoints(int $userId): int
    {
        return static::where('user_id', $userId)
            ->sum('reward_points');
    }
}
