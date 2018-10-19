<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 16:44
 */

namespace Subscription;


use Carbon\Carbon;
use FontLib\TrueType\Collection;
use Illuminate\Database\Eloquent\Builder;
use Subscription\Models\Subscription;
use Subscription\Models\SubscriptionCoupon;
use Subscription\Models\SubscriptionType;
use Subscription\Models\Transaction;
use \Illuminate\Database\Eloquent\Collection as EloquentCollection;

/**
 * Class Service
 * @package Subscription
 */
class Service
{
    /**
     * Create new @class Subscription::class row
     *
     * @param array $attributes
     *
     * @return Subscription
     */
    public function createSubscription(array $attributes)
    {
        return config('subscription.subscription_model')::create($attributes);
    }

    /**
     * Get all @class Subscription::class records
     *
     * @param null $callback - query builder callback
     *
     * @return EloquentCollection
     */
    public function getSubscriptions($callback = null) : EloquentCollection
    {
        /** @var Subscription $subscriptions */
        $subscriptions = app(config('subscription.subscription_model'));
        if ($callback) {
            $subscriptions = $subscriptions->where($callback);
        }
        return $subscriptions->get();
    }

    /**
     * Get @class SubscriptionCoupon::class from DB by name satisfies the time frame
     *
     * @param string $name - name-slug of coupon
     *
     * @return null|SubscriptionCoupon
     */
    public function getCoupon(string $name)
    {
        $now = Carbon::now();
        return config('subscription.coupon.model')::whereName($name)
            ->where('from', '<=', $now->toDateString())
            ->where('to', '>=', $now->toDateString())
            ->first();
    }

    /**
     * Get @class Subscription::class from DB.
     * If $fullData has been provided returns nesting object including related
     *      @class SubscriptionType::class
     *      @class SubscriptionCoupon::class
     *
     * @param int  $subscriptionId - application subscription identificator
     * @param bool $fullData - is need to provide related data
     *
     * @return null|Subscription
     */
    public function getSubscriptionById(int $subscriptionId, bool $fullData = false)
    {
        $subscription = config('subscription.subscription_model')::where('id', $subscriptionId);
        if($fullData) {
            /** @var Builder $subscription */
            $subscription = $subscription->with(['subscriptionType', 'subscriptionCoupon']);
        }
        return $subscription->first();
    }

    /**
     * Update @class Subscription::class row in DB
     *
     * @param int $subscriptionId - application subscription identificator
     * @param     $options - array options
     *
     * @return bool - Is successfully updated
     */
    public function updateSubscription(int $subscriptionId, $options) : bool
    {
        return config('subscription.subscription_model')::find($subscriptionId)->update($options);
    }

    /**
     * Get @class SubscriptionType::class row by subscription type identificator
     *
     * @param int $subscriptionTypeId - application subscription type identificator
     *
     * @return null|SubscriptionType
     *
     */
    public function getSubscriptionTypeById(int $subscriptionTypeId) :? SubscriptionType
    {
        return config('subscription.type.model')::find($subscriptionTypeId);
    }

    /**
     * Get @class Subscription::class by recurring payment identificator from DB
     *
     * @param string $recurringPaymentId - application recurring payment identificator
     *
     * @return null|Subscription
     */
    public function getSubscriptionByRecurringPaymentId(string $recurringPaymentId)
    {
        return config('subscription.subscription_model')::whereRecurringPaymentId($recurringPaymentId)->first();
    }

    /**
     * Create new @class Transaction::class row
     *
     * @param array $attributes
     *
     * @return Transaction
     */
    public function createTransaction(array $attributes)
    {
        return config('subscription.transaction_model')::create($attributes);
    }

    /**
     * Get all available @class SubscriptionCoupon::class
     *
     * @return array|Collection
     */
    public function getCoupons()
    {
        return config('subscription.coupon.model')::all();
    }

    /**
     * Create new @class SubscriptionCoupon::class row
     *
     * @param array $attributes
     *
     * @return Transaction
     */
    public function createCoupon(array $attributes)
    {
        return config('subscription.coupon.model')::create($attributes);
    }

    /**
     * Update @class SubscriptionCoupon::class row by coupon identificator
     *
     * @param array $attributes
     * @param int   $couponId - coupon identificator
     *
     * @return bool - Is updated row?
     */
    public function updateCoupon(array $attributes, int $couponId)
    {
        return config('subscription.coupon.model')::find($couponId)->update($attributes);
    }

    /**
     * Remove @class SubscriptionCoupon::class from DB row by coupon identificator
     *
     * @param int $couponId - coupon identificator
     *
     * @return bool - Is Removed row?
     */
    public function deleteCoupon(int $couponId)
    {
        return config('subscription.coupon.model')::where('id', $couponId)->delete();
    }

    /**
     * Update @class SubscriptionType::class row by subscription type identificator
     *
     * @param array $attributes
     * @param int   $subscriptionTypeId - subscription type identificator
     *
     * @return bool - Is updated row?
     */
    public function updateSubscriptionType(array $attributes, int $subscriptionTypeId) : bool
    {
        return config('subscription.type.model')::find($subscriptionTypeId)->update($attributes);
    }

}