<?php
/**
 * Created by PhpStorm.
 * User: nikolaygolub
 * Date: 04.10.2018
 * Time: 18:25
 */

namespace Subscription\Contracts;

use Illuminate\Database\Eloquent\Builder;

/**
 * Interface SubscribeEntityContract
 * @package Subscription\Contracts
 */
interface SubscribeEntityContract
{
    /**
     * Returns entities available by selected subscription
     *
     * @param Builder            $query
     * @param SubscriberContract $subscriber - entity with potential subscription
     *
     * @return Builder
     */
    public function scopeSubscribable(Builder $query, SubscriberContract $subscriber);

    /**
     * Set current subscription type id
     *
     * @param int $entityTypeId
     */
    public function setSubscriptionTypeId(int $entityTypeId);

    /**
     * Get current subscription type id
     *
     * @return int - subscription type id
     */
    public function getSubscriptionTypeId() : int;

}