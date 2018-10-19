<?php

namespace Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Subscription\Models\SubscriptionCoupon
 *
 * @property int $id
 * @property string $expire_at
 * @property int $user_id
 * @property string|null $type
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionCoupon onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereExpireAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereUserId($value)
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionCoupon withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionCoupon withoutTrashed()
 * @mixin \Eloquent
 * @property string $name
 * @property int $discount
 * @property string $from
 * @property string $to
 * @property string $period
 * @property int $frequency
 * @property int $cycles
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereCycles($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereDiscount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereFrequency($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereFrom($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon wherePeriod($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionCoupon whereTo($value)
 */
class SubscriptionCoupon extends Model
{
    use SoftDeletes;

    protected $table = 'subscription_coupons';
    protected $fillable = ['name', 'discount', 'period', 'frequency', 'cycles', 'from', 'to'];
    protected $primaryKey = 'id';



}
