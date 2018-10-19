<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 16:39
 */

namespace Subscription\Traits;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Subscription\Exceptions\UserAlreadyHasTheSubscription;
use Subscription\Models\Subscription;
use Subscription\Providers\SubscriptionServiceProvider;

/**
 * Trait Subscriber
 * @property Collection $subscriptions - All entities subscriptions
 * @package Subscription\Traits
 */
trait Subscriber
{
    protected $subscriptions;

    /**
     * Get entities subscriptions
     *
     * @param string $status - subscription status can be:
     *      @const Subscription:: LIVE_STATUS
     *      @const Subscription:: CANCELED_STATUS
     *      @const Subscription:: EXPIRED_STATUS
     *      @const Subscription:: SUSPENDED_STATUS
     *
     * @return Collection
     */
    public function getSubscriptions(string $status = Subscription::LIVE_STATUS) : Collection
    {
        $cacheKey = $this->getId() . 'live-subscription-cache-key-' . $status;
        Cache::forget($cacheKey);
        return $this->subscriptions =  Cache::remember($cacheKey, 1, function() use ($status) {
            $now = Carbon::now()->toDateTimeString();
            /** @var Builder $query */
            $query = $this->availableSubscriptions()
                ->where('status', $status);
            if ($status == Subscription::LIVE_STATUS) {
                /** @var Builder $query */
                $query = $query->where('expire_at', '>', $now);
            }
            return $query->get();
        });
    }

    /**
     * Entities subscriptions relation
     *
     * @return HasMany
     */
    public function availableSubscriptions() : HasMany
    {
        return $this->hasMany(
            config('subscription.subscription_model'),
            config('subscription.subscriber_foreign'),
            config('subscription.subscriber_owner')
        );
    }

    /**
     * @param int    $subscriptionTypeId
     * @param string $status
     *
     * @return null|Subscription
     */
    public function getTypeSubscription(int $subscriptionTypeId, string $status = Subscription::LIVE_STATUS) :? Subscription
    {
        return $this->getSubscriptions($status)->where('subscription_type_id', $subscriptionTypeId)->first();
    }

    /**
     * Get entity identificator
     *
     * @return int - entitie identificator
     */
    public function getId() : int
    {
        return $this->{$this->primaryKey};
    }

    /**
     * Subscribe entity
     *
     * @param int    $subscriptionTypeId
     * @param null   $subscriptionCouponName
     * @param string $driver - subscription driver
     *
     * @return RedirectResponse - redirect to subscription form
     * @throws UserAlreadyHasTheSubscription
     */
    public function subscribeEntity(int $subscriptionTypeId, $subscriptionCouponName = null, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : RedirectResponse
    {
        $couponId = null;
        if ($subscriptionCouponName) {
            $subscriptionCoupon = app('sub-service')->getCoupon($subscriptionCouponName);
            $couponId = $subscriptionCoupon ? $subscriptionCoupon->id : null;
        }
        if ($this->getTypeSubscription($subscriptionTypeId)) {
            throw new UserAlreadyHasTheSubscription();
        }
        return app($driver)->createSubscription($this, $subscriptionTypeId, $couponId);
    }


    /**
     * Cancel live subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is canceled subscription?
     */
    public function cancelSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool
    {
        return app($driver)->cancelSubscription($this, $subscriptionTypeId);
    }

    /**
     * Suspend live subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is suspended subscription?
     */
    public function suspendSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool
    {
        return app($driver)->suspendSubscription($this, $subscriptionTypeId);
    }

    /**
     * Reactivate suspended subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is reactivated subscription?
     */
    public function reactivateSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool
    {
        return app($driver)->reactivateSubscription($this, $subscriptionTypeId);
    }

}