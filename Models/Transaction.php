<?php

namespace Subscription\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Subscription\Models\Transaction
 *
 * @property int $id
 * @property int $subscription_id
 * @property string $transaction_id
 * @property string $driver
 * @property string $plan
 * @property float $amount
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereDriver($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction wherePlan($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereSubscriptionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereTransactionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereUpdatedAt($value)
 * @mixin \Eloquent
 * @property string $status
 * @property string|null $payer_id
 * @property string|null $message
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereMessage($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction wherePayerId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\Transaction whereStatus($value)
 */
class Transaction extends Model
{
    protected $table = 'transactions';
    protected $fillable = [
        'subscription_id',
        'amount',
        'status',
        'payer_id',
        'message'
    ];
}
