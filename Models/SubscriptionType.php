<?php

namespace Subscription\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Subscription\Models\SubscriptionType
 *
 * @property int $id
 * @property string $name
 * @property float $amount
 * @property string|null $deleted_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static bool|null forceDelete()
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionType onlyTrashed()
 * @method static bool|null restore()
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\Subscription\Models\SubscriptionType whereUpdatedAt($value)
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionType withTrashed()
 * @method static \Illuminate\Database\Query\Builder|\Subscription\Models\SubscriptionType withoutTrashed()
 * @mixin \Eloquent
 */
class SubscriptionType extends Model
{
    use SoftDeletes;

    protected $table = 'subscription_types';
    protected $fillable = ['name', 'amount'];
    protected $primaryKey = 'id';

}
