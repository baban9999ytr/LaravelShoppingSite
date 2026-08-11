<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ShoppingOrder extends Model
{
    protected $fillable = [
        'user_id',
        'origin_city',
        'destination_city',
        'ordered_at',
        'estimated_arrival_at',
        'estimated_arrived_at',
        'items',
        'did_arrive',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'estimated_arrival_at' => 'datetime',
        'estimated_arrived_at' => 'datetime',
        'did_arrive' => 'boolean',
        'items' => 'array',
    ];

    public function getRefundDeadlineAttribute(): CarbonImmutable
    {
        return $this->estimated_arrival_at->addDays(15);
    }

    public function getIsDeliveredAttribute(): bool
    {
        return $this->did_arrive || now()->greaterThanOrEqualTo($this->estimated_arrival_at);
    }

    public function getIsRefundableAttribute(): bool
    {
        $now = now();
        
        return $this->is_delivered && $now->lessThanOrEqualTo($this->refund_deadline);
    }

    public function getRemainingRefundTimeAttribute(): string
    {
        if (!$this->is_delivered) {
            return 'Sipariş henüz teslim edilmedi.';
        }

        if (!$this->is_refundable) {
            return 'İade süresi doldu.';
        }

        return now()->diffForHumans($this->refund_deadline, [
            'parts' => 2,
            'syntax' => 1,
        ]);
    }

    public function scopeRefundable(Builder $query): Builder
    {
        $now = now();
        return $query->where('estimated_arrival_at', '<=', $now)
                     ->where('estimated_arrival_at', '>=', $now->copy()->subDays(15));
    }
}