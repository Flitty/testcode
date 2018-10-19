<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 16:47
 */

namespace Subscription\Contracts;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\RedirectResponse;
use Subscription\Exceptions\UserAlreadyHasTheSubscription;
use Subscription\Models\Subscription;
use Subscription\Providers\SubscriptionServiceProvider;

/**
 * Interface SubscriberContract
 * @package Subscription\Contracts
 */
interface SubscriberContract
{
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
    public function getSubscriptions(string $status = Subscription::LIVE_STATUS) : Collection;

    /**
     * Entities subscriptions relation
     *
     * @return HasMany
     */
    public function availableSubscriptions() : HasMany;

    /**
     * @param int    $subscriptionTypeId
     * @param string $status
     *
     * @return null|Subscription
     */
    public function getTypeSubscription(int $subscriptionTypeId, string $status = Subscription::LIVE_STATUS) :? Subscription;

    /**
     * Get entity identificator
     *
     * @return int - entitie identificator
     */
    public function getId() : int;

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
    public function subscribeEntity(int $subscriptionTypeId, $subscriptionCouponName = null, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : RedirectResponse;

    /**
     * Cancel live subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is canceled subscription?
     */
    public function cancelSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool;

    /**
     * Suspend live subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is suspended subscription?
     */
    public function suspendSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool;

    /**
     * Reactivate suspended subscription
     *
     * @param int    $subscriptionTypeId
     * @param string $driver - subscription driver
     *
     * @return bool - is reactivated subscription?
     */
    public function reactivateSubscription(int $subscriptionTypeId, $driver = SubscriptionServiceProvider::PAY_PAL_DRIVER) : bool;
}